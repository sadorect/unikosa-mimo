<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Services\LoanPublicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Posts the set-forum notice when a loan defaults.
 *
 * Runs out of band from the transition that caused it, so a forum failure can
 * never roll back the default itself. Publishing is idempotent — the service
 * returns the existing live notice rather than posting a second one — so a
 * re-dispatch is safe.
 */
class PublishLoanDefaultToForum implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Loan $loan) {}

    public function handle(LoanPublicationService $publications): void
    {
        $loan = $this->loan->fresh();

        // The balance may have been cleared between the default and this job.
        if (! $loan || $loan->status !== 'defaulted' || $loan->outstanding_amount <= 0) {
            return;
        }

        // Belt as well as braces: the default has already been recorded and
        // everyone told. Whatever goes wrong with the forum, it must not
        // resurface as a failure of the transition that got us here.
        try {
            $publication = $publications->publishToSetForum($loan);
        } catch (Throwable $exception) {
            Log::error('Loan default notice could not be published.', [
                'loan_id' => $loan->id,
                'reference' => $loan->reference,
                'exception' => $exception->getMessage(),
            ]);

            return;
        }

        if ($publication && $publication->wasRecentlyCreated) {
            $publications->announce($loan, $publication);
        }
    }
}
