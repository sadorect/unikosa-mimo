<?php

namespace App\Jobs;

use App\Models\LoanDefaultPublication;
use App\Services\LoanPublicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Takes down notices that have run their course. Every notice carries its own
 * expiry, so a default nobody ever revisits still stops being published.
 */
class ExpireLoanPublications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(LoanPublicationService $publications): void
    {
        LoanDefaultPublication::query()
            ->whereNull('taken_down_at')
            ->where('expires_at', '<=', now())
            ->with('forumPost')
            ->chunkById(200, function ($expired) use ($publications) {
                foreach ($expired as $publication) {
                    $publications->takedown($publication, null, 'The notice reached its expiry date.');
                }
            });
    }
}
