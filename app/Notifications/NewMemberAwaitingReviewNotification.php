<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a set's coordinators and to the general admins when someone signs up,
 * so an approval never sits unnoticed in the admin panel.
 */
class NewMemberAwaitingReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $applicant)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $setName = $this->applicant->graduatingSet?->name ?? 'no set specified';

        return (new MailMessage)
            ->subject('New member awaiting approval: ' . $this->applicant->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->applicant->name . ' has signed up and is waiting to be approved.')
            ->line('Set: ' . $setName)
            ->line('Email: ' . $this->applicant->email)
            ->action('Review this signup', url('/admin/users?tableFilters[status][value]=pending'))
            ->line('If you do not recognise this person as a member of your set, please reject the request.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'member_awaiting_review',
            'user_id' => $this->applicant->id,
            'message' => $this->applicant->name . ' has signed up and is waiting for approval.',
        ];
    }
}
