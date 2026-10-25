<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(protected Impersonation $impersonation) {}

    /**
     * Route-model binding drops soft-deleted users, so a deleted target is a
     * 404 here before the service ever sees it.
     */
    public function start(Request $request, User $user): RedirectResponse
    {
        $reason = $this->impersonation->denialReason($request->user(), $user);

        if ($reason !== null) {
            abort(403, $reason);
        }

        $this->impersonation->start($request->user(), $user);

        return redirect()->route('dashboard');
    }

    /**
     * Lives under plain `auth`, not the admin panel: the member being viewed
     * usually has no panel access, and this is the one thing they must always
     * be able to reach.
     */
    public function stop(): RedirectResponse
    {
        $impersonator = $this->impersonation->stop();

        if (! $impersonator) {
            return redirect()->route('dashboard');
        }

        return redirect('/admin/users');
    }
}
