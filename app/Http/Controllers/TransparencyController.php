<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Payment;
use App\Models\Setting;
use Inertia\Inertia;

class TransparencyController extends Controller
{
    public function index()
    {
        $enabled = Setting::where('key', 'transparency_report_enabled')->value('value') ?? 'true';

        if ($enabled === 'false') {
            abort(404);
        }

        $totalRaised = Payment::where('status', 'successful')->sum('amount');
        $totalDonors = Payment::where('status', 'successful')->distinct('user_id')->count('user_id');

        $byCurrency = Payment::where('status', 'successful')
            ->selectRaw('currency, sum(amount) as total, count(*) as transactions')
            ->groupBy('currency')
            ->get();

        $byMethod = Payment::where('status', 'successful')
            ->selectRaw('payment_method, sum(amount) as total, count(*) as transactions')
            ->groupBy('payment_method')
            ->get();

        $campaigns = Campaign::orderByDesc('raised_amount')
            ->get(['id', 'title', 'target_amount', 'raised_amount', 'currency', 'is_active']);

        $monthly = Payment::where('status', 'successful')
            ->where('paid_at', '>=', now()->subYear())
            ->selectRaw("to_char(paid_at, 'YYYY-MM') as month, sum(amount) as total, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('Transparency/Index', [
            'totalRaised'  => $totalRaised,
            'totalDonors'  => $totalDonors,
            'byCurrency'   => $byCurrency,
            'byMethod'     => $byMethod,
            'campaigns'    => $campaigns,
            'monthly'      => $monthly,
            'publishedAt'  => now()->toDateString(),
        ]);
    }
}
