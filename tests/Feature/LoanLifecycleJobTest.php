<?php

namespace Tests\Feature;

use App\Jobs\ProcessLoanLifecycle;
use App\Jobs\SendLoanReminders;
use App\Models\Loan;
use App\Models\LoanProgram;
use App\Models\LoanStateTransition;
use App\Models\Setting;
use App\Models\UnikosaNotification;
use App\Models\User;
use App\Notifications\LoanDefaulted;
use App\Notifications\LoanDueSoon;
use App\Notifications\LoanGuarantorNudge;
use App\Notifications\LoanOverdue;
use App\Services\LoanRepaymentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanLifecycleJobTest extends TestCase
{
    use RefreshDatabase;

    private LoanProgram $program;
    private User $borrower;
    private User $guarantor;
    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Setting::set('loans_enabled', 'true', 'modules');

        $this->program = LoanProgram::factory()->create([
            'grace_days' => 7,
            'reminder_days_before' => [14, 7, 1],
        ]);

        $this->borrower = $this->setMember();
        $this->guarantor = $this->setMember();

        // Disbursed 90 days ago on a 3-month tenure: due imminently.
        $this->loan = Loan::factory()->for($this->program, 'program')->disbursed(90)->create([
            'set_id' => $this->program->set_id,
            'user_id' => $this->borrower->id,
        ]);

        $this->loan->guarantors()->create([
            'user_id' => $this->guarantor->id,
            'status' => 'accepted',
            'invited_at' => now(),
            'responded_at' => now(),
        ]);
    }

    private function setMember(): User
    {
        return User::factory()->create([
            'status' => 'approved',
            'email_verified_at' => now(),
            'graduating_set_id' => $this->program->set_id,
        ]);
    }

    /** Put the loan a given number of days past its due date. */
    private function shiftDueDate(int $daysAgo): void
    {
        $due = now()->subDays($daysAgo)->startOfDay();

        $this->loan->forceFill([
            'due_at' => $due,
            'grace_ends_at' => $due->copy()->addDays($this->program->grace_days),
        ])->saveQuietly();
    }

    public function test_a_reminder_goes_out_only_on_a_configured_offset(): void
    {
        Notification::fake();

        $this->loan->forceFill(['due_at' => now()->addDays(5)->startOfDay()])->saveQuietly();
        (new SendLoanReminders)->handle(app(\App\Services\LoanNotifier::class));
        Notification::assertNothingSent();

        $this->loan->forceFill(['due_at' => now()->addDays(7)->startOfDay()])->saveQuietly();
        (new SendLoanReminders)->handle(app(\App\Services\LoanNotifier::class));
        Notification::assertSentTo($this->borrower, LoanDueSoon::class);

        // Guarantors are not chased before the loan is even late.
        Notification::assertNotSentTo($this->guarantor, LoanDueSoon::class);
    }

    public function test_a_reminder_is_not_repeated_the_same_day(): void
    {
        Notification::fake();
        $this->loan->forceFill(['due_at' => now()->addDays(7)->startOfDay()])->saveQuietly();

        (new SendLoanReminders)->handle(app(\App\Services\LoanNotifier::class));
        (new SendLoanReminders)->handle(app(\App\Services\LoanNotifier::class));

        Notification::assertSentToTimes($this->borrower, LoanDueSoon::class, 1);
    }

    public function test_a_loan_already_repaid_is_not_reminded(): void
    {
        Notification::fake();
        $this->loan->forceFill(['due_at' => now()->addDays(7)->startOfDay()])->saveQuietly();
        $this->loan->repayments()->create([
            'amount' => 105000,
            'paid_on' => now()->subDay(),
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        (new SendLoanReminders)->handle(app(\App\Services\LoanNotifier::class));

        Notification::assertNothingSent();
    }

    public function test_passing_the_due_date_makes_the_loan_overdue_and_nudges_every_guarantor(): void
    {
        Notification::fake();
        $this->shiftDueDate(1);

        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));

        $fresh = $this->loan->fresh();
        $this->assertSame('in_grace', $fresh->status, 'Overdue and the grace window are one pass.');
        $this->assertNotNull($fresh->guarantors_nudged_at);

        Notification::assertSentTo($this->borrower, LoanOverdue::class);
        Notification::assertSentTo($this->guarantor, LoanGuarantorNudge::class);

        // The timeline has to show both steps, not just where it ended up.
        $moves = LoanStateTransition::where('loan_id', $this->loan->id)->pluck('to_status');
        $this->assertTrue($moves->contains('overdue'));
        $this->assertTrue($moves->contains('in_grace'));
    }

    public function test_a_loan_still_inside_its_term_is_left_alone(): void
    {
        Notification::fake();
        $this->loan->forceFill(['due_at' => now()->addDays(3)->startOfDay()])->saveQuietly();

        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));

        $this->assertSame('disbursed', $this->loan->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_the_grace_window_closing_defaults_the_loan_and_tells_the_fund(): void
    {
        $reviewer = $this->setMember();
        $reviewer->assignRole('loan_officer');
        $this->program->officers()->attach($reviewer->id, ['role' => 'reviewer']);

        Notification::fake();
        $this->shiftDueDate(9); // one day past a 7-day grace window

        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));

        $fresh = $this->loan->fresh();
        $this->assertSame('defaulted', $fresh->status);
        $this->assertNotNull($fresh->defaulted_at);

        Notification::assertSentTo($this->borrower, LoanDefaulted::class);
        Notification::assertSentTo($this->guarantor, LoanDefaulted::class);
        Notification::assertSentTo($reviewer, LoanDefaulted::class);
    }

    public function test_a_loan_inside_its_grace_window_does_not_default(): void
    {
        $this->shiftDueDate(3);

        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));

        $this->assertSame('in_grace', $this->loan->fresh()->status);
    }

    public function test_running_the_job_twice_notifies_nobody_twice(): void
    {
        Notification::fake();
        $this->shiftDueDate(1);

        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));
        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));

        Notification::assertSentToTimes($this->borrower, LoanOverdue::class, 1);
        Notification::assertSentToTimes($this->guarantor, LoanGuarantorNudge::class, 1);

        $this->assertSame(1, LoanStateTransition::where('loan_id', $this->loan->id)->where('to_status', 'overdue')->count());
        $this->assertSame(
            1,
            UnikosaNotification::where('user_id', $this->guarantor->id)->where('type', 'loan_guarantor_nudge')->count()
        );
    }

    public function test_defaulting_twice_is_impossible(): void
    {
        Notification::fake();
        $this->shiftDueDate(9);

        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));
        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));

        Notification::assertSentToTimes($this->borrower, LoanDefaulted::class, 1);
        $this->assertSame(1, LoanStateTransition::where('loan_id', $this->loan->id)->where('to_status', 'defaulted')->count());
    }

    public function test_a_guarantor_clearing_the_balance_stops_the_default(): void
    {
        $treasurer = $this->setMember();
        $treasurer->assignRole('loan_officer');
        $this->program->officers()->attach($treasurer->id, ['role' => 'treasurer']);

        $this->shiftDueDate(1);
        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));
        $this->assertSame('in_grace', $this->loan->fresh()->status);

        // The guarantor pays it off inside the grace window.
        $repayments = app(LoanRepaymentService::class);
        $repayment = $repayments->record($this->loan->fresh(), $this->guarantor, [
            'amount' => 105000,
            'paid_on' => now()->toDateString(),
        ]);
        $repayments->confirm($repayment, $treasurer);

        $this->assertSame('settled', $this->loan->fresh()->status);

        // Even once the grace window has passed, a settled loan cannot default.
        $this->shiftDueDate(9);
        $this->loan->forceFill(['status' => 'settled'])->saveQuietly();
        (new ProcessLoanLifecycle)->handle(app(\App\Services\LoanStateMachine::class));

        $this->assertSame('settled', $this->loan->fresh()->status);
    }
}
