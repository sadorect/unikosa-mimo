<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($template = EmailTemplate::render('registration_declined', ['name' => $notifiable->name])) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail;
        }

        // Fallback if the template has been deleted.
        return (new MailMessage)
            ->subject('An update on your registration')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Thank you for your interest in joining our alumni community.')
            ->line('Your registration has been reviewed and we are unable to approve your account at this time.')
            ->line('If you believe this was a mistake, please reply to this message or contact us so we can look into it.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'member_rejected',
            'message' => 'Your registration was reviewed and could not be approved at this time.',
        ];
    }
}
