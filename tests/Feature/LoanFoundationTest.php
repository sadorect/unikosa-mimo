<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanProgram;
use App\Models\LoanRepayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 covers the schema, the models and the fund configuration. The state
 * machine arrives with the application flow; what is asserted here is that the
 * balance is always derived from confirmed repayments and never from the
 * cached column.
 */
class LoanFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fund_reports_headroom_net_of_live_loans(): void
    {
        $program = LoanProgram::factory()->create(['fund_pool' => 1_000_000]);

        Loan::factory()->for($program, 'program')->create([
            'set_id' => $program->set_id,
            'amount_requested' => 100_000,
            'amount_approved' => 80_000,
            'status' => 'disbursed',
        ]);

        // A settled loan is terminal and releases its share of the pool again.
        Loan::factory()->for($program, 'program')->create([
            'set_id' => $program->set_id,
            'amount_requested' => 200_000,
            'amount_approved' => 200_000,
            'status' => 'settled',
        ]);

        $this->assertSame(920_000, $program->fresh()->availableHeadroom());
    }

    public function test_headroom_falls_back_to_the_requested_amount_before_approval(): void
    {
        $program = LoanProgram::factory()->create(['fund_pool' => 500_000]);

        Loan::factory()->for($program, 'program')->create([
            'set_id' => $program->set_id,
            'amount_requested' => 150_000,
            'amount_approved' => null,
            'status' => 'under_review',
        ]);

        $this->assertSame(350_000, $program->fresh()->availableHeadroom());
    }

    public function test_only_confirmed_repayments_reduce_the_balance(): void
    {
        $loan = Loan::factory()->disbursed()->create();

        $this->assertSame(105_000, $loan->outstanding_amount);

        LoanRepayment::factory()->for($loan)->create(['amount' => 30_000]);
        $this->assertSame(105_000, $loan->fresh()->outstanding_amount, 'A declared repayment must not move the balance.');

        LoanRepayment::factory()->for($loan)->confirmed()->create(['amount' => 30_000]);
        $this->assertSame(75_000, $loan->fresh()->outstanding_amount);

        LoanRepayment::factory()->for($loan)->rejected()->create(['amount' => 75_000]);
        $this->assertSame(75_000, $loan->fresh()->outstanding_amount);
    }

    public function test_a_penalty_raises_the_balance_and_overpayment_does_not_go_negative(): void
    {
        $loan = Loan::factory()->disbursed()->create(['penalty_amount' => 5_000]);

        $this->assertSame(110_000, $loan->outstanding_amount);

        LoanRepayment::factory()->for($loan)->confirmed()->create(['amount' => 200_000]);

        $this->assertSame(0, $loan->fresh()->outstanding_amount);
    }

    public function test_an_officer_appointment_is_scoped_to_one_fund_and_office(): void
    {
        $program = LoanProgram::factory()->create();
        $other = LoanProgram::factory()->create();
        $user = User::factory()->create();

        $program->officers()->attach($user->id, ['role' => 'reviewer']);

        $this->assertTrue($user->isLoanOfficer($program));
        $this->assertTrue($user->isLoanOfficer($program, 'reviewer'));
        $this->assertFalse($user->isLoanOfficer($program, 'disburser'));
        $this->assertFalse($user->isLoanOfficer($other));
    }

    public function test_an_officer_may_reach_the_admin_panel_without_a_loan_role(): void
    {
        $program = LoanProgram::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel(app(\Filament\Panel::class)));

        $program->officers()->attach($user->id, ['role' => 'disburser']);

        $this->assertTrue($user->fresh()->canAccessPanel(app(\Filament\Panel::class)));
    }
}
