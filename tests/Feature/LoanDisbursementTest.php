<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanProgram;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LoanDisbursed;
use App\Notifications\LoanDisbursementRequest;
use App\Services\LoanDisbursementService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class LoanDisbursementTest extends TestCase
{
    use RefreshDatabase;

    private LoanProgram $program;
    private User $member;
    private User $reviewer;
    private User $disburser;
    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Setting::set('loans_enabled', 'true', 'modules');

        $this->program = LoanProgram::factory()->create(['grace_days' => 7]);
        $this->member = $this->setMember();

        $this->reviewer = $this->setMember();
        $this->reviewer->assignRole('loan_officer');
        $this->program->officers()->attach($this->reviewer->id, ['role' => 'reviewer']);

        $this->disburser = $this->setMember();
        $this->disburser->assignRole('loan_disburser');
        $this->program->officers()->attach($this->disburser->id, ['role' => 'disburser']);

        $this->loan = Loan::factory()->for($this->program, 'program')->create([
            'set_id' => $this->program->set_id,
            'user_id' => $this->member->id,
            'status' => 'guarantors_secured',
            'amount_requested' => 100000,
            'amount_approved' => 100000,
            'tenure_months' => 3,
            'interest_rate_bps' => 500,
            'guarantors_secured_at' => now(),
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

    private function service(): LoanDisbursementService
    {
        return app(LoanDisbursementService::class);
    }

    public function test_releasing_notifies_the_disburser_and_names_them_on_the_loan(): void
    {
        Notification::fake();

        $this->service()->release($this->loan, $this->reviewer);

        $fresh = $this->loan->fresh();
        $this->assertSame('disburser_notified', $fresh->status);
        $this->assertSame($this->disburser->id, $fresh->disburser_id);
        $this->assertNotNull($fresh->disburser_notified_at);

        Notification::assertSentTo($this->disburser, LoanDisbursementRequest::class);
    }

    public function test_a_fund_with_no_disburser_cannot_release(): void
    {
        $this->program->officers()->detach($this->disburser->id);

        $this->expectException(RuntimeException::class);
        $this->service()->release($this->loan->fresh(), $this->reviewer);
    }

    public function test_the_transfer_cannot_be_recorded_before_the_notice_is_acknowledged(): void
    {
        $this->service()->release($this->loan, $this->reviewer);

        $this->expectException(RuntimeException::class);
        $this->service()->recordDisbursement($this->loan->fresh(), $this->disburser, [
            'disbursed_amount' => 100000,
            'disbursed_at' => now()->toDateString(),
        ]);
    }

    public function test_the_clock_is_counted_from_the_entered_date_not_today(): void
    {
        Notification::fake();

        $this->service()->release($this->loan, $this->reviewer);
        $this->service()->acknowledge($this->loan->fresh(), $this->disburser);

        // The transfer was made three days ago; the due date must reflect that.
        $transferDate = now()->subDays(3)->startOfDay();

        $this->service()->recordDisbursement($this->loan->fresh(), $this->disburser, [
            'disbursed_amount' => 100000,
            'disbursed_at' => $transferDate->toDateString(),
            'disbursement_method' => 'bank_transfer',
            'disbursement_reference' => 'TRF-9001',
        ]);

        $fresh = $this->loan->fresh();
        $this->assertSame('disbursed', $fresh->status);
        $this->assertSame($transferDate->toDateString(), $fresh->disbursed_at->toDateString());
        $this->assertSame($transferDate->copy()->addMonthsNoOverflow(3)->toDateString(), $fresh->due_at->toDateString());
        $this->assertSame($transferDate->copy()->addMonthsNoOverflow(3)->addDays(7)->toDateString(), $fresh->grace_ends_at->toDateString());
    }

    public function test_interest_is_frozen_from_the_amount_actually_disbursed(): void
    {
        $this->service()->release($this->loan, $this->reviewer);
        $this->service()->acknowledge($this->loan->fresh(), $this->disburser);

        // Only 80,000 of the approved 100,000 actually went out.
        $this->service()->recordDisbursement($this->loan->fresh(), $this->disburser, [
            'disbursed_amount' => 80000,
            'disbursed_at' => now()->toDateString(),
        ]);

        $fresh = $this->loan->fresh();
        $this->assertSame(4000, $fresh->interest_amount);
        $this->assertSame(84000, $fresh->total_repayable);
        $this->assertSame(84000, $fresh->outstanding_amount);

        // A later change to the fund's rate must not move a live loan.
        $this->program->update(['interest_rate_bps' => 2500]);
        $this->assertSame(4000, $this->loan->fresh()->interest_amount);
    }

    public function test_more_than_the_approved_amount_cannot_be_disbursed(): void
    {
        $this->service()->release($this->loan, $this->reviewer);
        $this->service()->acknowledge($this->loan->fresh(), $this->disburser);

        $this->expectException(RuntimeException::class);
        $this->service()->recordDisbursement($this->loan->fresh(), $this->disburser, [
            'disbursed_amount' => 150000,
            'disbursed_at' => now()->toDateString(),
        ]);
    }

    public function test_a_future_disbursement_date_is_refused(): void
    {
        $this->service()->release($this->loan, $this->reviewer);
        $this->service()->acknowledge($this->loan->fresh(), $this->disburser);

        $this->expectException(RuntimeException::class);
        $this->service()->recordDisbursement($this->loan->fresh(), $this->disburser, [
            'disbursed_amount' => 100000,
            'disbursed_at' => now()->addWeek()->toDateString(),
        ]);
    }

    public function test_the_applicant_and_every_guarantor_are_told_the_money_has_gone_out(): void
    {
        $guarantor = $this->setMember();
        $this->loan->guarantors()->create([
            'user_id' => $guarantor->id,
            'status' => 'accepted',
            'invited_at' => now(),
            'responded_at' => now(),
        ]);

        Notification::fake();

        $this->service()->release($this->loan, $this->reviewer);
        $this->service()->acknowledge($this->loan->fresh(), $this->disburser);
        $this->service()->recordDisbursement($this->loan->fresh(), $this->disburser, [
            'disbursed_amount' => 100000,
            'disbursed_at' => now()->toDateString(),
        ]);

        Notification::assertSentTo($this->member, LoanDisbursed::class);
        Notification::assertSentTo($guarantor, LoanDisbursed::class);
    }

    public function test_disbursing_needs_the_office_in_this_fund(): void
    {
        $this->assertTrue(Gate::forUser($this->disburser)->allows('disburse', $this->loan));
        $this->assertFalse(Gate::forUser($this->reviewer)->allows('disburse', $this->loan));

        $strayRole = User::factory()->create();
        $strayRole->assignRole('loan_disburser');
        $this->assertFalse(Gate::forUser($strayRole)->allows('disburse', $this->loan));
    }

    public function test_proof_of_transfer_is_private_and_only_served_to_people_entitled_to_the_loan(): void
    {
        Storage::fake(config('loans.private_disk'));

        $path = UploadedFile::fake()->create('transfer.pdf', 100, 'application/pdf')
            ->store('loan-disbursements', config('loans.private_disk'));

        $this->service()->release($this->loan, $this->reviewer);
        $this->service()->acknowledge($this->loan->fresh(), $this->disburser);
        $this->service()->recordDisbursement($this->loan->fresh(), $this->disburser, [
            'disbursed_amount' => 100000,
            'disbursed_at' => now()->toDateString(),
            'disbursement_proof_path' => $path,
        ]);

        Storage::disk(config('loans.private_disk'))->assertExists($path);

        $this->actingAs($this->member)->get("/loans/{$this->loan->id}/proof/disbursement")->assertOk();
        $this->actingAs($this->reviewer)->get("/loans/{$this->loan->id}/proof/disbursement")->assertOk();

        $stranger = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->actingAs($stranger)->get("/loans/{$this->loan->id}/proof/disbursement")->assertForbidden();
    }
}
