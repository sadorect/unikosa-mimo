<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BlogDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $posts,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Weekly Blog Digest - ' . config('app.name'))
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Here are the latest blog posts from this week:');

        foreach ($this->posts->take(5) as $post) {
            $mail->line('**' . $post->title . '** by ' . $post->author?->name);
            if ($post->excerpt) {
                $mail->line(substr($post->excerpt, 0, 100) . '...');
            }
        }

        return $mail->action('View Blog', url('/blog'))
            ->line('You received this because you are a member of the alumni community.');
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'blog_digest', 'post_count' => $this->posts->count()];
    }
}
