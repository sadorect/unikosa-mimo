<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the disburser: the committee has released this loan for payment. */
class LoanDisbursementRequest extends Notification implements ShouldQueue
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
            'applicant' => $this->loan->applicant->name,
            'reference' => $this->loan->reference,
            'amount' => $this->loan->currency.' '.number_format((int) $this->loan->amount_approved),
        ];

        if ($template = EmailTemplate::render('loan_disbursement_request', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Acknowledge and Disburse', route('filament.admin.resources.loans.view', $this->loan));
        }

        return (new MailMessage)
            ->subject("Please disburse {$data['amount']} — {$data['reference']}")
            ->greeting("Hello {$data['name']},")
            ->line("The welfare committee has approved {$data['amount']} for {$data['applicant']} and released it to you for payment.")
            ->line('Please confirm you have received this notice, then record the transfer once it has been made.')
            ->action('Acknowledge and Disburse', route('filament.admin.resources.loans.view', $this->loan));
    }
}
