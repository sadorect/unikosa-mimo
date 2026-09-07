<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanProgram;
use App\Models\LoanTerm;
use App\Models\Setting;
use App\Models\UnikosaNotification;
use App\Models\User;
use App\Notifications\LoanSubmittedForReview;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanApplicationTest extends TestCase
{
    use RefreshDatabase;

    private LoanProgram $program;
    private LoanTerm $term;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Setting::set('loans_enabled', 'true', 'modules');

        $this->program = LoanProgram::factory()->create();
        $this->term = LoanTerm::create([
            'loan_program_id' => $this->program->id,
            'version' => 'v1',
            'body' => '<p>Terms, including publication of defaults.</p>',
            'published_at' => now(),
        ]);

        $this->member = User::factory()->create([
            'status' => 'approved',
            'email_verified_at' => now(),
            'graduating_set_id' => $this->program->set_id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'amount_requested' => 100000,
            'tenure_months' => 3,
            'purpose' => 'School fees for the new term.',
            'agree_terms' => true,
        ], $overrides);
    }

    public function test_a_member_can_apply_and_the_agreement_is_snapshotted(): void
    {
        $this->actingAs($this->member)
            ->post('/loans', $this->payload())
            ->assertRedirect();

        $loan = Loan::first();

        $this->assertSame('submitted', $loan->status);
        $this->assertSame($this->term->id, $loan->loan_term_id);
        $this->assertNotNull($loan->terms_agreed_at);
        $this->assertNotNull($loan->terms_agreed_ip);
        $this->assertMatchesRegularExpression('/^LN-\d{4}-[A-Z0-9]{8}$/', $loan->reference);
        $this->assertSame(500, $loan->interest_rate_bps, 'The rate is snapshotted from the fund.');
        $this->assertSame(2, $loan->guarantors_required);
    }

    public function test_the_application_cannot_be_submitted_without_accepting_the_terms(): void
    {
        $this->actingAs($this->member)
            ->post('/loans', $this->payload(['agree_terms' => false]))
            ->assertSessionHasErrors('agree_terms');

        $this->assertSame(0, Loan::count());
    }

    public function test_publishing_new_terms_does_not_move_an_existing_application(): void
    {
        $this->actingAs($this->member)->post('/loans', $this->payload());
        $loan = Loan::first();

        LoanTerm::create([
            'loan_program_id' => $this->program->id,
            'version' => 'v2',
            'body' => '<p>Revised terms.</p>',
            'published_at' => now()->addMinute(),
        ]);

        $this->assertSame($this->term->id, $loan->fresh()->loan_term_id);
    }

    public function test_every_reviewer_is_notified_with_a_working_link(): void
    {
        Notification::fake();

        $reviewer = User::factory()->create(['status' => 'approved', 'graduating_set_id' => $this->program->set_id]);
        $other = User::factory()->create(['status' => 'approved', 'graduating_set_id' => $this->program->set_id]);
        $this->program->officers()->attach($reviewer->id, ['role' => 'reviewer']);
        $this->program->officers()->attach($other->id, ['role' => 'disburser']);

        $this->actingAs($this->member)->post('/loans', $this->payload());

        Notification::assertSentTo($reviewer, LoanSubmittedForReview::class);
        Notification::assertNotSentTo($other, LoanSubmittedForReview::class);

        // The regression test for the database-channel trap: a bell row must
        // carry a url, or the notification links nowhere.
        $bell = UnikosaNotification::where('user_id', $reviewer->id)->first();
        $this->assertNotNull($bell);
        $this->assertSame('loan_submitted', $bell->data['type']);
        $this->assertNotNull($bell->data['url'] ?? null);
        $this->assertStringContainsString('/loans/', $bell->data['url']);
    }

    public function test_an_amount_outside_the_funds_range_is_refused(): void
    {
        $this->actingAs($this->member)
            ->post('/loans', $this->payload(['amount_requested' => 1000000]))
            ->assertSessionHasErrors('amount_requested');

        $this->assertSame(0, Loan::count());
    }

    public function test_a_member_of_another_set_cannot_apply(): void
    {
        $outsider = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        $this->actingAs($outsider)
            ->post('/loans', $this->payload())
            ->assertNotFound();
    }

    public function test_an_applicant_can_withdraw_before_disbursement(): void
    {
        $this->actingAs($this->member)->post('/loans', $this->payload());
        $loan = Loan::first();

        $this->actingAs($this->member)
            ->delete("/loans/{$loan->id}")
            ->assertRedirect(route('loans.index'));

        $this->assertSame('withdrawn', $loan->fresh()->status);
    }

    public function test_every_member_facing_page_renders(): void
    {
        $this->actingAs($this->member)->post('/loans', $this->payload());
        $loan = Loan::first();

        $this->actingAs($this->member)->get('/loans')->assertOk();
        $this->actingAs($this->member)->get('/loans/terms')->assertOk();
        $this->actingAs($this->member)->get("/loans/{$loan->id}")->assertOk();

        // /loans/apply must not bind 'apply' as a loan id.
        $this->actingAs($this->member)->get('/loans/apply')->assertOk();
    }

    public function test_the_module_switch_closes_the_whole_module(): void
    {
        Setting::set('loans_enabled', 'false', 'modules');

        $this->actingAs($this->member)->get('/loans')->assertStatus(503);
        $this->actingAs($this->member)->post('/loans', $this->payload())->assertStatus(503);
    }
}
