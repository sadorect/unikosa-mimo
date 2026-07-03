<?php

namespace App\Support\Eligibility\Rules;

use App\Models\User;
use App\Support\Eligibility\RuleInterface;

/**
 * config: { chapter_ids: int[] }
 */
class ChapterRule implements RuleInterface
{
    public function passes(User $user, array $config): bool
    {
        return in_array($user->chapter_id, $config['chapter_ids'] ?? [], true);
    }
}
