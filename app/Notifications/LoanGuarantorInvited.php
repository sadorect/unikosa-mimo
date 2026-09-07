<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Loan;
use App\Models\LoanGuarantor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The invitation is where a guarantor takes on the publication risk, so the
 * mail says so plainly rather than leaving it in the terms they may not open.
 */
class LoanGuarantorInvited extends Notification implements ShouldQueue
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
        $data = [
            'name' => $notifiable->name,
            'applicant' => $this->loan->applicant->name,
            'reference' => $this->loan->reference,
            'amount' => $this->loan->currency.' '.number_format((int) ($this->loan->amount_approved ?? $this->loan->amount_requested)),
            'expires' => $this->guarantor->expires_at?->toFormattedDayDateString() ?? '',
        ];

        if ($template = EmailTemplate::render('loan_guarantor_invited', $data)) {
            $mail = (new MailMessage)->subject($template['subject']);
            foreach (preg_split('/\r\n|\r|\n/', $template['body']) as $line) {
                $mail->line($line);
            }

            return $mail->action('Respond to the Request', route('loans.guarantorships'));
        }

        return (new MailMessage)
            ->subject("{$data['applicant']} has asked you to guarantee a loan")
            ->greeting("Hello {$data['name']},")
            ->line("{$data['applicant']} has applied for a {$data['amount']} loan ({$data['reference']}) and has named you as a guarantor.")
            ->line('If you accept, your name is recorded against the loan. Should it fall into default, you will be asked to prompt the borrower, and your name may appear alongside theirs in a default notice.')
            ->line($data['expires'] !== '' ? "Please respond by {$data['expires']}." : 'Please respond soon.')
            ->action('Respond to the Request', route('loans.guarantorships'));
    }
}
