<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the guarantors when the borrower has run past the due date: the nudge
 * step the workflow asks for. It offers both things a guarantor can actually
 * do — prompt the borrower, or pay towards the balance themselves.
 */
class LoanGuarantorNudge extends Notification implements ShouldQueue
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
            'grace' => $this->loan->grace_ends_at?->toFormattedDayDateString() ?? '',
        ];

        if ($template = EmailTemplate::render('loan_guarantor_nudge', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('View or Pay Towards the Loan', route('loans.show', $this->loan));
        }

        return (new MailMessage)
            ->subject("A loan you guaranteed is overdue — {$data['reference']}")
            ->greeting("Hello {$data['name']},")
            ->line("{$data['borrower']}'s loan, which you guaranteed, has passed its due date with {$data['outstanding']} outstanding.")
            ->line("Please prompt them to settle it before {$data['grace']}.")
            ->line('If it is still unpaid after that date, the loan is recorded as in default and a notice naming both of you may be published.')
            ->line('You can also pay towards the balance yourself if you would rather settle it directly.')
            ->action('View or Pay Towards the Loan', route('loans.show', $this->loan));
    }
}
