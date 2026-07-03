<?php

namespace App\Support\Eligibility;

use App\Models\User;

interface RuleInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function passes(User $user, array $config): bool;
}
