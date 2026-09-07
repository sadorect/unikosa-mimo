<?php

namespace App\Support;

use Illuminate\Support\Str;
use Throwable;

/**
 * Turns an exception into something an operator can read.
 *
 * A bare "500" tells the person running the association nothing, and telling
 * them to go and read the log is not an answer when they are standing in front
 * of a broken screen. This produces a plain sentence about what class of thing
 * went wrong, the exception's own message, where it happened, and the few
 * stack frames that are actually ours.
 *
 * It is only ever shown to a super admin. Members get the written copy for the
 * status code and nothing else — see ErrorPage.
 */
class ErrorDiagnostic
{
    /**
     * @return array{summary: string, type: string, message: string, location: string|null, frames: string[]}|null
     */
    public static function for(?Throwable $exception): ?array
    {
        if (! $exception) {
            return null;
        }

        return [
            'summary' => static::summarise($exception),
            'type' => class_basename($exception),
            'message' => static::redact($exception->getMessage()),
            'location' => static::relative($exception->getFile()).':'.$exception->getLine(),
            'frames' => static::appFrames($exception),
        ];
    }

    /**
     * What kind of problem this is, in a sentence. The exception's own message
     * follows it, so this only has to give the reader a foothold.
     */
    protected static function summarise(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $type = class_basename($exception);

        return match (true) {
            str_contains($message, 'Call to undefined method') =>
                'The code called a method that does not exist on that class. Usually a rename that missed a caller, or a relationship Laravel guessed the name of.',

            str_contains($message, 'Call to a member function') && str_contains($message, 'on null') =>
                'The code expected an object here and found nothing — often a record that was deleted, or a relationship that has no row.',

            str_contains($message, 'Attempt to read property') =>
                'The code read a property off something that turned out to be empty.',

            $type === 'QueryException' =>
                'The database rejected the query. Often a missing column after an incomplete deploy, or a constraint the data does not satisfy.',

            $type === 'RelationNotFoundException' =>
                'A relationship the code asked for does not exist on that model.',

            $type === 'ModelNotFoundException' =>
                'A record the page needs could not be found. It may have been deleted.',

            $type === 'ValidationException' =>
                'The submitted data did not pass validation.',

            $type === 'TypeError' =>
                'A value of the wrong type was passed somewhere. This is a bug in the application code.',

            $type === 'ViewException' =>
                'A page template failed while rendering. The real cause is in the message below.',

            $type === 'FileNotFoundException' || str_contains($message, 'No such file') =>
                'A file the code expected is not on disk.',

            str_contains($message, 'Permission denied') =>
                'The web server was refused permission by the filesystem. Usually ownership on a storage or cache directory.',

            str_contains($message, 'Connection refused') || str_contains($message, 'could not connect') =>
                'A service this page depends on — the database, Redis or mail — did not accept the connection.',

            str_contains($message, 'Maximum execution time') =>
                'The request ran out of time before it finished.',

            str_contains($message, 'Allowed memory size') =>
                'The request ran out of memory, usually from loading far more records than expected.',

            default => 'An unexpected error stopped the page. The detail below is what the application reported.',
        };
    }

    /** Only frames inside this application; the framework's own are noise here. */
    protected static function appFrames(Throwable $exception, int $limit = 6): array
    {
        $frames = [];

        foreach ($exception->getTrace() as $frame) {
            if (! isset($frame['file']) || ! Str::startsWith($frame['file'], base_path('app'))
                && ! Str::startsWith($frame['file'], base_path('routes'))
                && ! Str::startsWith($frame['file'], base_path('database'))) {
                continue;
            }

            $call = ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '');
            $frames[] = static::relative($frame['file']).':'.($frame['line'] ?? '?').($call ? "  {$call}()" : '');

            if (count($frames) >= $limit) {
                break;
            }
        }

        return $frames;
    }

    protected static function relative(string $path): string
    {
        return Str::after($path, base_path().'/');
    }

    /**
     * A database driver will happily put a password into an exception message.
     * The reader is trusted, but a screenshot of this page is not.
     */
    protected static function redact(string $message): string
    {
        $secrets = array_filter([
            config('database.connections.pgsql.password'),
            config('database.connections.mysql.password'),
            config('database.redis.default.password'),
            config('app.key'),
            config('services.paystack.secret'),
            config('services.stripe.secret'),
            config('mail.mailers.smtp.password'),
        ], fn ($secret) => is_string($secret) && mb_strlen($secret) >= 8);

        return Str::limit(str_replace($secrets, '[redacted]', $message), 1000);
    }
}
