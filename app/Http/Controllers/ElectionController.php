<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\ElectionVoterReceipt;
use App\Models\Vote;
use App\Services\ElectionResultService;
use App\Support\Eligibility\EligibilityService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ElectionController extends Controller
{
    public function __construct(
        protected EligibilityService $eligibilityService,
        protected ElectionResultService $resultService,
    ) {}

    public function index()
    {
        return Inertia::render('Elections/Index', [
            'elections' => Election::where('status', '!=', 'draft')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function show(Election $election)
    {
        $election->load(['positions' => fn ($q) => $q->orderBy('display_order')]);
        $election->positions->load('approvedCandidates.user:id,name,avatar');

        $user = auth()->user();

        $hasVoted = ElectionVoterReceipt::where('election_id', $election->id)
            ->where('user_id', $user->id)
            ->exists();

        $canVote = $election->isVotingWindowOpen() && $this->eligibilityService->passes($user, $election, 'voting');

        $myCandidacies = Candidate::where('user_id', $user->id)
            ->whereIn('position_id', $election->positions->pluck('id'))
            ->get()
            ->keyBy('position_id');

        // A member may only hold one active candidacy per election at a time — running for
        // a second office requires withdrawing (or being rejected from) the first.
        $hasActiveCandidacyElsewhere = fn (int $positionId) => $myCandidacies
            ->except($positionId)
            ->whereIn('status', ['pending', 'approved'])
            ->isNotEmpty();

        $nominablePositions = [];
        foreach ($election->positions as $position) {
            $existingCandidacy = $myCandidacies->get($position->id);

            // Nominable if the member has no candidacy yet (fresh apply), or their existing
            // one is pending *with* moderator feedback (a "changes requested" resubmission).
            // A freshly-submitted pending candidacy with no feedback yet is already in the
            // queue — no form to show, just a "pending review" status.
            $canApplyOrResubmit = ! $existingCandidacy
                || ($existingCandidacy->status === 'pending' && $existingCandidacy->feedback !== null);

            $nominablePositions[$position->id] = $election->isNominationWindowOpen()
                && $canApplyOrResubmit
                && ! $hasActiveCandidacyElsewhere($position->id)
                && $this->eligibilityService->passes($user, $position, 'candidacy');
        }

        $activeCandidacy = $myCandidacies->whereIn('status', ['pending', 'approved'])->first();
        $activeCandidacyPositionTitle = $activeCandidacy
            ? $election->positions->firstWhere('id', $activeCandidacy->position_id)?->title
            : null;

        return Inertia::render('Elections/Show', [
            'election' => $election,
            'hasVoted' => $hasVoted,
            'canVote' => $canVote,
            'nominablePositions' => $nominablePositions,
            'activeCandidacyPositionTitle' => $activeCandidacyPositionTitle,
            'myCandidacies' => $myCandidacies->map(fn (Candidate $c) => [
                'id' => $c->id,
                'position_id' => $c->position_id,
                'status' => $c->status,
                'feedback' => $c->feedback,
            ]),
        ]);
    }

    public function vote(Request $request, Election $election)
    {
        $user = $request->user();

        abort_unless($election->isVotingWindowOpen(), 403, 'Voting is not currently open for this election.');
        abort_unless($this->eligibilityService->passes($user, $election, 'voting'), 403, 'You are not eligible to vote in this election.');

        $request->validate(['selections' => 'required|array']);

        // Cheap common-case guard (a genuine race is still caught by the unique constraint below).
        abort_if(
            ElectionVoterReceipt::where('election_id', $election->id)->where('user_id', $user->id)->exists(),
            409,
            'You have already voted in this election.'
        );

        $ballot = $this->buildBallot($election, $request->input('selections'));

        try {
            DB::transaction(function () use ($election, $user, $ballot) {
                // The unique(election_id, user_id) constraint on the receipt is the real
                // guarantee against a double-vote race: if two concurrent submissions get
                // this far, the second receipt insert violates it and the whole transaction
                // (including that attempt's votes) rolls back, surfacing as the 409 below.
                foreach ($ballot as $row) {
                    Vote::create([
                        'position_id' => $row['position_id'],
                        'candidate_id' => $row['candidate_id'],
                        'is_abstention' => $row['is_abstention'],
                    ]);
                }

                ElectionVoterReceipt::create([
                    'election_id' => $election->id,
                    'user_id' => $user->id,
                    'voted_at' => now(),
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                abort(409, 'You have already voted in this election.');
            }
            throw $e;
        }

        return back()->with('success', 'Your vote has been recorded.');
    }

    public function results(Election $election)
    {
        abort_unless($election->results_published_at !== null, 404);

        $election->load(['positions' => fn ($q) => $q->orderBy('display_order')]);

        $results = $election->positions->map(fn ($position) => [
            'position' => $position,
            ...$this->resultService->resultsFor($position),
        ]);

        return Inertia::render('Elections/Results', [
            'election' => $election,
            'results' => $results,
        ]);
    }

    /**
     * Validate and normalize the submitted ballot server-side — every position the
     * election currently has must have an explicit decision (a specific approved,
     * non-withdrawn candidate, or the literal 'abstain'). Never trust the client's
     * notion of which positions/candidates exist.
     *
     * @return array<int, array{position_id:int, candidate_id:?int, is_abstention:bool}>
     */
    protected function buildBallot(Election $election, array $selections): array
    {
        $positions = $election->positions()->with('approvedCandidates')->get();
        abort_if($positions->isEmpty(), 422, 'This election has no positions to vote on.');

        $ballot = [];

        foreach ($positions as $position) {
            $choice = $selections[$position->id] ?? null;
            abort_if($choice === null || $choice === '', 422, "Missing a decision for {$position->title}.");

            if ($choice === 'abstain') {
                $ballot[] = ['position_id' => $position->id, 'candidate_id' => null, 'is_abstention' => true];
                continue;
            }

            $candidate = $position->approvedCandidates->firstWhere('id', (int) $choice);
            abort_unless($candidate, 422, "Invalid candidate selection for {$position->title}.");

            $ballot[] = ['position_id' => $position->id, 'candidate_id' => $candidate->id, 'is_abstention' => false];
        }

        return $ballot;
    }

    protected function isUniqueViolation(QueryException $e): bool
    {
        // Postgres unique_violation SQLSTATE; the DB constraint (not this check) is the
        // actual guarantee against a double-vote race — this only turns it into a clean 409.
        return $e->getCode() === '23505';
    }
}
