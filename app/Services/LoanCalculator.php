<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * All loan arithmetic, in one pure, integer-only place.
 *
 * Money is whole naira as `integer`, matching dues.amount and payments.amount.
 * Rates are basis points (500 = 5.00%) and every division is intdiv(): a
 * decimal rate rounds differently between Postgres numeric and sqlite REAL,
 * and the two must agree because the tests run on one and production on the
 * other.
 */
class LoanCalculator
{
    /**
     * Flat interest over the whole tenure, not per month: 5% of NGN 100,000
     * over 3 months is NGN 5,000. Computed once at disbursement from the
     * amount actually disbursed, then frozen on the loan.
     */
    public function interest(int $principal, int $rateBps): int
    {
        if ($principal <= 0 || $rateBps <= 0) {
            return 0;
        }

        return intdiv($principal * $rateBps, 10000);
    }

    public function totalRepayable(int $principal, int $rateBps): int
    {
        return $principal + $this->interest($principal, $rateBps);
    }

    /**
     * What is still owed. Only confirmed repayments may be passed in — the
     * caller is responsible for having filtered them, and Loan::confirmedRepaid()
     * is the only thing that should be doing so.
     */
    public function outstanding(int $totalRepayable, int $penaltyAmount, int $confirmedRepaid): int
    {
        return max(0, $totalRepayable + $penaltyAmount - $confirmedRepaid);
    }

    /**
     * The payback deadline, counted from the date the disburser entered — not
     * from now(). Uses no-overflow month arithmetic so a loan disbursed on
     * 31 January over one month falls due on 28/29 February rather than
     * skipping into March.
     */
    public function dueDate(CarbonInterface $disbursedOn, int $tenureMonths): Carbon
    {
        return Carbon::parse($disbursedOn)->startOfDay()->addMonthsNoOverflow($tenureMonths);
    }

    public function graceEndsAt(CarbonInterface $dueAt, int $graceDays): Carbon
    {
        return Carbon::parse($dueAt)->startOfDay()->addDays($graceDays);
    }

    /**
     * A penalty a fund may assign by hand. Off by default — these loans
     * already carry interest — so this is only reached when an officer opts in.
     */
    public function penalty(int $outstanding, ?int $flatAmount, ?int $rateBps): int
    {
        $penalty = (int) $flatAmount;

        if ($rateBps !== null && $rateBps > 0 && $outstanding > 0) {
            $penalty += intdiv($outstanding * $rateBps, 10000);
        }

        return max(0, $penalty);
    }
}
