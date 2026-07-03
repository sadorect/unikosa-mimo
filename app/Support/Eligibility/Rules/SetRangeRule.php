<?php

namespace App\Support\Eligibility\Rules;

use App\Models\User;
use App\Support\Eligibility\RuleInterface;

/**
 * config: { min_year?: int, max_year?: int }
 */
class SetRangeRule implements RuleInterface
{
    public function passes(User $user, array $config): bool
    {
        $year = $user->graduatingSet?->year;

        if ($year === null) {
            return false;
        }

        if (isset($config['min_year']) && $year < (int) $config['min_year']) {
            return false;
        }

        if (isset($config['max_year']) && $year > (int) $config['max_year']) {
            return false;
        }

        return true;
    }
}
