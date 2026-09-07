<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the fund's reviewers. Mail only — the bell row is written by
 * LoanNotifier so that it carries a working link.
 */
class LoanSubmittedForReview extends Notification implements ShouldQueue
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
            'amount' => $this->loan->currency.' '.number_format($this->loan->amount_requested),
            'fund' => $this->loan->program->name,
        ];

        if ($template = EmailTemplate::render('loan_submitted_for_review', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Review Application', route('loans.show', $this->loan));
        }

        return (new MailMessage)
            ->subject("New loan application: {$data['reference']}")
            ->greeting("Hello {$data['name']},")
            ->line("{$data['applicant']} has applied to the {$data['fund']} for {$data['amount']}.")
            ->line('The application is waiting for the welfare committee to review it.')
            ->action('Review Application', route('loans.show', $this->loan));
    }
}
