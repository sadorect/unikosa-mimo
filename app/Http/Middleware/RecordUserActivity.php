<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records when a signed-in member was last seen.
 *
 * Writing on every request would mean a database write per page view for every
 * member online — presence is not worth that. A cache marker holds the write
 * off for `presence.write_every_seconds`, so a member browsing for an hour
 * costs one write a minute rather than one a click.
 *
 * The update deliberately bypasses the model: it must not touch `updated_at`,
 * fire model events, or appear in the activity log. Somebody reading a page is
 * not a change to their account.
 */
class RecordUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $this->touch($user);
        }

        return $next($request);
    }

    protected function touch(User $user): void
    {
        $key = "presence:{$user->getKey()}";

        if (Cache::has($key)) {
            return;
        }

        Cache::put($key, true, (int) config('presence.write_every_seconds'));

        User::withoutTimestamps(fn () => User::whereKey($user->getKey())->update(['last_seen_at' => now()]));
    }
}
