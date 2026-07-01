<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRsvpConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventDate = optional($this->event->start_at)->format('F j, Y g:i A');

        $template = EmailTemplate::render('event_rsvp_confirmation', [
            'name' => $notifiable->name,
            'event_title' => $this->event->title,
            'event_date' => $eventDate,
        ]);

        if ($template) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('View Event', url('/events/' . $this->event->id));
        }

        // Fallback if the template has been deleted.
        return (new MailMessage)
            ->action('View Event', url('/events/' . $this->event->id))
            ->subject('You are confirmed for ' . $this->event->title)
            ->line('Your RSVP for ' . $this->event->title . ' on ' . $eventDate . ' is confirmed.')
            ->line('We look forward to seeing you there!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'event_rsvp_confirmation',
            'event_id' => $this->event->id,
            'message' => 'Your RSVP for ' . $this->event->title . ' is confirmed.',
        ];
    }
}
