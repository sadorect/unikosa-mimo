<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsApproved
{
    protected array $staffRoles = ['super_admin', 'set_representative', 'chapter_head', 'content_moderator', 'finance_admin'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'approved' && ! $user->hasRole($this->staffRoles)) {
            return redirect()->route('account.pending');
        }

        return $next($request);
    }
}
