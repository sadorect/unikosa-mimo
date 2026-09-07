<?php

namespace Tests\Feature;

use App\Filament\Resources\LoanResource;
use App\Models\Loan;
use App\Models\LoanProgram;
use App\Models\LoanTerm;
use App\Models\Setting;
use App\Models\User;
use App\Services\LoanStateMachine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LoanReviewTest extends TestCase
{
    use RefreshDatabase;

    private LoanProgram $program;
    private User $member;
    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Setting::set('loans_enabled', 'true', 'modules');

        $this->program = LoanProgram::factory()->create();
        LoanTerm::create([
            'loan_program_id' => $this->program->id,
            'version' => 'v1',
            'body' => '<p>Terms.</p>',
            'published_at' => now(),
        ]);

        $this->member = User::factory()->create([
            'status' => 'approved',
            'email_verified_at' => now(),
            'graduating_set_id' => $this->program->set_id,
        ]);

        $this->reviewer = User::factory()->create(['status' => 'approved', 'graduating_set_id' => $this->program->set_id]);
        $this->reviewer->assignRole('loan_officer');
        $this->program->officers()->attach($this->reviewer->id, ['role' => 'reviewer']);
    }

    private function submittedLoan(): Loan
    {
        $this->actingAs($this->member)->post('/loans', [
            'amount_requested' => 100000,
            'tenure_months' => 3,
            'purpose' => 'School fees.',
            'agree_terms' => true,
        ]);

        return Loan::first();
    }

    public function test_reviewing_needs_both_the_permission_and_an_office_in_this_fund(): void
    {
        $loan = $this->submittedLoan();

        $this->assertTrue(Gate::forUser($this->reviewer)->allows('review', $loan));

        // The permission alone is not enough: it must be this fund's committee.
        $strayPermission = User::factory()->create();
        $strayPermission->assignRole('loan_officer');
        $this->assertFalse(Gate::forUser($strayPermission)->allows('review', $loan));

        // Nor is the office alone, without the permission.
        $officeOnly = User::factory()->create(['graduating_set_id' => $this->program->set_id]);
        $this->program->officers()->attach($officeOnly->id, ['role' => 'reviewer']);
        $this->assertFalse(Gate::forUser($officeOnly)->allows('review', $loan));

        // A disburser of the same fund is not a reviewer of it.
        $disburser = User::factory()->create(['graduating_set_id' => $this->program->set_id]);
        $disburser->assignRole('loan_officer');
        $this->program->officers()->attach($disburser->id, ['role' => 'disburser']);
        $this->assertFalse(Gate::forUser($disburser)->allows('review', $loan));

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->assertTrue(Gate::forUser($superAdmin)->allows('review', $loan));
    }

    public function test_publishing_a_default_is_not_something_a_fund_officer_can_do(): void
    {
        $loan = $this->submittedLoan();

        $this->assertFalse(Gate::forUser($this->reviewer)->allows('publishDefault', $loan));

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->assertTrue(Gate::forUser($superAdmin)->allows('publishDefault', $loan));
    }

    public function test_an_officer_of_one_fund_sees_no_loans_from_another(): void
    {
        $mine = $this->submittedLoan();

        $otherProgram = LoanProgram::factory()->create();
        $theirs = Loan::factory()->for($otherProgram, 'program')->create(['set_id' => $otherProgram->set_id]);

        $this->actingAs($this->reviewer);
        $visible = LoanResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visible->contains($mine->id));
        $this->assertFalse($visible->contains($theirs->id));
    }

    public function test_approval_snapshots_the_terms_and_later_fund_edits_do_not_move_them(): void
    {
        $loan = $this->submittedLoan();
        $machine = app(LoanStateMachine::class);

        $machine->transition($loan, 'under_review', $this->reviewer, ['silent' => true]);
        $machine->transition($loan, 'approved', $this->reviewer, [
            'silent' => true,
            'attributes' => [
                'amount_approved' => 80000,
                'tenure_months' => 3,
                'interest_rate_bps' => $this->program->interest_rate_bps,
                'guarantors_required' => $this->program->required_guarantors,
            ],
        ]);

        $this->program->update(['interest_rate_bps' => 2000, 'required_guarantors' => 4]);

        $fresh = $loan->fresh();
        $this->assertSame(80000, $fresh->amount_approved);
        $this->assertSame(500, $fresh->interest_rate_bps);
        $this->assertSame(2, $fresh->guarantors_required);
    }

    public function test_a_sent_back_application_can_be_corrected_and_resubmitted(): void
    {
        $loan = $this->submittedLoan();
        $machine = app(LoanStateMachine::class);

        $machine->transition($loan, 'under_review', $this->reviewer, ['silent' => true]);
        $machine->transition($loan, 'changes_requested', $this->reviewer, [
            'silent' => true,
            'attributes' => ['review_notes' => 'Tell us what the money is for.'],
        ]);

        $this->actingAs($this->member)
            ->patch("/loans/{$loan->id}", [
                'amount_requested' => 90000,
                'tenure_months' => 3,
                'purpose' => 'School fees for two children.',
            ])
            ->assertRedirect();

        $fresh = $loan->fresh();
        $this->assertSame('submitted', $fresh->status);
        $this->assertSame(90000, $fresh->amount_requested);
        $this->assertNull($fresh->review_notes, 'The old note must not linger on a resubmitted application.');
    }

    public function test_a_rejected_application_is_terminal(): void
    {
        $loan = $this->submittedLoan();
        $machine = app(LoanStateMachine::class);

        $machine->transition($loan, 'under_review', $this->reviewer, ['silent' => true]);
        $machine->transition($loan, 'rejected', $this->reviewer, [
            'silent' => true,
            'attributes' => ['rejected_at' => now(), 'review_notes' => 'Not this cycle.'],
        ]);

        $this->actingAs($this->member)
            ->patch("/loans/{$loan->id}", [
                'amount_requested' => 50000,
                'tenure_months' => 3,
                'purpose' => 'Trying again.',
            ])
            ->assertForbidden();

        $this->assertSame('rejected', $loan->fresh()->status);
    }

    public function test_the_admin_screens_render_for_an_officer_but_not_a_plain_member(): void
    {
        $this->submittedLoan();

        $this->actingAs($this->reviewer)->get('/admin/loans')->assertOk();
        $this->actingAs($this->reviewer)->get('/admin/loan-programs')->assertOk();

        // A member with neither permission nor office gets nothing.
        $this->actingAs($this->member)->get('/admin/loans')->assertForbidden();
    }

    public function test_an_unrelated_member_cannot_see_someone_elses_loan(): void
    {
        $loan = $this->submittedLoan();
        $stranger = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->actingAs($stranger)->get("/loans/{$loan->id}")->assertForbidden();
        $this->actingAs($this->member)->get("/loans/{$loan->id}")->assertOk();
        $this->actingAs($this->reviewer)->get("/loans/{$loan->id}")->assertOk();
    }
}
