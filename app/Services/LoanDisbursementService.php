<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * The hand-off from the committee to whoever actually moves the money, and
 * the point at which the payback clock starts.
 *
 * Money never touches the platform: the fund's disburser makes a bank
 * transfer and records it here. That is why the disbursement date is entered
 * rather than taken from now() — the transfer may well have been yesterday,
 * and every downstream date is counted from it.
 */
class LoanDisbursementService
{
    public function __construct(
        protected LoanStateMachine $stateMachine,
        protected LoanCalculator $calculator,
    ) {}

    /**
     * A deliberate human step, per the workflow: approval and guarantors being
     * in place do not release money on their own.
     */
    public function release(Loan $loan, User $reviewer, ?User $disburser = null): void
    {
        if ($loan->status !== 'guarantors_secured') {
            throw new RuntimeException('This loan is not ready to be released.');
        }

        $disburser ??= $loan->program->disbursers()->first();

        if (! $disburser) {
            throw new RuntimeException('This fund has no disburser appointed. Add one on the fund before releasing a loan.');
        }

        $this->stateMachine->transition($loan, 'disburser_notified', $reviewer, [
            'attributes' => [
                'disburser_id' => $disburser->id,
                'disburser_notified_at' => now(),
            ],
        ]);
    }

    /** The explicit receipt-of-notice step. */
    public function acknowledge(Loan $loan, User $disburser): void
    {
        if ($loan->status !== 'disburser_notified') {
            throw new RuntimeException('There is no disbursement notice to acknowledge.');
        }

        $this->stateMachine->transition($loan, 'disburser_acknowledged', $disburser, [
            'attributes' => [
                'disburser_id' => $loan->disburser_id ?? $disburser->id,
                'disburser_acknowledged_at' => now(),
            ],
        ]);
    }

    /**
     * Record the transfer. Interest is computed once from the amount actually
     * disbursed and then frozen: a ledger that keeps recalculating cannot be
     * reconciled against a bank statement.
     */
    public function recordDisbursement(Loan $loan, User $disburser, array $data): void
    {
        if ($loan->status !== 'disburser_acknowledged') {
            throw new RuntimeException('Acknowledge the disbursement notice before recording the transfer.');
        }

        $amount = (int) $data['disbursed_amount'];

        if ($amount < 1) {
            throw new RuntimeException('The disbursed amount must be a positive figure.');
        }

        if ($amount > (int) $loan->amount_approved) {
            throw new RuntimeException('You cannot disburse more than the committee approved.');
        }

        $disbursedAt = Carbon::parse($data['disbursed_at'])->startOfDay();

        if ($disbursedAt->isFuture()) {
            throw new RuntimeException('The disbursement date cannot be in the future.');
        }

        $interest = $this->calculator->interest($amount, (int) $loan->interest_rate_bps);
        $dueAt = $this->calculator->dueDate($disbursedAt, (int) $loan->tenure_months);

        $this->stateMachine->transition($loan, 'disbursed', $disburser, [
            'attributes' => [
                'disbursed_amount' => $amount,
                'disbursed_at' => $disbursedAt,
                'disbursement_method' => $data['disbursement_method'] ?? 'bank_transfer',
                'disbursement_reference' => $data['disbursement_reference'] ?? null,
                'disbursement_proof_path' => $data['disbursement_proof_path'] ?? null,
                'disbursed_by' => $disburser->id,
                'interest_amount' => $interest,
                'total_repayable' => $amount + $interest,
                'due_at' => $dueAt,
                'grace_ends_at' => $this->calculator->graceEndsAt($dueAt, (int) $loan->program->grace_days),
            ],
        ]);
    }
}
