<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the fund's treasurers: somebody says they have paid. */
class LoanRepaymentRecorded extends Notification implements ShouldQueue
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
            'borrower' => $this->loan->applicant->name,
            'payer' => $this->repayment->payer?->name ?? $this->loan->applicant->name,
            'amount' => $this->loan->currency.' '.number_format($this->repayment->amount),
            'paid_on' => $this->repayment->paid_on?->toFormattedDayDateString() ?? '',
        ];

        if ($template = EmailTemplate::render('loan_repayment_recorded', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Confirm the Repayment', route('filament.admin.resources.loan-repayments.index'));
        }

        $mail = (new MailMessage)
            ->subject("Repayment to confirm: {$data['amount']} on {$data['reference']}")
            ->greeting("Hello {$data['name']},");

        $mail = $this->repayment->isOnBehalfOfBorrower()
            ? $mail->line("{$data['payer']}, a guarantor, has paid {$data['amount']} towards {$data['borrower']}'s loan.")
            : $mail->line("{$data['payer']} has recorded a {$data['amount']} repayment.");

        return $mail
            ->line("Payment date: {$data['paid_on']}.")
            ->line('It does not reduce the balance until you confirm the money arrived.')
            ->action('Confirm the Repayment', route('filament.admin.resources.loan-repayments.index'));
    }
}
