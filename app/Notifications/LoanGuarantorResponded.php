<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use App\Models\LoanGuarantor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the applicant, either way — a decline is news they need as much as a yes. */
class LoanGuarantorResponded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Loan $loan, public LoanGuarantor $guarantor)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $accepted = $this->guarantor->status === 'accepted';

        $data = [
            'name' => $notifiable->name,
            'guarantor' => $this->guarantor->displayName(),
            'reference' => $this->loan->reference,
            'response' => $accepted ? 'accepted' : 'declined',
            'outstanding' => (string) max(0, $this->loan->guarantors_required - $this->loan->guarantors()->where('status', 'accepted')->count()),
        ];

        if ($template = EmailTemplate::render('loan_guarantor_responded', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('View Your Loan', route('loans.show', $this->loan));
        }

        $mail = (new MailMessage)
            ->subject("{$data['guarantor']} has {$data['response']} your guarantor request")
            ->greeting("Hello {$data['name']},");

        return $accepted
            ? $mail->line("{$data['guarantor']} has agreed to guarantee your loan {$data['reference']}.")
                ->line((int) $data['outstanding'] > 0
                    ? "You still need {$data['outstanding']} more."
                    : 'That is everyone you needed. The committee will now arrange disbursement.')
                ->action('View Your Loan', route('loans.show', $this->loan))
            : $mail->line("{$data['guarantor']} is unable to guarantee your loan {$data['reference']}.")
                ->line('You can ask someone else from your set.')
                ->action('Ask Another Member', route('loans.show', $this->loan));
    }
}
