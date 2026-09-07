<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * What an error page should say.
 *
 * Two rules run through this class.
 *
 * First, when the application aborted with its own explanation — "You have
 * already voted in this election", "This fund has not published its terms of
 * operation" — that sentence is what the member needs, and it is shown instead
 * of the generic wording. Seventeen aborts across this codebase carry such a
 * message and every one of them used to be thrown away.
 *
 * Second, that only ever applies to statuses we raise deliberately. A 5xx
 * message is written by the framework or by whatever actually broke, so it can
 * carry a file path, a query or a credential; those always get the generic
 * text.
 */
class ErrorPage
{
    /**
     * Statuses we render ourselves. Anything else falls through to the
     * framework, which is the right outcome for things a member will never
     * see.
     */
    public const HANDLED = [400, 401, 402, 403, 404, 405, 408, 409, 410, 413, 419, 422, 429, 500, 502, 503, 504];

    /** Statuses whose own message is safe to show: ones the app raises on purpose. */
    protected const MESSAGE_SAFE = [400, 401, 402, 403, 404, 405, 409, 410, 413, 422, 429, 503];

    /**
     * @return array{status: int, title: string, message: string, action: string|null}
     */
    public static function for(int $status, ?Throwable $exception = null, ?Request $request = null): array
    {
        $default = static::defaults()[$status] ?? [
            'title' => 'Something went wrong',
            'message' => 'An unexpected error stopped this page from loading. Please try again.',
        ];

        return [
            'status' => $status,
            'title' => $default['title'],
            'message' => static::explain($status, $exception) ?? $default['message'],
            'action' => $default['action'] ?? null,
        ];
    }

    /**
     * The application's own words, when it had any and they are safe to show.
     */
    protected static function explain(int $status, ?Throwable $exception): ?string
    {
        if (! $exception instanceof HttpExceptionInterface) {
            return null;
        }

        if (! in_array($status, static::MESSAGE_SAFE, true)) {
            return null;
        }

        $message = trim($exception->getMessage());

        if ($message === '') {
            return null;
        }

        return static::looksInternal($message) ? null : $message;
    }

    /**
     * Is this the framework talking to a developer rather than the application
     * talking to a member?
     *
     * The distinction is not cosmetic. "The route no-such-page could not be
     * found" tells a member nothing and tells everyone else how the app is put
     * together, and "No query results for model [App\Models\Loan] 5" names an
     * internal class. Anything that fails this check falls back to the written
     * copy for its status.
     */
    protected static function looksInternal(string $message): bool
    {
        // Symfony's placeholder for a bare abort(): no more use to a member
        // than the status code already on the page.
        $boilerplate = ['Not Found', 'Forbidden', 'Unauthorized', 'Bad Request', 'Too Many Requests', 'Service Unavailable', 'Unprocessable Entity', 'Unprocessable Content', 'Method Not Allowed', 'Gone', 'Conflict', 'Payload Too Large', 'Request Timeout', 'Payment Required'];

        if (in_array($message, $boilerplate, true)) {
            return true;
        }

        // A namespace separator, a scope resolution or a filesystem path means
        // we are looking at internals, whatever the sentence around it says.
        if (str_contains($message, '\\') || str_contains($message, '::') || str_contains($message, '/var/') || str_contains($message, base_path())) {
            return true;
        }

        // Long enough to be a stack trace or a query rather than a sentence.
        if (mb_strlen($message) > 300) {
            return true;
        }

        $frameworkPhrases = [
            '/^The route .* could not be found/i',
            '/^No query results for model/i',
            '/^Route \[.*\] not defined/i',
            '/method is not supported for (this )?route/i',
            '/^Target class .* does not exist/i',
            '/^Attempt to read property/i',
            '/^Call to (a member function|undefined)/i',
            '/^SQLSTATE/i',
            '/^Undefined (variable|array key|property)/i',
        ];

        foreach ($frameworkPhrases as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{title: string, message: string, action?: string}>
     */
    protected static function defaults(): array
    {
        return [
            400 => [
                'title' => 'That request could not be understood',
                'message' => 'Something about the request was malformed. Going back and trying again usually clears it.',
            ],
            401 => [
                'title' => 'Please sign in',
                'message' => 'You need to be signed in to view this page.',
                'action' => 'login',
            ],
            402 => [
                'title' => 'Payment required',
                'message' => 'This page is only available once the related payment has been completed.',
            ],
            403 => [
                'title' => "You don't have access to this page",
                'message' => "You're signed in, but your account doesn't have permission to view this page. If you think that's a mistake, an administrator can check it for you.",
            ],
            404 => [
                'title' => 'Page not found',
                'message' => "We couldn't find what you're looking for. It may have been moved or deleted, or the link might be incorrect.",
            ],
            405 => [
                'title' => 'That action is not allowed here',
                'message' => 'This page does not accept the action you tried. Please go back and use the buttons on the page.',
            ],
            408 => [
                'title' => 'The request timed out',
                'message' => 'Your request took too long to reach us. Check your connection and try again.',
            ],
            409 => [
                'title' => 'That has already been done',
                'message' => 'This action has already been carried out, or something changed while you were on the page. Refresh to see where things stand.',
            ],
            410 => [
                'title' => 'This page is no longer available',
                'message' => 'What you are looking for has been removed for good.',
            ],
            413 => [
                'title' => 'That file is too large',
                'message' => 'The file you tried to upload is bigger than we can accept. Please use a smaller one.',
            ],
            419 => [
                'title' => 'Your session expired',
                'message' => 'For your security this page timed out after a period of inactivity. Sign in again and your work can continue.',
                'action' => 'login',
            ],
            422 => [
                'title' => 'We could not process that',
                'message' => 'Some of the information submitted was not valid. Please go back, check the form and try again.',
            ],
            429 => [
                'title' => 'Too many attempts',
                'message' => "You've made a lot of requests in a short time. Please wait a minute and try again.",
            ],
            500 => [
                'title' => 'Something went wrong on our end',
                'message' => 'This is our fault, not yours. The error has been logged and will be looked into. Please try again in a few minutes.',
            ],
            502 => [
                'title' => 'We could not reach part of the site',
                'message' => 'A service this page depends on did not respond. This is usually brief — please try again shortly.',
            ],
            503 => [
                'title' => 'Temporarily unavailable',
                'message' => "This part of the site is unavailable at the moment. If maintenance is under way it is usually over quickly, so please check back shortly.",
            ],
            504 => [
                'title' => 'That took too long',
                'message' => 'The page took too long to build and we stopped waiting. Please try again — if it keeps happening, let us know.',
            ],
        ];
    }
}
