<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use App\Notifications\LoanRepaymentConfirmed;
use App\Notifications\LoanRepaymentRecorded;
use App\Notifications\LoanRepaymentRejected;
use App\Notifications\LoanSettled;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Repayments are declared by whoever paid and confirmed by the fund.
 *
 * Only confirmed rows move the balance, which is the whole point: a borrower
 * cannot clear their own default by typing in a payment nobody received.
 *
 * A guarantor may pay on a defaulting borrower's behalf — that is what
 * standing for someone means, and the fund needs the money more than it needs
 * the principle. Their payment reduces the same balance and can settle the
 * loan outright; the ledger records whose money it was so the borrower's own
 * repayment history stays honest.
 */
class LoanRepaymentService
{
    public function __construct(
        protected LoanStateMachine $stateMachine,
        protected LoanNotifier $notifier,
    ) {}

    /** States in which a loan can still take money. */
    public function acceptsRepayments(Loan $loan): bool
    {
        return in_array($loan->status, ['disbursed', 'overdue', 'in_grace', 'defaulted'], true);
    }

    /**
     * What this user is to this loan: 'borrower', 'guarantor', 'officer', or
     * null if they have no business paying into it.
     */
    public function payerRole(Loan $loan, User $user): ?string
    {
        if ($loan->user_id === $user->id) {
            return 'borrower';
        }

        if ($loan->guarantors()->where('user_id', $user->id)->where('status', 'accepted')->exists()) {
            return 'guarantor';
        }

        if ($user->can('confirmRepayment', $loan)) {
            return 'officer';
        }

        return null;
    }

    public function record(Loan $loan, User $actor, array $data): LoanRepayment
    {
        if (! $this->acceptsRepayments($loan)) {
            throw new RuntimeException('This loan is not taking repayments.');
        }

        $role = $this->payerRole($loan, $actor);

        if ($role === null) {
            throw new RuntimeException('You are not party to this loan.');
        }

        $amount = (int) $data['amount'];

        if ($amount < 1) {
            throw new RuntimeException('Enter the amount you paid.');
        }

        $paidOn = Carbon::parse($data['paid_on'])->startOfDay();

        if ($paidOn->isFuture()) {
            throw new RuntimeException('The payment date cannot be in the future.');
        }

        if ($loan->disbursed_at && $paidOn->lt($loan->disbursed_at)) {
            throw new RuntimeException('The payment date cannot be before the loan was disbursed.');
        }

        $repayment = LoanRepayment::create([
            'loan_id' => $loan->id,
            // An officer typing in a handover is recording the borrower's own
            // money; a guarantor paying is recording their own.
            'paid_by_user_id' => $role === 'officer' ? $loan->user_id : $actor->id,
            'payer_role' => $role === 'officer' ? 'borrower' : $role,
            'amount' => $amount,
            'paid_on' => $paidOn,
            'method' => $data['method'] ?? 'bank_transfer',
            'reference' => $data['reference'] ?? null,
            'proof_path' => $data['proof_path'] ?? null,
            'note' => $data['note'] ?? null,
            'recorded_by' => $actor->id,
            'status' => 'pending_confirmation',
        ]);

        foreach ($this->confirmers($loan) as $officer) {
            $this->notifier->send(
                $officer,
                new LoanRepaymentRecorded($loan, $repayment),
                'loan_repayment_recorded',
                ($role === 'guarantor'
                    ? "{$actor->name} has paid {$loan->currency} ".number_format($amount)." towards {$loan->applicant->name}'s loan {$loan->reference}."
                    : "{$actor->name} has declared a {$loan->currency} ".number_format($amount)." repayment on loan {$loan->reference}.")
                    .' It needs confirming.',
                route('filament.admin.resources.loan-repayments.index')
            );
        }

        return $repayment;
    }

    /**
     * A treasurer says the money arrived. Only now does the balance move, and
     * only now can a loan settle.
     */
    public function confirm(LoanRepayment $repayment, User $officer): void
    {
        if ($repayment->status !== 'pending_confirmation') {
            throw new RuntimeException('This repayment has already been dealt with.');
        }

        $loan = $repayment->loan;

        DB::transaction(function () use ($repayment, $officer, $loan) {
            $repayment->update([
                'status' => 'confirmed',
                'confirmed_by' => $officer->id,
                'confirmed_at' => now(),
                'rejection_reason' => null,
            ]);

            // A cache for display only; the balance is always recomputed from
            // the confirmed rows themselves.
            $loan->update(['amount_repaid' => $loan->fresh()->confirmedRepaid()]);
        });

        $loan = $loan->fresh();

        foreach ($this->interestedIn($repayment, $loan) as $recipient) {
            $this->notifier->send(
                $recipient,
                new LoanRepaymentConfirmed($loan, $repayment->fresh()),
                'loan_repayment_confirmed',
                "A {$loan->currency} ".number_format($repayment->amount)." repayment on loan {$loan->reference} has been confirmed. Outstanding: {$loan->currency} ".number_format($loan->outstanding_amount).'.',
                route('loans.show', $loan)
            );
        }

        $this->settleIfCleared($loan, $officer);
    }

    public function reject(LoanRepayment $repayment, User $officer, string $reason): void
    {
        if ($repayment->status !== 'pending_confirmation') {
            throw new RuntimeException('This repayment has already been dealt with.');
        }

        $repayment->update([
            'status' => 'rejected',
            'confirmed_by' => $officer->id,
            'confirmed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $loan = $repayment->loan;

        $this->notifier->send(
            $repayment->payer ?? $loan->applicant,
            new LoanRepaymentRejected($loan, $repayment->fresh()),
            'loan_repayment_rejected',
            "A repayment you recorded on loan {$loan->reference} could not be confirmed.",
            route('loans.show', $loan)
        );
    }

    /**
     * The cure path. Whoever cleared the balance — borrower or guarantor —
     * the loan closes and everyone standing on it is told.
     */
    public function settleIfCleared(Loan $loan, ?User $actor = null): bool
    {
        if (! $this->acceptsRepayments($loan) || $loan->outstanding_amount > 0) {
            return false;
        }

        $this->stateMachine->transition($loan, 'settled', $actor, [
            'silent' => true,
            'attributes' => ['settled_at' => now(), 'amount_repaid' => $loan->confirmedRepaid()],
        ]);

        $loan = $loan->fresh();

        foreach ($this->notifier->applicantAndGuarantors($loan) as $recipient) {
            $this->notifier->send(
                $recipient,
                new LoanSettled($loan),
                'loan_settled',
                $recipient->id === $loan->user_id
                    ? "Your loan {$loan->reference} is fully repaid and now closed."
                    : "Loan {$loan->reference}, which you guaranteed, is fully repaid. Your obligation has ended.",
                route('loans.show', $loan)
            );
        }

        return true;
    }

    /** Officers who may confirm money into this fund. */
    public function confirmers(Loan $loan): iterable
    {
        return $loan->program->officers()
            ->wherePivotIn('role', ['treasurer', 'disburser'])
            ->get()
            ->unique('id');
    }

    /**
     * Who hears that a repayment was confirmed: the borrower always, and the
     * guarantor who put the money up, when it was not the borrower's.
     */
    protected function interestedIn(LoanRepayment $repayment, Loan $loan): iterable
    {
        return collect([$loan->applicant, $repayment->payer])
            ->filter()
            ->unique('id');
    }
}
