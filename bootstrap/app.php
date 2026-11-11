<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\StopImpersonationOnLogout::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\RecordUserActivity::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\RecordUserActivity::class,
        ]);
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);
        $middleware->alias([
            'module' => \App\Http\Middleware\CheckModuleEnabled::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'approved' => \App\Http\Middleware\EnsureAccountIsApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Livewire rejecting a tampered snapshot or a write to a #[Locked]
        // property is the defence working, not a fault: scanners hammer
        // /livewire/update with forged payloads (155 of them on 2026-11-05).
        // Answer 400 and keep them out of the error log.
        // Mapped to an HTTP exception, which Laravel never reports.
        foreach ([
            \Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException::class,
            \Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class,
        ] as $tampered) {
            $exceptions->map($tampered, fn (Throwable $e) => new \Symfony\Component\HttpKernel\Exception\HttpException(
                400, 'This page could not be updated. Please reload it and try again.', $e,
            ));
        }

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            // Livewire drives the admin panel and renders whatever body it
            // gets back inside a modal, so it wants the readable page, not the
            // JSON an API client would.
            if (! $request->hasHeader('X-Livewire') && ($request->is('api/*') || $request->expectsJson())) {
                return $response;
            }

            if (app()->environment(['local', 'testing']) && ! config('app.force_error_pages')) {
                return $response;
            }

            $status = $response->getStatusCode();

            // An expired CSRF token is not worth a whole page: the member is
            // still where they were, and a flash message lets them retry.
            if ($status === 419 && back()->getTargetUrl() !== $request->fullUrl()) {
                return back()->with('error', 'Your session expired. Please try again.');
            }

            if (! in_array($status, \App\Support\ErrorPage::HANDLED, true)) {
                return $response;
            }

            return \App\Support\ErrorResponder::make($status, $exception, $request);
        });
    })->create();
