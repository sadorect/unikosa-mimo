<?php

namespace App\Jobs;

use App\Models\BlogPost;
use App\Models\Set;
use App\Models\User;
use App\Notifications\BlogDigestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBlogDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public ?int $setId = null) {}

    public function handle(): void
    {
        $weekAgo = now()->subWeek();

        $posts = BlogPost::where('status', 'published')
            ->where('published_at', '>=', $weekAgo)
            ->with('author')
            ->latest('published_at')
            ->get();

        if ($posts->isEmpty()) return;

        $subscriberQuery = User::where('status', 'approved')->whereNull('deleted_at');

        if ($this->setId) {
            $subscriberQuery->where('graduating_set_id', $this->setId);
        }

        $subscriberQuery->each(function (User $subscriber) use ($posts) {
            $subscriber->notify(new BlogDigestNotification($posts));
        });
    }

    public static function dispatchForAllSets(): void
    {
        Set::each(fn (Set $set) => static::dispatch($set->id));
    }
}
