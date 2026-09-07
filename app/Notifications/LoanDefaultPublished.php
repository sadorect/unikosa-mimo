<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use App\Models\LoanDefaultPublication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Everyone named in a notice is told it exists, and told how to get it taken
 * down. Publishing without telling the people named would be indefensible.
 */
class LoanDefaultPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Loan $loan, public LoanDefaultPublication $publication)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $association = $this->publication->scope === 'association_forum';

        $data = [
            'name' => $notifiable->name,
            'borrower' => $this->loan->applicant->name,
            'reference' => $this->loan->reference,
            'outstanding' => $this->loan->currency.' '.number_format($this->loan->outstanding_amount),
            'audience' => $association ? 'the whole association' : 'your set',
            'expires' => $this->publication->expires_at?->toFormattedDayDateString() ?? '',
        ];

        if ($template = EmailTemplate::render('loan_default_published', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('View the Loan', route('loans.show', $this->loan));
        }

        $isBorrower = $notifiable->id === $this->loan->user_id;

        $mail = (new MailMessage)
            ->subject("A default notice for {$data['reference']} has been published")
            ->greeting("Hello {$data['name']},");

        $mail = $isBorrower
            ? $mail->line("A notice about your loan {$data['reference']} has been published to the members' forum for {$data['audience']}, as the terms you accepted provide.")
            : $mail->line("A notice about {$data['borrower']}'s loan {$data['reference']}, which you guaranteed, has been published to the members' forum for {$data['audience']}. It names you as a guarantor.");

        return $mail
            ->line("Outstanding balance: {$data['outstanding']}.")
            ->line('The notice is removed the moment the balance is cleared, by the borrower or by a guarantor, and expires on its own by '.$data['expires'].'.')
            ->line('It is visible only to members signed in to the platform, and carries no contact details.')
            ->action('View the Loan', route('loans.show', $this->loan));
    }
}
