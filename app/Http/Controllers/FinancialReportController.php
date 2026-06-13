<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Payments/Reports');
    }

    public function summary(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'set_id' => 'nullable|exists:sets,id',
            'chapter_id' => 'nullable|exists:chapters,id',
        ]);

        $query = Payment::where('status', 'successful');

        if ($request->date_from) {
            $query->where('paid_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('paid_at', '<=', $request->date_to . ' 23:59:59');
        }

        $totalRaised = (clone $query)->sum('amount');
        $totalTransactions = (clone $query)->count();
        $byMethod = (clone $query)->selectRaw('payment_method, count(*) as count, sum(amount) as total')
            ->groupBy('payment_method')
            ->get();
        $byCurrency = (clone $query)->selectRaw('currency, count(*) as count, sum(amount) as total')
            ->groupBy('currency')
            ->get();

        $daily = (clone $query)
            ->selectRaw('date(paid_at) as date, sum(amount) as total, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'total_raised' => $totalRaised,
            'total_transactions' => $totalTransactions,
            'by_method' => $byMethod,
            'by_currency' => $byCurrency,
            'daily' => $daily,
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'format' => 'required|in:csv,pdf',
        ]);

        $query = Payment::where('status', 'successful')
            ->with(['user:id,name,email', 'payable'])
            ->latest('paid_at');

        if ($request->date_from) {
            $query->where('paid_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('paid_at', '<=', $request->date_to . ' 23:59:59');
        }

        $payments = $query->get();

        if ($request->format === 'csv') {
            $headers = ['Content-Type' => 'text/csv'];
            $filename = 'financial_report_' . now()->format('Y-m-d') . '.csv';

            $callback = function () use ($payments) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Date', 'User', 'Email', 'Amount', 'Currency', 'Method', 'Reference', 'Type']);
                foreach ($payments as $p) {
                    fputcsv($handle, [
                        $p->paid_at?->format('Y-m-d H:i'),
                        $p->user->name ?? 'N/A',
                        $p->user->email ?? 'N/A',
                        $p->amount,
                        $p->currency,
                        $p->payment_method,
                        $p->payment_reference,
                        class_basename($p->payable_type),
                    ]);
                }
                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        return back()->with('error', 'PDF export not yet implemented for reports.');
    }
}
