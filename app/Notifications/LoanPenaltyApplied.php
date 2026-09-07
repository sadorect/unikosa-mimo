<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanPenaltyApplied extends Notification implements ShouldQueue
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
            'penalty' => $this->loan->currency.' '.number_format((int) $this->loan->penalty_amount),
            'reason' => $this->loan->penalty_reason ?? '',
            'outstanding' => $this->loan->currency.' '.number_format($this->loan->outstanding_amount),
        ];

        if ($template = EmailTemplate::render('loan_penalty_applied', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('View the Loan', route('loans.show', $this->loan));
        }

        return (new MailMessage)
            ->subject("A penalty has been applied to loan {$data['reference']}")
            ->greeting("Hello {$data['name']},")
            ->line("The welfare committee has applied a penalty of {$data['penalty']} to your loan.")
            ->line($data['reason'] !== '' ? "Reason: {$data['reason']}" : '')
            ->line("Your outstanding balance is now {$data['outstanding']}.")
            ->action('View the Loan', route('loans.show', $this->loan));
    }
}
