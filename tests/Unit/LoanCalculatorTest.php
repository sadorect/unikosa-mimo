<?php

namespace Tests\Unit;

use App\Services\LoanCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Loan arithmetic is integer-only on purpose: these assertions must hold
 * identically on sqlite (the suite) and Postgres (production).
 */
class LoanCalculatorTest extends TestCase
{
    private LoanCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new LoanCalculator;
    }

    public function test_flat_interest_is_charged_once_over_the_whole_tenure(): void
    {
        $this->assertSame(5000, $this->calculator->interest(100000, 500));
        $this->assertSame(105000, $this->calculator->totalRepayable(100000, 500));
    }

    public function test_a_zero_rate_charges_nothing(): void
    {
        $this->assertSame(0, $this->calculator->interest(100000, 0));
        $this->assertSame(100000, $this->calculator->totalRepayable(100000, 0));
    }

    public function test_interest_truncates_rather_than_rounding(): void
    {
        // 33,333 * 5% = 1,666.65 — intdiv truncates, deterministically.
        $this->assertSame(1666, $this->calculator->interest(33333, 500));
        $this->assertSame(0, $this->calculator->interest(19, 500));
    }

    public function test_outstanding_includes_penalty_and_never_goes_negative(): void
    {
        $this->assertSame(105000, $this->calculator->outstanding(105000, 0, 0));
        $this->assertSame(80000, $this->calculator->outstanding(105000, 0, 25000));
        $this->assertSame(107000, $this->calculator->outstanding(105000, 2000, 0));
        $this->assertSame(0, $this->calculator->outstanding(105000, 0, 200000));
    }

    public function test_due_date_does_not_overflow_a_short_month(): void
    {
        $due = $this->calculator->dueDate(Carbon::parse('2026-01-31'), 1);

        $this->assertSame('2026-02-28', $due->toDateString());
    }

    public function test_due_date_counts_from_the_disbursement_date(): void
    {
        $due = $this->calculator->dueDate(Carbon::parse('2026-09-01'), 3);

        $this->assertSame('2026-12-01', $due->toDateString());
        $this->assertSame('2026-12-08', $this->calculator->graceEndsAt($due, 7)->toDateString());
    }

    public function test_penalty_combines_a_flat_amount_and_a_rate(): void
    {
        $this->assertSame(0, $this->calculator->penalty(100000, null, null));
        $this->assertSame(5000, $this->calculator->penalty(100000, 5000, null));
        $this->assertSame(2000, $this->calculator->penalty(100000, null, 200));
        $this->assertSame(7000, $this->calculator->penalty(100000, 5000, 200));
    }
}
