<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberPendingApprovalNotification extends Notification implements ShouldQueue
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
        if ($template = EmailTemplate::render('registration_pending', ['name' => $notifiable->name])) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail;
        }

        // Fallback if the template has been deleted.
        return (new MailMessage)
            ->subject('We have received your registration')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Thank you for registering. Your account has been created and is now awaiting review by one of our administrators.')
            ->line('You will not be able to access member areas until that review is complete.')
            ->line('We will email you as soon as a decision has been made — whether your account is approved or declined.')
            ->line('No further action is needed from you in the meantime.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'member_pending_approval',
            'message' => 'Your registration is awaiting review by an administrator. We will email you once a decision is made.',
        ];
    }
}
