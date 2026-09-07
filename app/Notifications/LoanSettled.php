<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the borrower and everyone who stood for them. For a guarantor this is
 * the message that says their exposure has ended, so it says so explicitly.
 */
class LoanSettled extends Notification implements ShouldQueue
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
            'total' => $this->loan->currency.' '.number_format((int) $this->loan->total_repayable),
        ];

        if ($template = EmailTemplate::render('loan_settled', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Download Your Statement', route('loans.statement', $this->loan));
        }

        $isBorrower = $notifiable->id === $this->loan->user_id;

        $mail = (new MailMessage)
            ->subject("Loan {$data['reference']} is fully repaid")
            ->greeting("Hello {$data['name']},");

        return $isBorrower
            ? $mail->line("Your loan {$data['reference']} is fully repaid and now closed.")
                ->line('Thank you for keeping the fund working for the next member who needs it.')
                ->action('Download Your Statement', route('loans.statement', $this->loan))
            : $mail->line("Loan {$data['reference']}, which you guaranteed, is fully repaid.")
                ->line('Your obligation as a guarantor has ended.');
    }
}
