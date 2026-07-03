<?php

namespace App\Support\Eligibility\Rules;

use App\Models\User;
use App\Support\Eligibility\RuleInterface;

/**
 * config: { set_ids: int[] }
 */
class SetListRule implements RuleInterface
{
    public function passes(User $user, array $config): bool
    {
        return in_array($user->graduating_set_id, $config['set_ids'] ?? [], true);
    }
}
