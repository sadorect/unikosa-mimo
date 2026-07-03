<?php

namespace App\Support\Eligibility\Rules;

use App\Models\User;
use App\Support\Eligibility\RuleInterface;

/**
 * config: {} — excludes imported-but-unclaimed alumni records.
 */
class AccountClaimedRule implements RuleInterface
{
    public function passes(User $user, array $config): bool
    {
        return (bool) $user->account_claimed;
    }
}
