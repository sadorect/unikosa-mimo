<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventDigestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEventDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $upcomingEvents = Event::where('start_at', '>=', now())
            ->where('start_at', '<=', now()->addWeek())
            ->with('chapter')
            ->get();

        if ($upcomingEvents->isEmpty()) return;

        $chapters = Chapter::with('members')->get();

        foreach ($chapters as $chapter) {
            $chapterEvents = $upcomingEvents->filter(fn ($e) => $e->chapter_id === $chapter->id || is_null($e->chapter_id));

            if ($chapterEvents->isEmpty()) continue;

            foreach ($chapter->members as $member) {
                if ($member->status !== 'approved') continue;
                $member->notify(new EventDigestNotification($chapterEvents));
            }
        }
    }
}
