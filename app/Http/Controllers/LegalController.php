<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Inertia\Inertia;

class LegalController extends Controller
{
    public function privacy()
    {
        return Inertia::render('Legal/Show', [
            'title' => 'Privacy Policy',
            'body' => Setting::get('legal_privacy_policy', ''),
        ]);
    }

    public function terms()
    {
        return Inertia::render('Legal/Show', [
            'title' => 'Terms of Service',
            'body' => Setting::get('legal_terms_of_service', ''),
        ]);
    }
}
