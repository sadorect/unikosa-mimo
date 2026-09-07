<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanGuarantor;
use App\Models\LoanProgram;
use App\Models\LoanTerm;
use App\Models\Setting;
use App\Models\UnikosaNotification;
use App\Models\User;
use App\Notifications\LoanGuarantorInvited;
use App\Notifications\LoanGuarantorsSecured;
use App\Services\LoanGuarantorService;
use App\Services\LoanStateMachine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanGuarantorTest extends TestCase
{
    use RefreshDatabase;

    private LoanProgram $program;
    private User $member;
    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Setting::set('loans_enabled', 'true', 'modules');

        $this->program = LoanProgram::factory()->create();
        LoanTerm::create([
            'loan_program_id' => $this->program->id,
            'version' => 'v1',
            'body' => '<p>Terms.</p>',
            'published_at' => now(),
        ]);

        $this->member = $this->setMember();

        $this->loan = Loan::factory()->for($this->program, 'program')->create([
            'set_id' => $this->program->set_id,
            'user_id' => $this->member->id,
            'status' => 'guarantors_pending',
            'amount_approved' => 100000,
            'tenure_months' => 3,
            'guarantors_required' => 2,
        ]);
    }

    private function setMember(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'approved',
            'email_verified_at' => now(),
            'graduating_set_id' => $this->program->set_id,
        ], $overrides));
    }

    private function service(): LoanGuarantorService
    {
        return app(LoanGuarantorService::class);
    }

    public function test_the_applicant_can_ask_a_member_of_their_set(): void
    {
        Notification::fake();
        $candidate = $this->setMember();

        $this->actingAs($this->member)
            ->post("/loans/{$this->loan->id}/guarantors", ['user_id' => $candidate->id])
            ->assertRedirect();

        $guarantor = LoanGuarantor::first();
        $this->assertSame('invited', $guarantor->status);
        $this->assertNotNull($guarantor->expires_at);

        Notification::assertSentTo($candidate, LoanGuarantorInvited::class);

        $bell = UnikosaNotification::where('user_id', $candidate->id)->first();
        $this->assertStringContainsString('/guarantorships', $bell->data['url']);
    }

    public function test_an_applicant_cannot_stand_for_themselves(): void
    {
        $this->actingAs($this->member)
            ->post("/loans/{$this->loan->id}/guarantors", ['user_id' => $this->member->id])
            ->assertSessionHasErrors('user_id');

        $this->assertSame(0, LoanGuarantor::count());
    }

    public function test_a_member_of_another_set_cannot_be_asked(): void
    {
        $outsider = User::factory()->create(['status' => 'approved']);

        $this->actingAs($this->member)
            ->post("/loans/{$this->loan->id}/guarantors", ['user_id' => $outsider->id])
            ->assertSessionHasErrors('user_id');
    }

    public function test_a_member_who_is_not_approved_cannot_be_asked(): void
    {
        $pending = $this->setMember(['status' => 'pending']);

        $this->actingAs($this->member)
            ->post("/loans/{$this->loan->id}/guarantors", ['user_id' => $pending->id])
            ->assertSessionHasErrors('user_id');
    }

    public function test_a_member_in_default_cannot_stand_for_anyone(): void
    {
        $defaulter = $this->setMember();
        Loan::factory()->for($this->program, 'program')->create([
            'set_id' => $this->program->set_id,
            'user_id' => $defaulter->id,
            'status' => 'defaulted',
        ]);

        $this->actingAs($this->member)
            ->post("/loans/{$this->loan->id}/guarantors", ['user_id' => $defaulter->id])
            ->assertSessionHasErrors('user_id');
    }

    public function test_only_the_applicant_may_nominate(): void
    {
        $stranger = $this->setMember();

        $this->actingAs($stranger)
            ->post("/loans/{$this->loan->id}/guarantors", ['user_id' => $this->setMember()->id])
            ->assertForbidden();
    }

    public function test_the_loan_advances_only_on_the_last_acceptance_needed(): void
    {
        Notification::fake();

        $reviewer = $this->setMember();
        $reviewer->assignRole('loan_officer');
        $this->program->officers()->attach($reviewer->id, ['role' => 'reviewer']);

        $first = $this->setMember();
        $second = $this->setMember();

        $firstInvite = $this->service()->invite($this->loan, $first, $this->member);
        $secondInvite = $this->service()->invite($this->loan, $second, $this->member);

        $this->actingAs($first)->post("/guarantorships/{$firstInvite->id}/accept")->assertRedirect();
        $this->assertSame('guarantors_pending', $this->loan->fresh()->status);

        $this->actingAs($second)->post("/guarantorships/{$secondInvite->id}/accept")->assertRedirect();

        $fresh = $this->loan->fresh();
        $this->assertSame('guarantors_secured', $fresh->status);
        $this->assertNotNull($fresh->guarantors_secured_at);

        Notification::assertSentTo($reviewer, LoanGuarantorsSecured::class);
    }

    public function test_a_decline_leaves_the_loan_waiting(): void
    {
        $invitee = $this->setMember();
        $invite = $this->service()->invite($this->loan, $invitee, $this->member);

        $this->actingAs($invitee)
            ->post("/guarantorships/{$invite->id}/decline", ['response_note' => 'Sorry, not this time.'])
            ->assertRedirect();

        $this->assertSame('declined', $invite->fresh()->status);
        $this->assertSame('Sorry, not this time.', $invite->fresh()->response_note);
        $this->assertSame('guarantors_pending', $this->loan->fresh()->status);
    }

    public function test_only_the_person_asked_may_answer(): void
    {
        $invitee = $this->setMember();
        $impostor = $this->setMember();
        $invite = $this->service()->invite($this->loan, $invitee, $this->member);

        $this->actingAs($impostor)->post("/guarantorships/{$invite->id}/accept")->assertForbidden();
        $this->assertSame('invited', $invite->fresh()->status);
    }

    public function test_an_expired_invitation_cannot_be_accepted(): void
    {
        $invitee = $this->setMember();
        $invite = $this->service()->invite($this->loan, $invitee, $this->member);
        $invite->update(['expires_at' => now()->subDay()]);

        $this->actingAs($invitee)->post("/guarantorships/{$invite->id}/accept");

        $this->assertSame('expired', $invite->fresh()->status);
        $this->assertSame('guarantors_pending', $this->loan->fresh()->status);
    }

    public function test_a_declined_invitation_can_be_reopened_rather_than_duplicated(): void
    {
        $invitee = $this->setMember();
        $invite = $this->service()->invite($this->loan, $invitee, $this->member);
        $this->actingAs($invitee)->post("/guarantorships/{$invite->id}/decline");

        $this->service()->invite($this->loan->fresh(), $invitee, $this->member);

        $this->assertSame(1, LoanGuarantor::where('loan_id', $this->loan->id)->count());
        $this->assertSame('invited', $invite->fresh()->status);
    }

    public function test_the_applicant_can_withdraw_a_request_nobody_has_answered(): void
    {
        $invitee = $this->setMember();
        $invite = $this->service()->invite($this->loan, $invitee, $this->member);

        $this->actingAs($this->member)
            ->delete("/loans/{$this->loan->id}/guarantors/{$invite->id}")
            ->assertRedirect();

        $this->assertSame('revoked', $invite->fresh()->status);
    }

    public function test_withdrawing_the_loan_closes_outstanding_requests(): void
    {
        $invitee = $this->setMember();
        $invite = $this->service()->invite($this->loan, $invitee, $this->member);

        $this->actingAs($this->member)->delete("/loans/{$this->loan->id}")->assertRedirect();

        $this->assertSame('withdrawn', $this->loan->fresh()->status);
        $this->assertSame('revoked', $invite->fresh()->status);
    }

    public function test_candidates_exclude_the_applicant_and_anyone_already_asked(): void
    {
        $asked = $this->setMember();
        $available = $this->setMember();
        $this->service()->invite($this->loan, $asked, $this->member);

        $ids = collect($this->service()->candidates($this->loan->fresh()))->pluck('id');

        $this->assertTrue($ids->contains($available->id));
        $this->assertFalse($ids->contains($asked->id));
        $this->assertFalse($ids->contains($this->member->id));
    }

    public function test_the_guarantorships_page_renders_and_a_guarantor_can_see_the_loan(): void
    {
        $invitee = $this->setMember();
        $this->service()->invite($this->loan, $invitee, $this->member);

        $this->actingAs($invitee)->get('/guarantorships')->assertOk();
        $this->actingAs($invitee)->get("/loans/{$this->loan->id}")->assertOk();
    }

    public function test_a_loan_that_is_not_collecting_guarantors_takes_no_nominations(): void
    {
        app(LoanStateMachine::class)->transition($this->loan, 'withdrawn', $this->member, ['silent' => true]);

        $this->actingAs($this->member)
            ->post("/loans/{$this->loan->id}/guarantors", ['user_id' => $this->setMember()->id])
            ->assertSessionHasErrors('user_id');
    }
}
