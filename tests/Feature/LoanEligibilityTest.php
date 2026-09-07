<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanProgram;
use App\Models\LoanTerm;
use App\Models\User;
use App\Services\LoanEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private LoanProgram $program;
    private User $member;
    private LoanEligibilityService $eligibility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program = LoanProgram::factory()->create(['fund_pool' => 1_000_000]);
        LoanTerm::create([
            'loan_program_id' => $this->program->id,
            'version' => 'v1',
            'body' => '<p>Terms.</p>',
            'published_at' => now(),
        ]);

        $this->member = User::factory()->create([
            'status' => 'approved',
            'graduating_set_id' => $this->program->set_id,
        ]);

        $this->eligibility = app(LoanEligibilityService::class);
    }

    public function test_an_approved_member_of_the_set_may_apply(): void
    {
        $this->assertSame([], $this->eligibility->reasons($this->member, $this->program, 100_000));
    }

    public function test_a_pending_member_may_not_apply(): void
    {
        $this->member->update(['status' => 'pending']);

        $this->assertContains('Your membership is still awaiting approval.', $this->eligibility->reasons($this->member, $this->program));
    }

    public function test_a_member_of_another_set_may_not_apply(): void
    {
        $outsider = User::factory()->create(['status' => 'approved']);

        $this->assertFalse($this->eligibility->passes($outsider, $this->program));
    }

    public function test_a_paused_fund_takes_no_applications(): void
    {
        $this->program->update(['status' => 'paused']);

        $this->assertContains('This fund is not currently accepting applications.', $this->eligibility->reasons($this->member, $this->program));
    }

    public function test_a_fund_with_no_published_terms_takes_no_applications(): void
    {
        $this->program->terms()->update(['published_at' => null]);

        $this->assertContains(
            'This fund has not yet published its terms of operation.',
            $this->eligibility->reasons($this->member->fresh(), $this->program->fresh())
        );
    }

    public function test_a_second_concurrent_loan_is_blocked_but_a_settled_one_is_not(): void
    {
        $loan = Loan::factory()->for($this->program, 'program')->create([
            'set_id' => $this->program->set_id,
            'user_id' => $this->member->id,
            'status' => 'disbursed',
        ]);

        $this->assertFalse($this->eligibility->passes($this->member, $this->program));

        $loan->update(['status' => 'settled']);

        $this->assertTrue($this->eligibility->passes($this->member, $this->program));
    }

    public function test_a_written_off_loan_closes_the_fund_to_that_member(): void
    {
        Loan::factory()->for($this->program, 'program')->create([
            'set_id' => $this->program->set_id,
            'user_id' => $this->member->id,
            'status' => 'written_off',
            'written_off_at' => now(),
        ]);

        $this->assertContains(
            'A previous loan of yours was written off. Please speak to the welfare committee.',
            $this->eligibility->reasons($this->member, $this->program)
        );
    }

    public function test_amounts_outside_the_funds_range_are_blocked(): void
    {
        $this->assertNotSame([], $this->eligibility->reasons($this->member, $this->program, 1_000));
        $this->assertNotSame([], $this->eligibility->reasons($this->member, $this->program, 900_000));
    }

    public function test_an_amount_beyond_the_remaining_headroom_is_blocked(): void
    {
        $this->program->update(['fund_pool' => 150_000]);

        Loan::factory()->for($this->program, 'program')->create([
            'set_id' => $this->program->set_id,
            'user_id' => User::factory()->create(['graduating_set_id' => $this->program->set_id])->id,
            'amount_requested' => 100_000,
            'amount_approved' => 100_000,
            'status' => 'disbursed',
        ]);

        $reasons = $this->eligibility->reasons($this->member, $this->program->fresh(), 100_000);

        $this->assertNotSame([], $reasons);
        $this->assertStringContainsString('50,000 left uncommitted', implode(' ', $reasons));
    }

    public function test_an_account_younger_than_the_minimum_tenure_is_blocked(): void
    {
        $this->program->update(['min_member_tenure_days' => 30]);
        $this->member->update(['created_at' => now()->subDays(5)]);

        $this->assertContains(
            'Accounts must be at least 30 days old to apply.',
            $this->eligibility->reasons($this->member->fresh(), $this->program->fresh())
        );
    }
}
