<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberApprovedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Your Profile Has Been Approved!')
            ->greeting('Welcome, ' . $notifiable->name . '!')
            ->line('Your alumni profile has been approved. You now have full access to the platform.')
            ->action('View Dashboard', url('/dashboard'))
            ->line('Thank you for joining our alumni community!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'member_approved',
            'message' => 'Your profile has been approved. Welcome to the community!',
        ];
    }
}
