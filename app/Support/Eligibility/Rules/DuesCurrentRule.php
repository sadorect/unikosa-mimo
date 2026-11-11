<?php

namespace App\Support\Eligibility\Rules;

use App\Models\Due;
use App\Models\Payment;
use App\Models\User;
use App\Support\Eligibility\RuleInterface;

/**
 * config: { due_ids?: int[] } — passes if the user has a successful payment for every
 * currently-applicable due (or, if due_ids is given, every due in that specific list).
 * "Applicable" is Due::applicableTo(): a due
 * targets the user if it matches their set/chapter, or is unscoped (applies to everyone).
 */
class DuesCurrentRule implements RuleInterface
{
    public function passes(User $user, array $config): bool
    {
        $duesQuery = Due::applicableTo($user);

        if (! empty($config['due_ids'])) {
            $duesQuery->whereIn('id', $config['due_ids']);
        }

        $applicableDueIds = $duesQuery->pluck('id');

        if ($applicableDueIds->isEmpty()) {
            return true;
        }

        $paidDueIds = Payment::where('user_id', $user->id)
            ->where('payable_type', Due::class)
            ->whereIn('payable_id', $applicableDueIds)
            ->where('status', 'successful')
            ->pluck('payable_id');

        return $applicableDueIds->diff($paidDueIds)->isEmpty();
    }
}
