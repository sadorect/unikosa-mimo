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
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(['key' => $template['key']], $template);
        }
    }
}
