<?php

namespace App\Services;

use App\Models\Position;
use App\Models\Vote;

class ElectionResultService
{
    /**
     * Full result for a position: the per-candidate tally, abstain count, total, and the
     * winner (honoring an optional threshold) — with the underlying vote query run only
     * once. Prefer this over calling tally() and winner() separately (which would query
     * twice). Computed live from the immutable, anonymous votes table; once voting closes
     * those rows never change, so the same input always yields the same result.
     *
     * @return array{tallies: \Illuminate\Support\Collection, abstentions: int, total: int, winner: ?array}
     */
    public function resultsFor(Position $position): array
    {
        $result = $this->tally($position);
        $result['winner'] = $this->winnerFromTally($position, $result['tallies']);

        return $result;
    }

    /**
     * Per-candidate vote counts for a position, plus the abstain count.
     *
     * @return array{tallies: \Illuminate\Support\Collection, abstentions: int, total: int}
     */
    public function tally(Position $position): array
    {
        $tallies = Vote::where('position_id', $position->id)
            ->where('is_abstention', false)
            ->selectRaw('candidate_id, count(*) as votes')
            ->groupBy('candidate_id')
            ->with('candidate.user:id,name')
            ->orderByDesc('votes')
            ->get();

        $abstentions = Vote::where('position_id', $position->id)
            ->where('is_abstention', true)
            ->count();

        $totalCandidateVotes = $tallies->sum('votes');

        $tallies = $tallies->map(fn ($row) => [
            'candidate' => $row->candidate,
            'votes' => $row->votes,
            'percent' => $totalCandidateVotes > 0 ? round(($row->votes / $totalCandidateVotes) * 100, 2) : 0.0,
        ]);

        return [
            'tallies' => $tallies,
            'abstentions' => $abstentions,
            'total' => $totalCandidateVotes + $abstentions,
        ];
    }

    /**
     * The leading candidate for a position, honoring an optional winning_threshold_percent.
     * Returns null if there were no votes, or if a threshold is set and nobody cleared it
     * (a "no winner — threshold not met" outcome) rather than silently returning the
     * plurality leader in that case.
     */
    public function winner(Position $position): ?array
    {
        return $this->winnerFromTally($position, $this->tally($position)['tallies']);
    }

    protected function winnerFromTally(Position $position, \Illuminate\Support\Collection $tallies): ?array
    {
        $leader = $tallies->first();

        if (! $leader) {
            return null;
        }

        if ($position->winning_threshold_percent !== null && $leader['percent'] < (float) $position->winning_threshold_percent) {
            return null;
        }

        return $leader;
    }
}
