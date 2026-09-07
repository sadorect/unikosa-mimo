<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewMemberAwaitingReviewNotification;
use Illuminate\Support\Facades\Notification;

class MembershipReviewNotifier
{
    /**
     * Everyone entitled to review this applicant: the coordinators of their
     * graduating set, the set's named representative, and every super admin as
     * the always-available fallback for sets that have no coordinator yet.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function reviewersFor(User $applicant)
    {
        $reviewers = User::role('super_admin')->get();

        if ($set = $applicant->graduatingSet) {
            $reviewers = $reviewers
                ->concat($set->coordinators()->get())
                ->concat($set->rep ? [$set->rep] : []);
        }

        return $reviewers
            ->reject(fn (User $reviewer) => $reviewer->is($applicant))
            ->unique('id')
            ->values();
    }

    /**
     * Alert every eligible reviewer that a signup is waiting.
     */
    public static function notify(User $applicant): void
    {
        $reviewers = static::reviewersFor($applicant);

        if ($reviewers->isEmpty()) {
            return;
        }

        Notification::send($reviewers, new NewMemberAwaitingReviewNotification($applicant));
    }
}
