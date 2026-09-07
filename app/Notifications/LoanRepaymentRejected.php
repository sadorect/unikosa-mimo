<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRepaymentRejected extends Notification implements ShouldQueue
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
            'reason' => $this->repayment->rejection_reason ?? '',
        ];

        if ($template = EmailTemplate::render('loan_repayment_rejected', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('View the Loan', route('loans.show', $this->loan));
        }

        return (new MailMessage)
            ->subject("A repayment on {$data['reference']} could not be confirmed")
            ->greeting("Hello {$data['name']},")
            ->line("The fund could not match the {$data['amount']} repayment you recorded to a payment it received.")
            ->line($data['reason'] !== '' ? "Their note: {$data['reason']}" : 'Please check the details and record it again.')
            ->line('The balance on the loan is unchanged.')
            ->action('View the Loan', route('loans.show', $this->loan));
    }
}
