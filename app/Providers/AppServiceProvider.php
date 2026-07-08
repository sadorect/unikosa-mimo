<?php

namespace App\Providers;

use App\Http\Responses\PassportAuthorizationViewResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthorizationViewResponse::class, PassportAuthorizationViewResponse::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // First-party chapter sites skip the OAuth consent screen.
        Passport::useClientModel(\App\Models\Passport\Client::class);

        // SSO tokens for satellite chapter sites (e.g. Unikosana NA).
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}
