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

        // An error page has no business in a search index, and a 404 that
        // ranks for a member's name would be worse than one that doesn't.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
