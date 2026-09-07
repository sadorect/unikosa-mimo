<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanProgram;
use App\Models\User;

/**
 * Why a member may not apply right now, in words the application page can
 * show them. Returning reasons rather than a bare boolean is the point: a
 * welfare fund that silently refuses an application generates a phone call to
 * a set officer, not a support ticket.
 */
class LoanEligibilityService
{
    /** @return string[] Empty when the member may apply. */
    public function reasons(User $user, LoanProgram $program, ?int $amount = null): array
    {
        $reasons = [];

        if (! $program->isActive()) {
            $reasons[] = 'This fund is not currently accepting applications.';
        }

        if ($user->status !== 'approved') {
            $reasons[] = 'Your membership is still awaiting approval.';
        }

        if ((int) $user->graduating_set_id !== (int) $program->set_id) {
            $reasons[] = 'This fund is only open to members of '.($program->set?->name ?? 'its own set').'.';
        }

        if ($program->currentTerm() === null) {
            $reasons[] = 'This fund has not yet published its terms of operation.';
        }

        if ($program->min_member_tenure_days) {
            $daysOnPlatform = $user->created_at?->diffInDays(now()) ?? 0;

            if ($daysOnPlatform < $program->min_member_tenure_days) {
                $reasons[] = "Accounts must be at least {$program->min_member_tenure_days} days old to apply.";
            }
        }

        $activeLoans = Loan::where('loan_program_id', $program->id)
            ->where('user_id', $user->id)
            ->whereNotIn('status', config('loans.terminal_statuses'))
            ->count();

        if ($activeLoans >= $program->max_active_loans_per_member) {
            $reasons[] = $program->max_active_loans_per_member === 1
                ? 'You already have a loan in progress with this fund.'
                : "You already have {$activeLoans} loans in progress with this fund.";
        }

        // A written-off loan, or a default currently on public record, closes
        // the fund to that member until the association decides otherwise.
        if (Loan::where('loan_program_id', $program->id)->where('user_id', $user->id)->whereNotNull('written_off_at')->exists()) {
            $reasons[] = 'A previous loan of yours was written off. Please speak to the welfare committee.';
        }

        $published = Loan::where('loan_program_id', $program->id)
            ->where('user_id', $user->id)
            ->whereHas('publications', fn ($query) => $query->active())
            ->exists();

        if ($published) {
            $reasons[] = 'You have a default notice currently published. Clear the balance to apply again.';
        }

        if ($amount !== null) {
            $reasons = array_merge($reasons, $this->amountReasons($program, $amount));
        }

        return array_values(array_unique($reasons));
    }

    public function passes(User $user, LoanProgram $program, ?int $amount = null): bool
    {
        return $this->reasons($user, $program, $amount) === [];
    }

    /**
     * Re-checking the amount on an application that already exists. Its own
     * commitment is added back to the headroom first, or a member editing a
     * sent-back application would be told the fund cannot afford the loan it
     * has already set aside for them.
     *
     * @return string[]
     */
    public function amountBlockersFor(Loan $loan, int $amount): array
    {
        $ownCommitment = (int) ($loan->amount_approved ?? $loan->amount_requested);

        return $this->amountReasons($loan->program, $amount, $ownCommitment);
    }

    /** @return string[] */
    protected function amountReasons(LoanProgram $program, int $amount, int $extraHeadroom = 0): array
    {
        $reasons = [];
        $currency = $program->currency;

        if ($amount < $program->min_loan_amount) {
            $reasons[] = "The smallest loan this fund makes is {$currency} ".number_format($program->min_loan_amount).'.';
        }

        if ($program->max_loan_amount !== null && $amount > $program->max_loan_amount) {
            $reasons[] = "The largest loan this fund makes is {$currency} ".number_format($program->max_loan_amount).'.';
        }

        $headroom = $program->availableHeadroom() + $extraHeadroom;

        if ($amount > $headroom) {
            $reasons[] = $headroom > 0
                ? "The fund has only {$currency} ".number_format($headroom).' left uncommitted at the moment.'
                : 'The fund is fully committed at the moment.';
        }

        return $reasons;
    }
}
