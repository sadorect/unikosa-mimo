<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $events,
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
            ->subject('Upcoming Events This Week - ' . config('app.name'))
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Here are the upcoming events this week:');

        foreach ($this->events->take(5) as $event) {
            $mail->line('**' . $event->title . '** - ' . $event->start_at->format('M j, Y g:i A'));
            if ($event->location) {
                $mail->line('Location: ' . $event->location);
            }
        }

        return $mail->action('View Events', url('/events'))
            ->line('Don\'t miss out on these community events!');
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'event_digest', 'event_count' => $this->events->count()];
    }
}
