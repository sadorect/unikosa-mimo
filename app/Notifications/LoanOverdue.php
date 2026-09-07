<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the borrower once the due date has passed. It names the grace deadline
 * and what happens after it, because the borrower's best chance of avoiding a
 * default notice is knowing the date it lands.
 */
class LoanOverdue extends Notification implements ShouldQueue
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
            'outstanding' => $this->loan->currency.' '.number_format($this->loan->outstanding_amount),
            'due' => $this->loan->due_at?->toFormattedDayDateString() ?? '',
            'grace' => $this->loan->grace_ends_at?->toFormattedDayDateString() ?? '',
        ];

        if ($template = EmailTemplate::render('loan_overdue', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Record a Repayment', route('loans.show', $this->loan));
        }

        return (new MailMessage)
            ->subject("Your loan {$data['reference']} is overdue")
            ->greeting("Hello {$data['name']},")
            ->line("{$data['outstanding']} was due on {$data['due']} and is still outstanding.")
            ->line("You have until {$data['grace']} to clear it. After that the loan is recorded as in default, and a notice naming you and your guarantors may be published.")
            ->line('Your guarantors have been notified.')
            ->action('Record a Repayment', route('loans.show', $this->loan));
    }
}
