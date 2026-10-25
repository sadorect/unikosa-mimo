<?php

namespace App\Http\Middleware;

use App\Services\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify owns POST /logout. When an admin signed in as a member clicks
 * "Sign Out", what they mean is "stop being this member" — letting Fortify
 * run would kill the admin's own session too and strand them at the login
 * page. So the logout is answered here, before Fortify sees it.
 */
class StopImpersonationOnLogout
{
    public function __construct(protected Impersonation $impersonation) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->is('logout') && $this->impersonation->isActive()) {
            $this->impersonation->stop();

            return redirect('/admin/users');
        }

        return $next($request);
    }
}
