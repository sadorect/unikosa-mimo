<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanGuarantor;
use App\Models\User;
use App\Services\LoanGuarantorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

class LoanGuarantorController extends Controller
{
    public function __construct(protected LoanGuarantorService $guarantors) {}

    /** The invitations addressed to the signed-in member. */
    public function index(Request $request)
    {
        $requests = $request->user()->guarantorships()
            ->with(['loan.applicant', 'loan.program'])
            ->latest()
            ->get()
            ->map(fn (LoanGuarantor $guarantor) => [
                'id' => $guarantor->id,
                'status' => $guarantor->status,
                'invited_at' => $guarantor->invited_at,
                'expires_at' => $guarantor->expires_at,
                'expired' => $guarantor->hasExpired() && $guarantor->status === 'invited',
                'loan' => [
                    'id' => $guarantor->loan->id,
                    'reference' => $guarantor->loan->reference,
                    'status' => $guarantor->loan->status,
                    'currency' => $guarantor->loan->currency,
                    'amount' => $guarantor->loan->amount_approved ?? $guarantor->loan->amount_requested,
                    'purpose' => $guarantor->loan->purpose,
                    'tenure_months' => $guarantor->loan->tenure_months,
                    'due_at' => $guarantor->loan->due_at,
                    'outstanding' => $guarantor->loan->outstanding_amount,
                    'applicant' => $guarantor->loan->applicant->name,
                    'program' => $guarantor->loan->program->name,
                ],
            ]);

        return Inertia::render('Loans/Guarantorships', ['requests' => $requests]);
    }

    /** Members of the set the applicant could still ask. */
    public function candidates(Request $request, Loan $loan)
    {
        abort_unless($loan->user_id === $request->user()->id, 403);

        return response()->json([
            'candidates' => $this->guarantors->candidates($loan, $request->string('q')->toString() ?: null),
        ]);
    }

    public function store(Request $request, Loan $loan)
    {
        abort_unless($loan->user_id === $request->user()->id, 403, 'Only the applicant can nominate guarantors.');

        $validated = $request->validate(['user_id' => 'required|integer|exists:users,id']);

        $candidate = User::findOrFail($validated['user_id']);

        try {
            $this->guarantors->invite($loan, $candidate, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['user_id' => $exception->getMessage()]);
        }

        return back()->with('success', "{$candidate->name} has been asked to guarantee your loan.");
    }

    /** The applicant taking back a request nobody has answered yet. */
    public function destroy(Request $request, Loan $loan, LoanGuarantor $guarantor)
    {
        abort_unless($guarantor->loan_id === $loan->id, 404);
        abort_unless($loan->user_id === $request->user()->id, 403);
        abort_unless($guarantor->status === 'invited', 409, 'That request has already been answered.');

        $guarantor->update(['status' => 'revoked', 'responded_at' => now()]);

        return back()->with('success', 'Request withdrawn.');
    }

    public function accept(Request $request, LoanGuarantor $guarantor)
    {
        return $this->respond($request, $guarantor, true);
    }

    public function decline(Request $request, LoanGuarantor $guarantor)
    {
        return $this->respond($request, $guarantor, false);
    }

    protected function respond(Request $request, LoanGuarantor $guarantor, bool $accepted)
    {
        // Only the person asked may answer, and only through their own
        // authenticated session — this is the consent that puts their name on
        // a default notice.
        abort_unless($guarantor->user_id === $request->user()->id, 403);

        $validated = $request->validate(['response_note' => 'nullable|string|max:1000']);

        try {
            $this->guarantors->respond(
                $guarantor,
                $request->user(),
                $accepted,
                $validated['response_note'] ?? null,
                $request->ip()
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $accepted
            ? 'You have accepted. Thank you for standing for a fellow member.'
            : 'You have declined the request.');
    }
}
