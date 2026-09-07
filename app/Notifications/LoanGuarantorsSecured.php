<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the committee: this loan is ready to be released to the disburser. */
class LoanGuarantorsSecured extends Notification implements ShouldQueue
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
            'amount' => $this->loan->currency.' '.number_format((int) ($this->loan->amount_approved ?? $this->loan->amount_requested)),
            'guarantors' => $this->loan->guarantors()->where('status', 'accepted')->with('user')->get()
                ->map(fn ($guarantor) => $guarantor->displayName())->implode(', '),
        ];

        if ($template = EmailTemplate::render('loan_guarantors_secured', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Release to the Disburser', route('filament.admin.resources.loans.view', $this->loan));
        }

        return (new MailMessage)
            ->subject("Loan {$data['reference']} has its guarantors")
            ->greeting("Hello {$data['name']},")
            ->line("{$data['applicant']}'s loan for {$data['amount']} now has all the guarantors it needs.")
            ->line("Guarantors: {$data['guarantors']}.")
            ->line('It is ready to be released to the disburser.')
            ->action('Release to the Disburser', route('filament.admin.resources.loans.view', $this->loan));
    }
}
