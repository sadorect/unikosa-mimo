<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * A statement of the loan: what was borrowed, what has been paid, by whom,
 * and what is left. Mirrors PaymentController::receipt().
 */
class LoanStatementController extends Controller
{
    public function __invoke(Request $request, Loan $loan)
    {
        Gate::authorize('view', $loan);

        $loan->load(['program.set', 'applicant', 'repayments.payer', 'guarantors.user']);

        $pdf = Pdf::loadView('loans.statement', [
            'loan' => $loan,
            // Only confirmed rows appear: a statement showing money nobody has
            // seen would be worse than no statement.
            'repayments' => $loan->repayments->where('status', 'confirmed')->sortBy('paid_on'),
            'appName' => config('app.name'),
            'appUrl' => config('app.url'),
        ]);

        return $pdf->download("loan-statement-{$loan->reference}.pdf");
    }
}
