<?php

namespace App\Jobs;

use App\Mail\ElectionResultsPublished;
use App\Models\Election;
use App\Models\UnikosaNotification;
use App\Models\User;
use App\Services\ElectionResultService;
use App\Support\Eligibility\EligibilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class PublishElectionResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: notification delivery is not safely resumable mid-run, and the
    // atomic claim below already prevents double-clicks from double-sending. A genuine
    // infra failure is better surfaced (via a failed job) than silently re-blasting
    // every voter with duplicate result emails on retry.
    public int $tries = 1;

    public function __construct(
        public Election $election,
    ) {}

    public function handle(EligibilityService $eligibilityService, ElectionResultService $resultService): void
    {
        // Atomically claim the election: only the first run for which results_notified_at
        // is still null proceeds. A concurrent dispatch (double-click) or a re-dispatch
        // gets 0 affected rows and returns without sending anything.
        $claimed = Election::whereKey($this->election->getKey())
            ->whereNull('results_notified_at')
            ->update(['results_notified_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $election = $this->election->fresh(['positions']);

        $summaryLines = $election->positions->map(function ($position) use ($resultService) {
            $winner = $resultService->resultsFor($position)['winner'];
            $winnerLabel = $winner
                ? ($winner['candidate']?->user?->name ?? 'Unknown') . " ({$winner['percent']}%)"
                : 'No winner — threshold not met';

            return "{$position->title}: {$winnerLabel}";
        })->all();

        $now = now();

        // Stream approved members in chunks (avoids loading the whole member base into
        // memory), eager-loading the relations the eligibility rules read so passes()
        // doesn't lazy-load per user. Rule sets themselves are memoized inside
        // EligibilityService for the life of this job instance.
        User::where('status', 'approved')
            ->with(['graduatingSet', 'roles'])
            ->chunkById(500, function ($members) use ($eligibilityService, $election, $summaryLines, $now) {
                $eligible = $members->filter(
                    fn (User $user) => $eligibilityService->passes($user, $election, 'voting')
                );

                if ($eligible->isEmpty()) {
                    return;
                }

                UnikosaNotification::insert($eligible->map(fn (User $user) => [
                    'user_id' => $user->id,
                    'type' => 'election_results_published',
                    'data' => json_encode([
                        'type' => 'election_results_published',
                        'message' => "Results are in for \"{$election->title}\".",
                        'url' => route('elections.results', $election),
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());

                foreach ($eligible as $member) {
                    Mail::to($member->email)->queue(new ElectionResultsPublished($election, $summaryLines));
                }
            });
    }
}
