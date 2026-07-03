<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\UnikosaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $logoPath = Setting::get('logo_path');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'avatar' => $request->user()->avatar,
                    'status' => $request->user()->status,
                    'roles' => $request->user()->getRoleNames()->toArray(),
                ] : null,
            ],
            'settings' => [
                'site_name' => Setting::get('site_name', 'Unikosa'),
                'accent_color' => Setting::get('accent_color', '#F59E0B'),
                'theme_mode' => Setting::get('theme_mode', 'light'),
                'font_family' => Setting::get('font_family', 'Inter'),
                'logo_url' => $logoPath ? Storage::disk(config('filesystems.media_disk'))->url($logoPath) : null,
                'social' => [
                    'facebook' => Setting::get('social_facebook'),
                    'twitter' => Setting::get('social_twitter'),
                    'instagram' => Setting::get('social_instagram'),
                    'linkedin' => Setting::get('social_linkedin'),
                    'youtube' => Setting::get('social_youtube'),
                ],
            ],
            'notifications' => [
                'unread_count' => fn () => $request->user()
                    ? UnikosaNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count()
                    : 0,
            ],
            'messages' => [
                'unread_count' => fn () => $request->user()
                    ? \App\Models\Message::where('recipient_id', $request->user()->id)->whereNull('read_at')->count()
                    : 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
