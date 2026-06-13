<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function index()
    {
        return Inertia::render('Campaigns/Index', [
            'campaigns' => Campaign::where('is_active', true)->latest()->paginate(12),
        ]);
    }

    public function show(Campaign $campaign)
    {
        $campaign->load('creator');

        return Inertia::render('Payments/Contribute', [
            'campaign' => $campaign,
        ]);
    }
}
