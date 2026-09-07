<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

/**
 * Two things must both hold for an officer action: the spatie permission, and
 * an office in *this loan's own fund*. The pivot is the authority — as with
 * set coordinators, a role that failed to sync must never lock an officer out,
 * and a role that was granted too broadly must never let someone act on
 * another set's money.
 *
 * Super admins bypass the pivot, and only super admins do.
 */
class LoanPolicy
{
    public function view(User $user, Loan $loan): bool
    {
        if ($loan->user_id === $user->id) {
            return true;
        }

        // A guarantor sees the loan they are standing for.
        if ($loan->guarantors()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $this->isOfficer($user, $loan);
    }

    public function update(User $user, Loan $loan): bool
    {
        return $loan->user_id === $user->id
            && in_array($loan->status, ['draft', 'changes_requested'], true);
    }

    public function submit(User $user, Loan $loan): bool
    {
        return $this->update($user, $loan);
    }

    public function withdraw(User $user, Loan $loan): bool
    {
        return $loan->user_id === $user->id && $loan->isPreDisbursement();
    }

    public function review(User $user, Loan $loan): bool
    {
        return $this->officerWith($user, $loan, 'review loans', 'reviewer');
    }

    public function disburse(User $user, Loan $loan): bool
    {
        return $this->officerWith($user, $loan, 'disburse loans', 'disburser');
    }

    /** Confirming a repayment is the treasurer's job; a disburser may also do it. */
    public function confirmRepayment(User $user, Loan $loan): bool
    {
        return $this->officerWith($user, $loan, 'manage loans', 'treasurer')
            || $this->officerWith($user, $loan, 'disburse loans', 'disburser');
    }

    /**
     * The borrower, anyone who accepted guarantorship, and the fund's own
     * officers. A guarantor paying on a defaulting borrower's behalf is the
     * point of guarantorship, so they must be able to put money in without
     * anyone's permission.
     */
    public function recordRepayment(User $user, Loan $loan): bool
    {
        if ($loan->user_id === $user->id) {
            return true;
        }

        if ($loan->guarantors()->where('user_id', $user->id)->where('status', 'accepted')->exists()) {
            return true;
        }

        return $this->confirmRepayment($user, $loan);
    }

    public function manage(User $user, Loan $loan): bool
    {
        return $user->hasRole('super_admin')
            || ($user->can('manage loans') && $this->isOfficer($user, $loan));
    }

    /**
     * Publishing a defaulter is the one action no fund officer holds by virtue
     * of office: it needs the platform-wide permission, which is granted to
     * super_admin alone.
     */
    public function publishDefault(User $user, Loan $loan): bool
    {
        return $user->can('publish loan defaults');
    }

    protected function isOfficer(User $user, Loan $loan): bool
    {
        return $user->hasRole('super_admin')
            || $user->isLoanOfficer($loan->program);
    }

    protected function officerWith(User $user, Loan $loan, string $permission, string $office): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can($permission) && $user->isLoanOfficer($loan->program, $office);
    }
}
