<?php

namespace App\Support;

use App\Models\Set;
use App\Models\User;

class SetCoordinatorRole
{
    /**
     * Keep the `set_representative` role in step with coordinator appointments:
     * grant it to everyone who now coordinates this set, and take it back from
     * anyone who no longer coordinates any set at all.
     */
    public static function sync(Set $set): void
    {
        $current = $set->coordinators()->get();

        foreach ($current as $coordinator) {
            if (! $coordinator->hasRole('set_representative')) {
                $coordinator->assignRole('set_representative');
            }
        }

        $stale = User::role('set_representative')
            ->whereDoesntHave('coordinatedSets')
            ->get();

        foreach ($stale as $user) {
            // Never strip the role from someone who is still a set's named rep.
            if (Set::where('rep_id', $user->id)->exists()) {
                continue;
            }

            $user->removeRole('set_representative');
        }
    }
}
