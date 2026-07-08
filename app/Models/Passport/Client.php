<?php

namespace App\Models\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client as BaseClient;

class Client extends BaseClient
{
    /**
     * Skip the OAuth consent screen for first-party clients.
     *
     * Every client issued by this identity provider is a first-party
     * chapter site owned by the same organisation (e.g. Unikosana North
     * America), so prompting the member to "authorize" our own app is
     * unnecessary friction. A hypothetical third-party client (one created
     * with an owner) still receives the standard consent screen.
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return $this->firstParty();
    }
}
