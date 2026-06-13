<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BirthdayNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Happy Birthday from ' . config('app.name') . '!')
            ->greeting('Happy Birthday, ' . $notifiable->name . '! 🎂')
            ->line('On behalf of the entire alumni community, we wish you a wonderful birthday.')
            ->line('May this year bring you joy, success, and continued connection with your fellow alumni.')
            ->action('Visit Alumni Community', url('/'))
            ->line('With warm regards from the ' . config('app.name') . ' family.');
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'birthday_shoutout'];
    }
}
