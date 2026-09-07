<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Renders the error page.
 *
 * The Inertia page is the one members normally see. The Blade fallback exists
 * because the Inertia one cannot always be built: HandleInertiaRequests reads
 * settings from the database on every request, so exactly when the database is
 * the thing that broke, rendering the pretty 500 throws a second 500 and the
 * member gets a blank white page. The fallback touches nothing but config.
 */
class ErrorResponder
{
    public static function make(int $status, ?Throwable $exception, Request $request): Response
    {
        $page = ErrorPage::for($status, $exception, $request);

        // A super admin runs this platform, so a 500 that says only "something
        // went wrong" is useless to exactly the person who has to fix it.
        // Nobody else ever sees this block.
        if (static::viewerMaySeeDetails()) {
            $page['diagnostic'] = ErrorDiagnostic::for($exception);
        }

        // Livewire shows the response body in a modal, so the admin panel gets
        // the readable page rather than the framework's bare 500. It sends
        // JSON-ish headers, hence handling it before the Inertia attempt.
        if ($request->hasHeader('X-Livewire')) {
            return static::decorate(response()->view('errors.fallback', $page, $status));
        }

        try {
            $response = Inertia::render('Error', $page + [
                'signedIn' => Auth::hasUser() && Auth::check(),
            ])->toResponse($request)->setStatusCode($status);
        } catch (Throwable $renderFailure) {
            Log::warning('Falling back to the static error page.', [
                'status' => $status,
                'reason' => $renderFailure->getMessage(),
            ]);

            $response = response()->view('errors.fallback', $page, $status);
        }

        return static::decorate($response);
    }

    /**
     * Only a super admin. Not finance_admin, not a fund officer: a stack trace
     * names files and can quote data, and administering money is not the same
     * trust level as administering the platform.
     */
    protected static function viewerMaySeeDetails(): bool
    {
        return Auth::hasUser()
            && Auth::check()
            && (Auth::user()?->hasRole('super_admin') ?? false);
    }

    protected static function decorate(Response $response): Response
    {
        // An error page has no business in a search index, and a 404 that
        // ranks for a member's name would be worse than one that doesn't.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
