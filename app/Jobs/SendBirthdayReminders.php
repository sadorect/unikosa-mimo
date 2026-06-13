<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\BirthdayNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBirthdayReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function handle(): void
    {
        $today = now();

        User::where('status', 'approved')
            ->whereNull('deleted_at')
            ->whereNotNull('date_of_birth')
            ->whereRaw('EXTRACT(MONTH FROM date_of_birth) = ?', [$today->month])
            ->whereRaw('EXTRACT(DAY FROM date_of_birth) = ?', [$today->day])
            ->each(function (User $user) {
                $user->notify(new BirthdayNotification());
            });
    }
}
