<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Disbursement and repayment proof lives on the private disk and is streamed
 * through here, never linked from /storage. These files carry bank references
 * and account details; a guessable public URL would put them one lucky path
 * away from anyone.
 */
class LoanDocumentController extends Controller
{
    public function disbursementProof(Request $request, Loan $loan)
    {
        Gate::authorize('view', $loan);

        return $this->stream($loan->disbursement_proof_path);
    }

    public function repaymentProof(Request $request, Loan $loan, LoanRepayment $repayment)
    {
        abort_unless($repayment->loan_id === $loan->id, 404);
        Gate::authorize('view', $loan);

        return $this->stream($repayment->proof_path);
    }

    protected function stream(?string $path)
    {
        abort_unless($path, 404);

        $disk = Storage::disk(config('loans.private_disk'));
        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
