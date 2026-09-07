<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the borrower, their guarantors and the fund's officers. It states the
 * cure — clearing the balance lifts everything, including any notice — because
 * a default that reads as final gives nobody a reason to pay.
 */
class LoanDefaulted extends Notification implements ShouldQueue
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
            'borrower' => $this->loan->applicant->name,
            'reference' => $this->loan->reference,
            'outstanding' => $this->loan->currency.' '.number_format($this->loan->outstanding_amount),
        ];

        if ($template = EmailTemplate::render('loan_defaulted', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('View the Loan', route('loans.show', $this->loan));
        }

        $isBorrower = $notifiable->id === $this->loan->user_id;

        $mail = (new MailMessage)
            ->subject("Loan {$data['reference']} is now in default")
            ->greeting("Hello {$data['name']},");

        $mail = $isBorrower
            ? $mail->line("Your loan {$data['reference']} has passed its grace period with {$data['outstanding']} outstanding and is now recorded as in default.")
            : $mail->line("{$data['borrower']}'s loan {$data['reference']}, which you guaranteed, is now recorded as in default with {$data['outstanding']} outstanding.");

        return $mail
            ->line('A notice may now be published to the association naming the borrower and their guarantors.')
            ->line('Clearing the balance closes the loan and removes any notice immediately.')
            ->action('View the Loan', route('loans.show', $this->loan));
    }
}
