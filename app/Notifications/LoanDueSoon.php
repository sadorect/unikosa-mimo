<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the borrower, ahead of the due date. Guarantors are not troubled yet. */
class LoanDueSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Loan $loan, public int $daysAway)
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
            'outstanding' => $this->loan->currency.' '.number_format($this->loan->outstanding_amount),
            'due' => $this->loan->due_at?->toFormattedDayDateString() ?? '',
            'days' => (string) $this->daysAway,
        ];

        if ($template = EmailTemplate::render('loan_due_soon', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Record a Repayment', route('loans.show', $this->loan));
        }

        return (new MailMessage)
            ->subject("Your loan {$data['reference']} is due in {$data['days']} days")
            ->greeting("Hello {$data['name']},")
            ->line("{$data['outstanding']} is outstanding on your loan, due {$data['due']}.")
            ->line('Once you have paid, record it here so the fund can confirm it.')
            ->action('Record a Repayment', route('loans.show', $this->loan));
    }
}
