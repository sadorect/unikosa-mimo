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

    /**
     * Reminders go out in waves rather than all at once: the local mail server
     * refuses a client that sends too many messages a minute ("450 4.7.1 too
     * much mail"), which is how the October and November runs lost nearly every
     * email. Each wave of BATCH_SIZE emails is held BATCH_INTERVAL seconds behind
     * the previous one. The in-app notification is not delayed.
     */
    public const BATCH_SIZE = 20;

    public const BATCH_INTERVAL = 90;

    public function handle(): void
    {
        $dues = Due::whereNull('deleted_at')->get();
        $sent = 0;

        foreach ($dues as $due) {
            $users = User::where('status', 'approved')
                ->whereNull('deleted_at');

            if ($due->set_id) {
                $users->where('graduating_set_id', $due->set_id);
            }
            if ($due->chapter_id) {
                $users->where('chapter_id', $due->chapter_id);
            }

            $users->each(function ($user) use ($due, &$sent) {
                $wave = intdiv($sent++, self::BATCH_SIZE);

                $user->notify(
                    (new DuesReminderNotification($due->name, $due->amount, $due->currency))
                        ->delay(['mail' => now()->addSeconds($wave * self::BATCH_INTERVAL)])
                );
            });
        }
    }
}
