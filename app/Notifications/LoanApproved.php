<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Approval and the request for guarantors are one message: an approved loan
 * that nobody guarantees goes nowhere, so telling the applicant what to do
 * next matters more than telling them the decision.
 */
class LoanApproved extends Notification implements ShouldQueue
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
            'amount' => $this->loan->currency.' '.number_format((int) $this->loan->amount_approved),
            'requested' => $this->loan->currency.' '.number_format($this->loan->amount_requested),
            'guarantors' => (string) $this->loan->guarantors_required,
            'tenure' => (string) $this->loan->tenure_months,
        ];

        if ($template = EmailTemplate::render('loan_approved', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Nominate Guarantors', route('loans.show', $this->loan));
        }

        $mail = (new MailMessage)
            ->subject("Your loan {$data['reference']} has been approved")
            ->greeting("Hello {$data['name']},")
            ->line("The welfare committee has approved {$data['amount']} over {$data['tenure']} months.");

        if ((int) $this->loan->amount_approved !== (int) $this->loan->amount_requested) {
            $mail->line("This is less than the {$data['requested']} you asked for.");
        }

        return $mail
            ->line("Before the money can be released, {$data['guarantors']} fellow members must accept guarantorship.")
            ->action('Nominate Guarantors', route('loans.show', $this->loan));
    }
}
