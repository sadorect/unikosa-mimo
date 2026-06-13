<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DuesReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $dueName,
        public int $amount,
        public string $currency,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $formattedAmount = number_format($this->amount / 100, 2);

        return (new MailMessage)
            ->subject('Dues Reminder: ' . $this->dueName)
            ->line('This is a friendly reminder about your outstanding dues.')
            ->line('Dues: ' . $this->dueName)
            ->line('Amount: ' . $this->currency . ' ' . $formattedAmount)
            ->action('Pay Now', url('/dashboard'))
            ->line('If you have already paid, please disregard this message.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'dues_reminder',
            'due_name' => $this->dueName,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'message' => "Reminder: {$this->dueName} — {$this->currency} " . number_format($this->amount / 100, 2),
        ];
    }
}
