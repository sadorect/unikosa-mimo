<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Services\LoanRepaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class LoanRepaymentController extends Controller
{
    public function __construct(protected LoanRepaymentService $repayments) {}

    public function store(Request $request, Loan $loan)
    {
        Gate::authorize('recordRepayment', $loan);

        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'paid_on' => 'required|date',
            'method' => 'required|in:'.implode(',', config('loans.repayment_methods')),
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'proof' => 'nullable|file|mimes:'.implode(',', config('loans.proof_mimes')).'|max:'.config('loans.proof_max_kb'),
        ]);

        if ($request->hasFile('proof')) {
            // Private disk: a repayment slip carries account details and must
            // not be reachable under /storage.
            $validated['proof_path'] = $request->file('proof')->store('loan-repayments', config('loans.private_disk'));
        }

        try {
            $this->repayments->record($loan, $request->user(), $validated);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['amount' => $exception->getMessage()]);
        }

        return back()->with('success', 'Recorded. The fund will confirm it once the money is seen.');
    }
}
