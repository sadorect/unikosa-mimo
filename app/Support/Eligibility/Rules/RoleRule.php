<?php

namespace App\Support\Eligibility\Rules;

use App\Models\User;
use App\Support\Eligibility\RuleInterface;

/**
 * config: { roles: string[] }
 */
class RoleRule implements RuleInterface
{
    public function passes(User $user, array $config): bool
    {
        return $user->hasAnyRole($config['roles'] ?? []);
    }
}
