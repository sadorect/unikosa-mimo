<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Support\Eligibility\EligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function __construct(
        protected EligibilityService $eligibilityService,
    ) {}

    /**
     * Doubles as the resubmission path: if the member already has a 'pending' candidacy
     * for this position (either freshly submitted, or reset to pending by a moderator's
     * "Request Changes" action along with feedback), this updates it in place rather than
     * blocking. Approved/rejected/withdrawn candidacies are terminal — no resubmission.
     */
    public function store(Request $request, Election $election, Position $position)
    {
        abort_unless($position->election_id === $election->id, 404);
        abort_unless($election->isNominationWindowOpen(), 403, 'Nominations are not currently open for this election.');

        $user = $request->user();

        abort_unless($this->eligibilityService->passes($user, $position, 'candidacy'), 403, 'You are not eligible to run for this position.');

        $existing = Candidate::where('position_id', $position->id)->where('user_id', $user->id)->first();
        abort_if($existing && $existing->status !== 'pending', 409, 'You cannot resubmit for this position.');

        // A member may only hold one active candidacy per election — running for a second
        // office requires withdrawing (or having been rejected from) the first.
        $activeElsewhereInElection = Candidate::where('user_id', $user->id)
            ->where('position_id', '!=', $position->id)
            ->whereIn('position_id', $election->positions()->pluck('id'))
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
        abort_if($activeElsewhereInElection, 409, 'You already have an active nomination for another position in this election. Withdraw it first if you want to run for a different position.');

        $validated = $request->validate([
            'manifesto' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            $disk = config('filesystems.media_disk');
            $path = $request->file('photo')->store('candidates', $disk);
            abort_if(! $path, 500, 'Failed to save your photo. Please try again.');
            $validated['photo'] = Storage::disk($disk)->url($path);
        }

        if ($existing) {
            $existing->update([
                'manifesto' => $validated['manifesto'] ?? $existing->manifesto,
                'photo' => $validated['photo'] ?? $existing->photo,
                'feedback' => null,
            ]);

            return back()->with('success', 'Your nomination has been resubmitted for review.');
        }

        Candidate::create([
            'position_id' => $position->id,
            'user_id' => $user->id,
            'manifesto' => $validated['manifesto'] ?? null,
            'photo' => $validated['photo'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Your nomination has been submitted for review.');
    }

    public function withdraw(Request $request, Candidate $candidate)
    {
        abort_unless($candidate->user_id === $request->user()->id, 403);
        abort_unless($candidate->position->election->status === 'nominations_open', 403, 'Nominations have already closed for this election.');

        $candidate->update(['status' => 'withdrawn', 'withdrawn_at' => now()]);

        return back()->with('success', 'Your nomination has been withdrawn.');
    }
}
