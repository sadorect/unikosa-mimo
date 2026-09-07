<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the applicant and their guarantors — this is the message that starts the
 * payback clock, so it states the due date rather than leaving it to be
 * looked up.
 */
class LoanDisbursed extends Notification implements ShouldQueue
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
            'amount' => $this->loan->currency.' '.number_format((int) $this->loan->disbursed_amount),
            'total' => $this->loan->currency.' '.number_format((int) $this->loan->total_repayable),
            'due' => $this->loan->due_at?->toFormattedDayDateString() ?? '',
            'grace' => $this->loan->grace_ends_at?->toFormattedDayDateString() ?? '',
        ];

        if ($template = EmailTemplate::render('loan_disbursed', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('View the Loan', route('loans.show', $this->loan));
        }

        $isApplicant = $notifiable->id === $this->loan->user_id;

        $mail = (new MailMessage)
            ->subject("Loan {$data['reference']} has been disbursed")
            ->greeting("Hello {$data['name']},");

        $mail = $isApplicant
            ? $mail->line("{$data['amount']} has been released to you.")
            : $mail->line("{$data['amount']} has been released to {$data['applicant']}, whose loan you are guaranteeing.");

        return $mail
            ->line("Total repayable: {$data['total']}, due by {$data['due']}.")
            ->line("If it is unpaid after {$data['grace']}, the loan is recorded as in default and a notice may be published.")
            ->action('View the Loan', route('loans.show', $this->loan));
    }
}
