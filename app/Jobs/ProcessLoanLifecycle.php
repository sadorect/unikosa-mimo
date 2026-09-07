<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Services\LoanStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * The part of the scheme that runs without anybody clicking: loans falling
 * overdue, guarantors being nudged, and the grace window closing into a
 * default.
 *
 * Every move is claimed atomically through
 * LoanStateMachine::transitionIfCurrently(), so a double dispatch cannot
 * notify a borrower's guarantors twice or default the same loan twice.
 */
class ProcessLoanLifecycle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: these transitions notify people, and a retry after a
    // partial run would chase them again. The atomic claim makes a manual
    // re-run safe instead.
    public int $tries = 1;

    public function handle(LoanStateMachine $machine): void
    {
        $this->markOverdue($machine);
        $this->markDefaulted($machine);
    }

    /**
     * Past the due date with a balance owing. The loan goes overdue and
     * straight into its grace window in one pass, so the timeline reads the
     * way the fund's rules do: the clock to default starts the day the loan
     * is late, not whenever a job next happens to run.
     */
    protected function markOverdue(LoanStateMachine $machine): void
    {
        $today = now()->startOfDay();

        Loan::query()
            ->where('status', 'disbursed')
            ->whereNotNull('due_at')
            ->where('due_at', '<', $today)
            ->with(['program', 'applicant'])
            ->chunkById(200, function ($loans) use ($machine) {
                foreach ($loans as $loan) {
                    if ($loan->outstanding_amount <= 0) {
                        continue;
                    }

                    if (! $machine->transitionIfCurrently($loan, 'disbursed', 'overdue')) {
                        continue;
                    }

                    // Only now are the guarantors told, and only once: the
                    // nudge hangs off entering the grace window.
                    $machine->transitionIfCurrently($loan->refresh(), 'overdue', 'in_grace', null, [
                        'attributes' => ['guarantors_nudged_at' => now()],
                    ]);
                }
            });
    }

    /** Grace window closed and still owing. */
    protected function markDefaulted(LoanStateMachine $machine): void
    {
        $today = now()->startOfDay();

        Loan::query()
            ->where('status', 'in_grace')
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<', $today)
            ->with(['program', 'applicant'])
            ->chunkById(200, function ($loans) use ($machine) {
                foreach ($loans as $loan) {
                    if ($loan->outstanding_amount <= 0) {
                        continue;
                    }

                    $machine->transitionIfCurrently($loan, 'in_grace', 'defaulted', null, [
                        'attributes' => ['defaulted_at' => now()],
                    ]);
                }
            });
    }
}
