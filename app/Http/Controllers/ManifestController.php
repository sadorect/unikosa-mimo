<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class ManifestController extends Controller
{
    public function index()
    {
        $siteName = Setting::get('site_name', 'Unikosa');
        $accentColor = Setting::get('accent_color', '#F59E0B');

        $icons = [
            ['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => asset('icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ];

        $logoPath = Setting::get('logo_path');
        if ($logoPath) {
            array_unshift($icons, [
                'src' => Storage::disk(config('filesystems.media_disk'))->url($logoPath),
                'sizes' => 'any',
                'type' => 'image/png',
            ]);
        }

        return response()->json([
            'name' => $siteName . ' Alumni Network',
            'short_name' => $siteName,
            'description' => $siteName . ' is the official global alumni network — connecting graduates for events, mentorship, jobs, and giving back.',
            'start_url' => '/',
            'id' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $accentColor,
            'icons' => $icons,
        ])->header('Content-Type', 'application/manifest+json');
    }
}
