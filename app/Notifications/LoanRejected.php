<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRejected extends Notification implements ShouldQueue
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
            'reason' => $this->loan->review_notes ?? '',
        ];

        if ($template = EmailTemplate::render('loan_rejected', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail;
        }

        return (new MailMessage)
            ->subject("An update on your loan application {$data['reference']}")
            ->greeting("Hello {$data['name']},")
            ->line('The welfare committee has reviewed your application and is unable to approve it at this time.')
            ->line($data['reason'] !== '' ? "Their note: {$data['reason']}" : 'You are welcome to speak to a committee member about it.');
    }
}
