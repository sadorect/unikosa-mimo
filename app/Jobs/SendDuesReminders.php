<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\DuesReminderNotification;
use App\Models\Due;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDuesReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $dues = Due::whereNull('deleted_at')->get();

        foreach ($dues as $due) {
            $users = User::where('status', 'approved')
                ->whereNull('deleted_at');

            if ($due->set_id) {
                $users->where('graduating_set_id', $due->set_id);
            }
            if ($due->chapter_id) {
                $users->where('chapter_id', $due->chapter_id);
            }

            $users->each(function ($user) use ($due) {
                $user->notify(new DuesReminderNotification($due->name, $due->amount, $due->currency));
            });
        }
    }
}
