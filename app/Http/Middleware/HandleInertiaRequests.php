<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\UnikosaNotification;
use Illuminate\Http\Request;
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
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'status' => $request->user()->status,
                    'roles' => $request->user()->getRoleNames()->toArray(),
                ] : null,
            ],
            'settings' => [
                'site_name' => Setting::get('site_name', 'Unikosa'),
                'accent_color' => Setting::get('accent_color', '#F59E0B'),
                'theme_mode' => Setting::get('theme_mode', 'light'),
                'font_family' => Setting::get('font_family', 'Inter'),
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
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
