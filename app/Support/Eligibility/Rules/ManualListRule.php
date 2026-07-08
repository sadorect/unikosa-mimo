<?php

namespace App\Support\Eligibility\Rules;

use App\Models\User;
use App\Support\Eligibility\RuleInterface;

/**
 * config: { user_ids: int[] } — explicit allow-list escape hatch for criteria not
 * covered by another rule type.
 */
class ManualListRule implements RuleInterface
{
    public function passes(User $user, array $config): bool
    {
        return in_array($user->id, array_map('intval', $config['user_ids'] ?? []), true);
    }
}
