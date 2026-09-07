<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'registration_pending',
                'name' => 'Registration Received / Pending Approval',
                'subject' => 'We have received your {{ site_name }} registration',
                'body' => "Hello {{ name }},\n"
                    . "Thank you for registering. Your account has been created and is now awaiting review by one of our administrators.\n"
                    . "You will not be able to access member areas until that review is complete.\n"
                    . "We will email you as soon as a decision has been made - whether your account is approved or declined.\n"
                    . "No further action is needed from you in the meantime.",
                'variables' => ['site_name', 'name'],
            ],
            [
                'key' => 'registration_declined',
                'name' => 'Registration Declined',
                'subject' => 'An update on your {{ site_name }} registration',
                'body' => "Hello {{ name }},\n"
                    . "Thank you for your interest in joining our alumni community.\n"
                    . "Your registration has been reviewed and we are unable to approve your account at this time.\n"
                    . "If you believe this was a mistake, please reply to this message or contact us so we can look into it.",
                'variables' => ['site_name', 'name'],
            ],
            [
                'key' => 'welcome',
                'name' => 'Welcome / Profile Approved',
                'subject' => 'Your {{ site_name }} profile has been approved!',
                'body' => "Welcome, {{ name }}!\n"
                    . "Your alumni profile has been approved. You now have full access to the platform.\n"
                    . "Thank you for joining our alumni community!",
                'variables' => ['site_name', 'name'],
            ],
            [
                'key' => 'dues_reminder',
                'name' => 'Dues Reminder',
                'subject' => 'Dues Reminder: {{ due_name }}',
                'body' => "This is a friendly reminder about your outstanding dues.\n"
                    . "Dues: {{ due_name }}\n"
                    . "Amount: {{ currency }} {{ amount }}\n"
                    . "If you have already paid, please disregard this message.",
                'variables' => ['site_name', 'due_name', 'currency', 'amount'],
            ],
            [
                'key' => 'event_rsvp_confirmation',
                'name' => 'Event RSVP Confirmation',
                'subject' => 'You are confirmed for {{ event_title }}',
                'body' => "Hi {{ name }},\n"
                    . "Your RSVP for {{ event_title }} on {{ event_date }} is confirmed.\n"
                    . "We look forward to seeing you there!",
                'variables' => ['site_name', 'name', 'event_title', 'event_date'],
            ],
            [
                'key' => 'loan_submitted_for_review',
                'name' => 'Loan: New Application for Review',
                'subject' => 'New loan application: {{ reference }}',
                'body' => "Hello {{ name }},\n"
                    . "{{ applicant }} has applied to the {{ fund }} for {{ amount }}.\n"
                    . "The application is waiting for the welfare committee to review it.",
                'variables' => ['site_name', 'name', 'applicant', 'reference', 'amount', 'fund'],
            ],
            [
                'key' => 'loan_changes_requested',
                'name' => 'Loan: Changes Requested',
                'subject' => 'Your loan application {{ reference }} needs changes',
                'body' => "Hello {{ name }},\n"
                    . "The welfare committee has asked for some changes before they can review your application.\n"
                    . "What they asked for: {{ notes }}\n"
                    . "Update your application and resubmit it when you are ready.",
                'variables' => ['site_name', 'name', 'reference', 'notes'],
            ],
            [
                'key' => 'loan_approved',
                'name' => 'Loan: Approved',
                'subject' => 'Your loan {{ reference }} has been approved',
                'body' => "Hello {{ name }},\n"
                    . "The welfare committee has approved {{ amount }} over {{ tenure }} months.\n"
                    . "Before the money can be released, {{ guarantors }} fellow members must accept guarantorship.\n"
                    . "Open your loan to nominate them.",
                'variables' => ['site_name', 'name', 'reference', 'amount', 'requested', 'tenure', 'guarantors'],
            ],
            [
                'key' => 'loan_rejected',
                'name' => 'Loan: Not Approved',
                'subject' => 'An update on your loan application {{ reference }}',
                'body' => "Hello {{ name }},\n"
                    . "The welfare committee has reviewed your application and is unable to approve it at this time.\n"
                    . "Their note: {{ reason }}\n"
                    . "You are welcome to speak to a committee member about it.",
                'variables' => ['site_name', 'name', 'reference', 'reason'],
            ],
            [
                'key' => 'loan_guarantor_invited',
                'name' => 'Loan: Guarantor Request',
                'subject' => '{{ applicant }} has asked you to guarantee a loan',
                'body' => "Hello {{ name }},\n"
                    . "{{ applicant }} has applied for a {{ amount }} loan ({{ reference }}) and has named you as a guarantor.\n"
                    . "If you accept, your name is recorded against the loan. Should it fall into default, you will be asked to prompt the borrower, and your name may appear alongside theirs in a default notice.\n"
                    . "Please respond by {{ expires }}.",
                'variables' => ['site_name', 'name', 'applicant', 'reference', 'amount', 'expires'],
            ],
            [
                'key' => 'loan_guarantor_responded',
                'name' => 'Loan: Guarantor Answered',
                'subject' => '{{ guarantor }} has {{ response }} your guarantor request',
                'body' => "Hello {{ name }},\n"
                    . "{{ guarantor }} has {{ response }} your request to guarantee loan {{ reference }}.\n"
                    . "Guarantors still needed: {{ outstanding }}.",
                'variables' => ['site_name', 'name', 'guarantor', 'reference', 'response', 'outstanding'],
            ],
            [
                'key' => 'loan_guarantors_secured',
                'name' => 'Loan: Guarantors Secured',
                'subject' => 'Loan {{ reference }} has its guarantors',
                'body' => "Hello {{ name }},\n"
                    . "{{ applicant }}'s loan for {{ amount }} now has all the guarantors it needs.\n"
                    . "Guarantors: {{ guarantors }}.\n"
                    . "It is ready to be released to the disburser.",
                'variables' => ['site_name', 'name', 'applicant', 'reference', 'amount', 'guarantors'],
            ],
            [
                'key' => 'loan_disbursement_request',
                'name' => 'Loan: Disbursement Requested',
                'subject' => 'Please disburse {{ amount }} - {{ reference }}',
                'body' => "Hello {{ name }},\n"
                    . "The welfare committee has approved {{ amount }} for {{ applicant }} and released it to you for payment.\n"
                    . "Please confirm you have received this notice, then record the transfer once it has been made.",
                'variables' => ['site_name', 'name', 'applicant', 'reference', 'amount'],
            ],
            [
                'key' => 'loan_disbursement_acknowledged',
                'name' => 'Loan: Disbursement Notice Acknowledged',
                'subject' => '{{ disburser }} has the disbursement notice for {{ reference }}',
                'body' => "Hello {{ name }},\n"
                    . "{{ disburser }} has confirmed receiving the notice to disburse {{ amount }}.\n"
                    . "You will be notified again once the transfer has actually been made.",
                'variables' => ['site_name', 'name', 'disburser', 'reference', 'amount'],
            ],
            [
                'key' => 'loan_disbursed',
                'name' => 'Loan: Disbursed',
                'subject' => 'Loan {{ reference }} has been disbursed',
                'body' => "Hello {{ name }},\n"
                    . "{{ amount }} has been released against loan {{ reference }} for {{ applicant }}.\n"
                    . "Total repayable: {{ total }}, due by {{ due }}.\n"
                    . "If it is unpaid after {{ grace }}, the loan is recorded as in default and a notice may be published.",
                'variables' => ['site_name', 'name', 'applicant', 'reference', 'amount', 'total', 'due', 'grace'],
            ],
            [
                'key' => 'loan_repayment_recorded',
                'name' => 'Loan: Repayment Awaiting Confirmation',
                'subject' => 'Repayment to confirm: {{ amount }} on {{ reference }}',
                'body' => "Hello {{ name }},\n"
                    . "{{ payer }} has recorded a {{ amount }} repayment against {{ borrower }}'s loan {{ reference }}.\n"
                    . "Payment date: {{ paid_on }}.\n"
                    . "It does not reduce the balance until you confirm the money arrived.",
                'variables' => ['site_name', 'name', 'reference', 'borrower', 'payer', 'amount', 'paid_on'],
            ],
            [
                'key' => 'loan_repayment_confirmed',
                'name' => 'Loan: Repayment Confirmed',
                'subject' => 'Repayment confirmed on {{ reference }}',
                'body' => "Hello {{ name }},\n"
                    . "A repayment of {{ amount }} from {{ payer }} has been confirmed against loan {{ reference }}.\n"
                    . "Outstanding balance: {{ outstanding }}.",
                'variables' => ['site_name', 'name', 'reference', 'amount', 'payer', 'outstanding'],
            ],
            [
                'key' => 'loan_repayment_rejected',
                'name' => 'Loan: Repayment Not Confirmed',
                'subject' => 'A repayment on {{ reference }} could not be confirmed',
                'body' => "Hello {{ name }},\n"
                    . "The fund could not match the {{ amount }} repayment you recorded to a payment it received.\n"
                    . "Their note: {{ reason }}\n"
                    . "The balance on the loan is unchanged.",
                'variables' => ['site_name', 'name', 'reference', 'amount', 'reason'],
            ],
            [
                'key' => 'loan_settled',
                'name' => 'Loan: Settled',
                'subject' => 'Loan {{ reference }} is fully repaid',
                'body' => "Hello {{ name }},\n"
                    . "Loan {{ reference }} is fully repaid and now closed. Total repaid: {{ total }}.\n"
                    . "If you guaranteed this loan, your obligation has ended.",
                'variables' => ['site_name', 'name', 'reference', 'total'],
            ],
            [
                'key' => 'loan_due_soon',
                'name' => 'Loan: Due Soon',
                'subject' => 'Your loan {{ reference }} is due in {{ days }} days',
                'body' => "Hello {{ name }},\n"
                    . "{{ outstanding }} is outstanding on your loan, due {{ due }}.\n"
                    . "Once you have paid, record it on the platform so the fund can confirm it.",
                'variables' => ['site_name', 'name', 'reference', 'outstanding', 'due', 'days'],
            ],
            [
                'key' => 'loan_overdue',
                'name' => 'Loan: Overdue',
                'subject' => 'Your loan {{ reference }} is overdue',
                'body' => "Hello {{ name }},\n"
                    . "{{ outstanding }} was due on {{ due }} and is still outstanding.\n"
                    . "You have until {{ grace }} to clear it. After that the loan is recorded as in default, and a notice naming you and your guarantors may be published.\n"
                    . "Your guarantors have been notified.",
                'variables' => ['site_name', 'name', 'reference', 'outstanding', 'due', 'grace'],
            ],
            [
                'key' => 'loan_guarantor_nudge',
                'name' => 'Loan: Guarantor Nudge',
                'subject' => 'A loan you guaranteed is overdue - {{ reference }}',
                'body' => "Hello {{ name }},\n"
                    . "{{ borrower }}'s loan, which you guaranteed, has passed its due date with {{ outstanding }} outstanding.\n"
                    . "Please prompt them to settle it before {{ grace }}.\n"
                    . "If it is still unpaid after that date, the loan is recorded as in default and a notice naming both of you may be published.\n"
                    . "You can also pay towards the balance yourself if you would rather settle it directly.",
                'variables' => ['site_name', 'name', 'borrower', 'reference', 'outstanding', 'grace'],
            ],
            [
                'key' => 'loan_defaulted',
                'name' => 'Loan: In Default',
                'subject' => 'Loan {{ reference }} is now in default',
                'body' => "Hello {{ name }},\n"
                    . "{{ borrower }}'s loan {{ reference }} has passed its grace period with {{ outstanding }} outstanding and is now recorded as in default.\n"
                    . "A notice may now be published to the association naming the borrower and their guarantors.\n"
                    . "Clearing the balance closes the loan and removes any notice immediately.",
                'variables' => ['site_name', 'name', 'borrower', 'reference', 'outstanding'],
            ],
            [
                'key' => 'loan_default_published',
                'name' => 'Loan: Default Notice Published',
                'subject' => 'A default notice for {{ reference }} has been published',
                'body' => "Hello {{ name }},\n"
                    . "A notice about loan {{ reference }} has been published to the members' forum for {{ audience }}, as the terms accepted at application provide.\n"
                    . "Outstanding balance: {{ outstanding }}.\n"
                    . "The notice is removed the moment the balance is cleared, by the borrower or by a guarantor, and expires on its own by {{ expires }}.\n"
                    . "It is visible only to members signed in to the platform, and carries no contact details.",
                'variables' => ['site_name', 'name', 'borrower', 'reference', 'outstanding', 'audience', 'expires'],
            ],
            [
                'key' => 'loan_publication_taken_down',
                'name' => 'Loan: Default Notice Removed',
                'subject' => 'The default notice for {{ reference }} has been removed',
                'body' => "Hello {{ name }},\n"
                    . "The default notice for loan {{ reference }} has been taken down.\n"
                    . "Reason: {{ reason }}.",
                'variables' => ['site_name', 'name', 'reference', 'reason'],
            ],
            [
                'key' => 'loan_penalty_applied',
                'name' => 'Loan: Penalty Applied',
                'subject' => 'A penalty has been applied to loan {{ reference }}',
                'body' => "Hello {{ name }},\n"
                    . "The welfare committee has applied a penalty of {{ penalty }} to your loan.\n"
                    . "Reason: {{ reason }}\n"
                    . "Your outstanding balance is now {{ outstanding }}.",
                'variables' => ['site_name', 'name', 'reference', 'penalty', 'reason', 'outstanding'],
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(['key' => $template['key']], $template);
        }
    }
}
