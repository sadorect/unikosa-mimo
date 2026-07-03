<?php

namespace App\Http\Middleware;

use App\Services\CaptchaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class VerifyCaptcha
{
    /** Maps route names to the captcha "form" key used in Setting::captcha_forms. */
    protected array $routeFormMap = [
        'login.store' => 'login',
        'register.store' => 'register',
        'password.email' => 'forgot_password',
        'password.update' => 'reset_password',
        'contact.store' => 'contact',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $form = $this->routeFormMap[$request->route()?->getName()] ?? null;

        if ($form && app(CaptchaService::class)->appliesTo($form)) {
            $valid = app(CaptchaService::class)->verify(
                $request->input('captcha_id'),
                $request->input('captcha_answer')
            );

            if (! $valid) {
                throw ValidationException::withMessages([
                    'captcha_answer' => 'The captcha answer is incorrect. Please try again.',
                ]);
            }
        }

        return $next($request);
    }
}
