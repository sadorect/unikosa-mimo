<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the committee and the applicant. The receipt-of-notice step exists so
 * that "the disburser never saw it" and "the disburser has not paid yet" are
 * distinguishable states rather than the same silence.
 */
class LoanDisbursementAcknowledged extends Notification implements ShouldQueue
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
            'disburser' => $this->loan->disburser?->name ?? 'The disburser',
            'amount' => $this->loan->currency.' '.number_format((int) $this->loan->amount_approved),
        ];

        if ($template = EmailTemplate::render('loan_disbursement_acknowledged', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail;
        }

        return (new MailMessage)
            ->subject("{$data['disburser']} has the disbursement notice for {$data['reference']}")
            ->greeting("Hello {$data['name']},")
            ->line("{$data['disburser']} has confirmed receiving the notice to disburse {$data['amount']}.")
            ->line('You will be notified again once the transfer has actually been made.');
    }
}
