<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRepaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Loan $loan, public LoanRepayment $repayment)
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
            'amount' => $this->loan->currency.' '.number_format($this->repayment->amount),
            'payer' => $this->repayment->payer?->name ?? $this->loan->applicant->name,
            'outstanding' => $this->loan->currency.' '.number_format($this->loan->outstanding_amount),
        ];

        if ($template = EmailTemplate::render('loan_repayment_confirmed', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('View the Loan', route('loans.show', $this->loan));
        }

        $mail = (new MailMessage)
            ->subject("Repayment confirmed on {$data['reference']}")
            ->greeting("Hello {$data['name']},");

        // Said plainly, because a borrower needs to know a guarantor has been
        // out of pocket on their behalf.
        $mail = $this->repayment->isOnBehalfOfBorrower() && $notifiable->id === $this->loan->user_id
            ? $mail->line("{$data['payer']}, one of your guarantors, has paid {$data['amount']} towards your loan, and the fund has confirmed it.")
            : $mail->line("A repayment of {$data['amount']} has been confirmed.");

        return $mail
            ->line("Outstanding balance: {$data['outstanding']}.")
            ->action('View the Loan', route('loans.show', $this->loan));
    }
}
