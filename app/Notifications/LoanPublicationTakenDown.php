<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanPublicationTakenDown extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Loan $loan, public string $reason)
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
            'reason' => $this->reason,
        ];

        if ($template = EmailTemplate::render('loan_publication_taken_down', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail;
        }

        return (new MailMessage)
            ->subject("The default notice for {$data['reference']} has been removed")
            ->greeting("Hello {$data['name']},")
            ->line("The default notice for loan {$data['reference']} has been taken down.")
            ->line("Reason: {$data['reason']}.");
    }
}
