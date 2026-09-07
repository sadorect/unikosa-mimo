<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanChangesRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Loan $loan)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = [
            'name' => $notifiable->name,
            'reference' => $this->loan->reference,
            'notes' => $this->loan->review_notes ?? '',
        ];

        if ($template = EmailTemplate::render('loan_changes_requested', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Update Your Application', route('loans.show', $this->loan));
        }

        return (new MailMessage)
            ->subject("Your loan application {$data['reference']} needs changes")
            ->greeting("Hello {$data['name']},")
            ->line('The welfare committee has asked for some changes before they can review your application.')
            ->line($data['notes'] !== '' ? "What they asked for: {$data['notes']}" : 'Please review your application and resubmit it.')
            ->action('Update Your Application', route('loans.show', $this->loan));
    }
}
