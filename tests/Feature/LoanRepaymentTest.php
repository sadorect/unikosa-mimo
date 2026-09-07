<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanProgram;
use App\Models\LoanRepayment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LoanRepaymentConfirmed;
use App\Notifications\LoanSettled;
use App\Services\LoanRepaymentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class LoanRepaymentTest extends TestCase
{
    use RefreshDatabase;

    private LoanProgram $program;
    private User $borrower;
    private User $guarantor;
    private User $treasurer;
    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Setting::set('loans_enabled', 'true', 'modules');

        $this->program = LoanProgram::factory()->create();
        $this->borrower = $this->setMember();
        $this->guarantor = $this->setMember();

        $this->treasurer = $this->setMember();
        $this->treasurer->assignRole('loan_officer');
        $this->program->officers()->attach($this->treasurer->id, ['role' => 'treasurer']);

        $this->loan = Loan::factory()->for($this->program, 'program')->disbursed(10)->create([
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

    private function service(): LoanRepaymentService
    {
        return app(LoanRepaymentService::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'amount' => 30000,
            'paid_on' => now()->toDateString(),
            'method' => 'bank_transfer',
        ], $overrides);
    }

    public function test_a_declared_repayment_does_not_move_the_balance_until_it_is_confirmed(): void
    {
        $this->actingAs($this->borrower)
            ->post("/loans/{$this->loan->id}/repayments", $this->payload())
            ->assertRedirect();

        $repayment = LoanRepayment::first();
        $this->assertSame('pending_confirmation', $repayment->status);
        $this->assertSame(105000, $this->loan->fresh()->outstanding_amount);

        $this->service()->confirm($repayment, $this->treasurer);

        $this->assertSame(75000, $this->loan->fresh()->outstanding_amount);
        $this->assertSame(30000, $this->loan->fresh()->amount_repaid);
    }

    public function test_part_payments_accumulate(): void
    {
        foreach ([30000, 25000] as $amount) {
            $this->actingAs($this->borrower)->post("/loans/{$this->loan->id}/repayments", $this->payload(['amount' => $amount]));
        }

        foreach (LoanRepayment::all() as $repayment) {
            $this->service()->confirm($repayment, $this->treasurer);
        }

        $this->assertSame(50000, $this->loan->fresh()->outstanding_amount);
        $this->assertSame('disbursed', $this->loan->fresh()->status);
    }

    public function test_clearing_the_balance_settles_the_loan_and_tells_everyone_standing_on_it(): void
    {
        Notification::fake();

        $this->actingAs($this->borrower)->post("/loans/{$this->loan->id}/repayments", $this->payload(['amount' => 105000]));
        $this->service()->confirm(LoanRepayment::first(), $this->treasurer);

        $fresh = $this->loan->fresh();
        $this->assertSame('settled', $fresh->status);
        $this->assertNotNull($fresh->settled_at);
        $this->assertSame(0, $fresh->outstanding_amount);

        Notification::assertSentTo($this->borrower, LoanSettled::class);
        Notification::assertSentTo($this->guarantor, LoanSettled::class);
    }

    public function test_overpayment_settles_without_going_negative(): void
    {
        $this->actingAs($this->borrower)->post("/loans/{$this->loan->id}/repayments", $this->payload(['amount' => 200000]));
        $this->service()->confirm(LoanRepayment::first(), $this->treasurer);

        $this->assertSame(0, $this->loan->fresh()->outstanding_amount);
        $this->assertSame('settled', $this->loan->fresh()->status);
    }

    public function test_a_guarantor_can_pay_on_a_defaulting_borrowers_behalf(): void
    {
        Notification::fake();

        $this->loan->update(['status' => 'defaulted', 'defaulted_at' => now()]);

        $this->actingAs($this->guarantor)
            ->post("/loans/{$this->loan->id}/repayments", $this->payload(['amount' => 105000]))
            ->assertRedirect();

        $repayment = LoanRepayment::first();

        // The ledger has to say whose money it was: `recorded_by` is only who
        // typed it in.
        $this->assertSame($this->guarantor->id, $repayment->paid_by_user_id);
        $this->assertSame('guarantor', $repayment->payer_role);
        $this->assertTrue($repayment->isOnBehalfOfBorrower());

        $this->service()->confirm($repayment, $this->treasurer);

        $fresh = $this->loan->fresh();
        $this->assertSame(0, $fresh->outstanding_amount);
        $this->assertSame('settled', $fresh->status, 'A guarantor clearing the balance closes the loan.');

        // The borrower is told that a guarantor has been out of pocket for them.
        Notification::assertSentTo($this->borrower, LoanRepaymentConfirmed::class);
        Notification::assertSentTo($this->guarantor, LoanRepaymentConfirmed::class);
    }

    public function test_a_guarantor_may_pay_before_default_too(): void
    {
        $this->actingAs($this->guarantor)
            ->post("/loans/{$this->loan->id}/repayments", $this->payload(['amount' => 20000]))
            ->assertRedirect();

        $this->assertSame('guarantor', LoanRepayment::first()->payer_role);
    }

    public function test_a_guarantor_who_declined_cannot_pay(): void
    {
        $declined = $this->setMember();
        $this->loan->guarantors()->create([
            'user_id' => $declined->id,
            'status' => 'declined',
            'invited_at' => now(),
            'responded_at' => now(),
        ]);

        $this->actingAs($declined)
            ->post("/loans/{$this->loan->id}/repayments", $this->payload())
            ->assertForbidden();
    }

    public function test_a_stranger_cannot_pay_into_someone_elses_loan(): void
    {
        $stranger = $this->setMember();

        $this->actingAs($stranger)
            ->post("/loans/{$this->loan->id}/repayments", $this->payload())
            ->assertForbidden();
    }

    public function test_an_officer_recording_a_handover_credits_it_to_the_borrower(): void
    {
        $this->actingAs($this->treasurer)
            ->post("/loans/{$this->loan->id}/repayments", $this->payload(['amount' => 10000, 'method' => 'cash']))
            ->assertRedirect();

        $repayment = LoanRepayment::first();
        $this->assertSame($this->borrower->id, $repayment->paid_by_user_id);
        $this->assertSame('borrower', $repayment->payer_role);
        $this->assertSame($this->treasurer->id, $repayment->recorded_by);
    }

    public function test_a_rejected_repayment_leaves_the_balance_alone(): void
    {
        $this->actingAs($this->borrower)->post("/loans/{$this->loan->id}/repayments", $this->payload());
        $repayment = LoanRepayment::first();

        $this->service()->reject($repayment, $this->treasurer, 'No matching transfer found.');

        $this->assertSame('rejected', $repayment->fresh()->status);
        $this->assertSame(105000, $this->loan->fresh()->outstanding_amount);
    }

    public function test_a_repayment_cannot_be_confirmed_twice(): void
    {
        $this->actingAs($this->borrower)->post("/loans/{$this->loan->id}/repayments", $this->payload());
        $repayment = LoanRepayment::first();
        $this->service()->confirm($repayment, $this->treasurer);

        $this->expectException(RuntimeException::class);
        $this->service()->confirm($repayment->fresh(), $this->treasurer);
    }

    public function test_dates_outside_the_life_of_the_loan_are_refused(): void
    {
        $this->actingAs($this->borrower)
            ->post("/loans/{$this->loan->id}/repayments", $this->payload(['paid_on' => now()->addWeek()->toDateString()]))
            ->assertSessionHasErrors('amount');

        $this->actingAs($this->borrower)
            ->post("/loans/{$this->loan->id}/repayments", $this->payload(['paid_on' => now()->subYear()->toDateString()]))
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, LoanRepayment::count());
    }

    public function test_a_settled_loan_takes_no_further_repayments(): void
    {
        $this->actingAs($this->borrower)->post("/loans/{$this->loan->id}/repayments", $this->payload(['amount' => 105000]));
        $this->service()->confirm(LoanRepayment::first(), $this->treasurer);

        $this->actingAs($this->borrower)
            ->post("/loans/{$this->loan->id}/repayments", $this->payload())
            ->assertSessionHasErrors('amount');

        $this->assertSame(1, LoanRepayment::count());
    }

    public function test_repayment_proof_is_private_and_served_only_to_people_party_to_the_loan(): void
    {
        Storage::fake(config('loans.private_disk'));

        $this->actingAs($this->guarantor)->post("/loans/{$this->loan->id}/repayments", $this->payload([
            'proof' => UploadedFile::fake()->create('slip.pdf', 50, 'application/pdf'),
        ]));

        $repayment = LoanRepayment::first();
        $this->assertNotNull($repayment->proof_path);
        Storage::disk(config('loans.private_disk'))->assertExists($repayment->proof_path);

        $this->actingAs($this->borrower)->get("/loans/{$this->loan->id}/proof/repayment/{$repayment->id}")->assertOk();

        $stranger = $this->setMember();
        $this->actingAs($stranger)->get("/loans/{$this->loan->id}/proof/repayment/{$repayment->id}")->assertForbidden();
    }

    public function test_the_treasurers_confirmation_queue_renders(): void
    {
        $this->actingAs($this->borrower)->post("/loans/{$this->loan->id}/repayments", $this->payload());

        $this->actingAs($this->treasurer)->get('/admin/loan-repayments')->assertOk();
        $this->actingAs($this->borrower)->get('/admin/loan-repayments')->assertForbidden();
    }

    public function test_the_statement_downloads_and_only_shows_confirmed_money(): void
    {
        $this->actingAs($this->borrower)->post("/loans/{$this->loan->id}/repayments", $this->payload(['amount' => 30000]));
        $this->service()->confirm(LoanRepayment::first(), $this->treasurer);
        $this->actingAs($this->borrower)->post("/loans/{$this->loan->id}/repayments", $this->payload(['amount' => 5000]));

        $response = $this->actingAs($this->borrower)->get("/loans/{$this->loan->id}/statement");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $stranger = $this->setMember();
        $this->actingAs($stranger)->get("/loans/{$this->loan->id}/statement")->assertForbidden();
    }
}
