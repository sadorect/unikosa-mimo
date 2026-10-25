<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

/**
 * "Sign in as" for super admins.
 *
 * The only state is the `impersonator_id` session key: while it is set the
 * signed-in user is the member being viewed and the key names the admin who
 * started it. Every rule about who may impersonate whom lives in
 * denialReason() so the Filament button and the route can never disagree.
 */
class Impersonation
{
    public const SESSION_KEY = 'impersonator_id';

    public function __construct(
        protected Session $session,
    ) {}

    /**
     * Why $actor may not sign in as $target, or null when they may.
     */
    public function denialReason(?User $actor, User $target): ?string
    {
        if (! $actor) {
            return 'You must be signed in.';
        }

        if ($this->isActive()) {
            return 'Stop the current session before signing in as somebody else.';
        }

        if (! $actor->hasRole('super_admin')) {
            return 'Only a super admin can sign in as a member.';
        }

        if ($actor->is($target)) {
            return 'You are already signed in as yourself.';
        }

        // Never hand out another super admin's session, even to a super admin:
        // the audit trail would show them acting and they would have no idea.
        if ($target->hasRole('super_admin')) {
            return 'Super admin accounts cannot be impersonated.';
        }

        if ($target->trashed()) {
            return 'This account has been deleted.';
        }

        return null;
    }

    public function start(User $actor, User $target): void
    {
        $reason = $this->denialReason($actor, $target);

        if ($reason !== null) {
            throw new \LogicException($reason);
        }

        activity('impersonation')
            ->causedBy($actor)
            ->performedOn($target)
            ->event('impersonation.started')
            ->log("Signed in as {$target->name}");

        $this->session->put(self::SESSION_KEY, $actor->getKey());
        $this->guard()->login($target);
        $this->session->regenerate();
    }

    /**
     * Return the browser to the admin who started it. Returns that admin, or
     * null when nothing was being impersonated.
     */
    public function stop(): ?User
    {
        $impersonator = $this->impersonator();

        if (! $impersonator) {
            $this->session->forget(self::SESSION_KEY);

            return null;
        }

        $target = $this->guard()->user();

        if ($target) {
            activity('impersonation')
                ->causedBy($impersonator)
                ->performedOn($target)
                ->event('impersonation.stopped')
                ->log("Stopped signing in as {$target->name}");
        }

        $this->session->forget(self::SESSION_KEY);
        $this->guard()->login($impersonator);
        $this->session->regenerate();

        return $impersonator;
    }

    public function isActive(): bool
    {
        return $this->session->has(self::SESSION_KEY);
    }

    public function impersonator(): ?User
    {
        $id = $this->session->get(self::SESSION_KEY);

        return $id ? User::find($id) : null;
    }

    protected function guard(): StatefulGuard
    {
        return Auth::guard('web');
    }
}
