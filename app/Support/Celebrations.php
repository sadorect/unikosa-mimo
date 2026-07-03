<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

/**
 * Computes upcoming member celebrations (birthdays from date_of_birth, wedding
 * anniversaries from wedding_anniversary) within a rolling window. Handles the
 * year-end wraparound in PHP so it's correct regardless of DB date functions,
 * and respects each member's privacy toggle for their date of birth.
 */
class Celebrations
{
    /**
     * @return array<int, array{type:string,name:string,avatar:?string,user_id:int,date:string,days_until:int,label:string}>
     */
    public static function upcoming(int $days = 14, ?int $setId = null, ?int $chapterId = null, int $limit = 12): array
    {
        $today = Carbon::today();

        $query = User::query()
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNotNull('date_of_birth')->orWhereNotNull('wedding_anniversary');
            })
            ->with('profilePrivacy')
            ->select('id', 'name', 'avatar', 'date_of_birth', 'wedding_anniversary');

        if ($setId) {
            $query->where('graduating_set_id', $setId);
        }
        if ($chapterId) {
            $query->where('chapter_id', $chapterId);
        }

        $events = [];

        foreach ($query->get() as $user) {
            // Birthdays honour the member's "show date of birth" privacy setting.
            $showDob = $user->profilePrivacy?->show_dob ?? false;
            if ($user->date_of_birth && $showDob) {
                if (($e = self::occurrence($user, $user->date_of_birth, 'birthday', $today, $days)) !== null) {
                    $events[] = $e;
                }
            }
            if ($user->wedding_anniversary) {
                if (($e = self::occurrence($user, $user->wedding_anniversary, 'anniversary', $today, $days)) !== null) {
                    $events[] = $e;
                }
            }
        }

        usort($events, fn ($a, $b) => $a['days_until'] <=> $b['days_until']);

        return array_slice($events, 0, $limit);
    }

    private static function occurrence(User $user, Carbon $date, string $type, Carbon $today, int $days): ?array
    {
        // Next occurrence of this month/day, this year or next.
        $next = Carbon::create($today->year, $date->month, $date->day);
        if ($next->lt($today)) {
            $next->addYear();
        }

        $daysUntil = (int) $today->diffInDays($next, false);
        if ($daysUntil < 0 || $daysUntil > $days) {
            return null;
        }

        $label = match ($daysUntil) {
            0 => 'Today',
            1 => 'Tomorrow',
            default => "in {$daysUntil} days",
        };

        return [
            'type' => $type,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'user_id' => $user->id,
            'date' => $next->format('M j'),
            'days_until' => $daysUntil,
            'label' => $label,
        ];
    }
}
