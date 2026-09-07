<?php

namespace Database\Seeders;

use App\Models\LoanProgram;
use App\Models\LoanTerm;
use App\Models\Set;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Seeder;

/**
 * The 99 SET welfare loan fund — the first fund on the platform.
 *
 * Seeded in `draft` with the module switch off, so running this changes
 * nothing a member can see. An officer activates the fund from Filament once
 * the association has agreed the numbers and the terms text.
 */
class LoanProgramSeeder extends Seeder
{
    public function run(): void
    {
        $set = Set::where('name', '99 SET')->first()
            ?? Set::where('year', 1999)->first();

        // A fresh test database has no sets; skip rather than fabricate one.
        if (! $set) {
            return;
        }

        $program = LoanProgram::updateOrCreate(
            ['set_id' => $set->id, 'slug' => '99-set-welfare-loan'],
            [
                'name' => '99 SET Welfare Loan',
                'status' => 'draft',
                'currency' => 'NGN',
                'min_loan_amount' => 20000,
                'max_loan_amount' => 500000,
                'default_tenure_months' => 3,
                'max_tenure_months' => 3,
                'interest_rate_bps' => 500,
                'interest_method' => 'flat_per_tenure',
                'required_guarantors' => 2,
                'grace_days' => 7,
                'reminder_days_before' => [14, 7, 1],
                'max_active_loans_per_member' => 1,
                'penalty_enabled' => false,
                'forum_publication_enabled' => true,
                'public_publication_allowed' => false,
                'publication_expiry_days' => 90,
            ]
        );

        LoanTerm::updateOrCreate(
            ['loan_program_id' => $program->id, 'version' => 'v1'],
            ['body' => HtmlSanitizer::clean($this->termsDraft()), 'published_at' => null]
        );
    }

    /**
     * Draft terms for the association to edit and publish. Left unpublished on
     * purpose: no member can apply against terms nobody has approved.
     *
     * The publication sanction is stated explicitly here because it is the
     * consent an applicant and their guarantors give at application time — if
     * this paragraph is removed, the forum and public notices lose their basis.
     */
    protected function termsDraft(): string
    {
        return <<<'HTML'
            <h2>Terms of operation</h2>
            <p>This fund is operated by the 99 SET for the benefit of its own members. It is a members' welfare scheme, not a commercial lender. Money moves by direct bank transfer between the fund's officers and the member; the platform records the transaction but never holds or moves funds.</p>

            <h3>Eligibility</h3>
            <ul>
                <li>You must be an approved member of the 99 SET on this platform.</li>
                <li>You may hold only one active loan at a time.</li>
                <li>Applications are reviewed by the set's welfare committee, whose decision is final.</li>
            </ul>

            <h3>Amount, interest and repayment</h3>
            <ul>
                <li>The committee may approve less than the amount requested.</li>
                <li>Interest is a flat charge over the whole loan period, fixed at disbursement and never recalculated.</li>
                <li>The repayment period is three months, counted from the date the fund disburses the money.</li>
                <li>Part-payments are accepted. Record each payment with proof; only payments a fund treasurer has confirmed reduce your balance.</li>
            </ul>

            <h3>Guarantors</h3>
            <ul>
                <li>Two fellow members must accept guarantorship before the loan is released.</li>
                <li>Guarantors are shown these terms, including the publication clause below, before they accept.</li>
                <li>If a loan falls overdue, guarantors are notified and asked to prompt the borrower.</li>
            </ul>

            <h3>Late repayment and publication</h3>
            <ul>
                <li>If any balance is outstanding on the due date, the loan is marked overdue and the borrower and guarantors are notified.</li>
                <li>A grace period of seven days then applies.</li>
                <li>If a balance remains after the grace period, the loan is recorded as in default, and a notice naming the borrower, their set, the loan reference, the amount and the guarantors may be published to the association's members-only forum.</li>
                <li>In a persistent case, and only by decision of the association's administrators, that notice may be published on a public page of this website. Contact details, dates of birth and other personal information are never published.</li>
                <li>Any such notice is removed as soon as the balance is cleared, and in any case expires automatically.</li>
            </ul>

            <p>By applying, you confirm that you have read and accepted these terms, including the publication clause.</p>
            HTML;
    }
}
