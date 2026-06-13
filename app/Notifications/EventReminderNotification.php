<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Event $event,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Upcoming Event: ' . $this->event->title)
            ->line('This is a reminder about an upcoming event you RSVP\'d to.')
            ->line('Event: ' . $this->event->title)
            ->line('Date: ' . $this->event->start_at->format('F j, Y g:i A'))
            ->line($this->event->location ? 'Location: ' . $this->event->location : 'Virtual Event')
            ->action('View Event', url('/events/' . $this->event->slug));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'event_reminder',
            'event_id' => $this->event->id,
            'message' => "Reminder: {$this->event->title} on {$this->event->start_at->format('M j, Y g:i A')}",
        ];
    }
}
