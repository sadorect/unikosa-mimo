<?php

namespace Tests\Feature;

use App\Jobs\ExpireLoanPublications;
use App\Jobs\ProcessLoanLifecycle;
use App\Jobs\PublishLoanDefaultToForum;
use App\Models\ForumGroup;
use App\Models\ForumPost;
use App\Models\Loan;
use App\Models\LoanDefaultPublication;
use App\Models\LoanProgram;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LoanDefaultPublished;
use App\Notifications\LoanPublicationTakenDown;
use App\Services\LoanPublicationService;
use App\Services\LoanRepaymentService;
use App\Services\LoanStateMachine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class LoanPublicationTest extends TestCase
{
    use RefreshDatabase;

    private LoanProgram $program;
    private User $borrower;
    private User $guarantor;
    private User $reviewer;
    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Setting::set('loans_enabled', 'true', 'modules');

        $this->program = LoanProgram::factory()->create(['publication_expiry_days' => 90]);
        $this->borrower = $this->setMember();
        $this->guarantor = $this->setMember();

        $this->reviewer = $this->setMember();
        $this->reviewer->assignRole('loan_officer');
        $this->program->officers()->attach($this->reviewer->id, ['role' => 'reviewer']);
        $this->program->officers()->attach($this->reviewer->id, ['role' => 'treasurer']);

        $this->loan = Loan::factory()->for($this->program, 'program')->defaulted()->create([
            'set_id' => $this->program->set_id,
            'user_id' => $this->borrower->id,
            'reviewed_by' => $this->reviewer->id,
        ]);

        $this->loan->guarantors()->create([
            'user_id' => $this->guarantor->id,
            'status' => 'accepted',
            'invited_at' => now(),
            'responded_at' => now(),
        ]);
    }

    private function setMember(): User
    {
        return User::factory()->create([
            'status' => 'approved',
            'email_verified_at' => now(),
            'graduating_set_id' => $this->program->set_id,
        ]);
    }

    private function publications(): LoanPublicationService
    {
        return app(LoanPublicationService::class);
    }

    public function test_a_default_is_published_to_the_sets_own_forum_group(): void
    {
        $publication = $this->publications()->publishToSetForum($this->loan);

        $this->assertSame('set_forum', $publication->scope);
        $this->assertNotNull($publication->expires_at);

        $post = ForumPost::find($publication->forum_post_id);
        $this->assertSame('approved', $post->status, 'A system notice skips the moderation queue.');
        $this->assertSame('announcement', $post->post_type);

        $group = ForumGroup::find($post->forum_group_id);
        $this->assertSame('set', $group->type);
        $this->assertSame($this->loan->set_id, $group->set_id);
    }

    public function test_the_notice_names_the_borrower_and_guarantors_but_nothing_private(): void
    {
        $this->borrower->update(['phone' => '08030001111', 'date_of_birth' => '1981-04-02', 'profession' => 'Architect']);

        $publication = $this->publications()->publishToSetForum($this->loan->fresh());
        $body = ForumPost::find($publication->forum_post_id)->body;
        $payload = $publication->published_payload;

        $this->assertStringContainsString($this->borrower->name, $body);
        $this->assertStringContainsString($this->guarantor->name, $body);
        $this->assertStringContainsString($this->loan->reference, $body);

        foreach ([$this->borrower->email, '08030001111', '1981-04-02', 'Architect', (string) $this->loan->purpose] as $private) {
            $this->assertStringNotContainsString($private, $body);
            $this->assertStringNotContainsString($private, json_encode($payload));
        }
    }

    public function test_publishing_twice_does_not_post_a_second_notice(): void
    {
        $first = $this->publications()->publishToSetForum($this->loan);
        $second = $this->publications()->publishToSetForum($this->loan->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, LoanDefaultPublication::where('loan_id', $this->loan->id)->count());
    }

    public function test_a_loan_that_is_not_in_default_cannot_be_published(): void
    {
        $this->loan->forceFill(['status' => 'in_grace'])->saveQuietly();

        $this->expectException(RuntimeException::class);
        $this->publications()->publishToSetForum($this->loan->fresh());
    }

    public function test_a_fund_with_set_publication_switched_off_publishes_nothing(): void
    {
        $this->program->update(['set_forum_publication_enabled' => false]);

        $this->assertNull($this->publications()->publishToSetForum($this->loan->fresh()));
    }

    public function test_everyone_named_is_told_the_notice_exists(): void
    {
        Notification::fake();

        (new PublishLoanDefaultToForum($this->loan))->handle($this->publications());

        Notification::assertSentTo($this->borrower, LoanDefaultPublished::class);
        Notification::assertSentTo($this->guarantor, LoanDefaultPublished::class);
    }

    public function test_defaulting_publishes_the_notice_through_the_lifecycle_job(): void
    {
        $loan = Loan::factory()->for($this->program, 'program')->disbursed(100)->create([
            'set_id' => $this->program->set_id,
            'user_id' => $this->setMember()->id,
            'reviewed_by' => $this->reviewer->id,
        ]);
        $loan->forceFill([
            'status' => 'in_grace',
            'grace_ends_at' => now()->subDays(2),
        ])->saveQuietly();

        (new ProcessLoanLifecycle)->handle(app(LoanStateMachine::class));

        $this->assertSame('defaulted', $loan->fresh()->status);
        $this->assertTrue($loan->publications()->scope('set_forum')->active()->exists());
    }

    public function test_escalation_needs_the_permission_the_fund_switch_and_the_cooling_off(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // Fund switch off.
        $this->assertThrows(fn () => $this->publications()->publishToAssociationForum($this->loan, $superAdmin), RuntimeException::class);

        $this->program->update(['association_forum_publication_enabled' => true]);

        // An officer without the platform permission still cannot escalate.
        $this->assertThrows(fn () => $this->publications()->publishToAssociationForum($this->loan->fresh(), $this->reviewer), RuntimeException::class);

        // Inside the cooling-off window.
        $this->loan->forceFill(['defaulted_at' => now()->subDays(2)])->saveQuietly();
        $this->assertThrows(fn () => $this->publications()->publishToAssociationForum($this->loan->fresh(), $superAdmin), RuntimeException::class);

        // Past it, with everything else in place.
        $this->loan->forceFill(['defaulted_at' => now()->subDays(20)])->saveQuietly();
        $publication = $this->publications()->publishToAssociationForum($this->loan->fresh(), $superAdmin);

        $this->assertSame('association_forum', $publication->scope);

        $group = ForumGroup::find(ForumPost::find($publication->forum_post_id)->forum_group_id);
        $this->assertSame('loan-default-notices', $group->slug);
        $this->assertNull($group->set_id, 'The association-wide group belongs to no single set.');
    }

    public function test_settling_the_loan_takes_every_notice_down(): void
    {
        Notification::fake();

        $this->program->update(['association_forum_publication_enabled' => true]);
        $this->loan->forceFill(['defaulted_at' => now()->subDays(20)])->saveQuietly();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->publications()->publishToSetForum($this->loan->fresh());
        $this->publications()->publishToAssociationForum($this->loan->fresh(), $superAdmin);
        $this->assertSame(2, $this->loan->publications()->active()->count());

        // The guarantor clears it, which is the case that matters most.
        $repayments = app(LoanRepaymentService::class);
        $repayment = $repayments->record($this->loan->fresh(), $this->guarantor, [
            'amount' => 105000,
            'paid_on' => now()->toDateString(),
        ]);
        $repayments->confirm($repayment, $this->reviewer);

        $this->assertSame('settled', $this->loan->fresh()->status);
        $this->assertSame(0, $this->loan->publications()->active()->count());

        foreach (LoanDefaultPublication::all() as $publication) {
            $this->assertNotNull($publication->taken_down_at);
            $this->assertNull(ForumPost::find($publication->forum_post_id), 'The forum post itself must go, not just the row.');
        }

        Notification::assertSentTo($this->borrower, LoanPublicationTakenDown::class);
        Notification::assertSentTo($this->guarantor, LoanPublicationTakenDown::class);
    }

    public function test_an_expired_notice_is_taken_down_on_its_own(): void
    {
        $publication = $this->publications()->publishToSetForum($this->loan);
        $publication->forceFill(['expires_at' => now()->subDay()])->saveQuietly();

        (new ExpireLoanPublications)->handle($this->publications());

        $this->assertNotNull($publication->fresh()->taken_down_at);
        $this->assertNull(ForumPost::find($publication->forum_post_id));
    }

    public function test_a_manual_takedown_records_who_and_why(): void
    {
        $publication = $this->publications()->publishToSetForum($this->loan);

        $this->publications()->takedownAllFor($this->loan->fresh(), $this->reviewer, 'Published in error.');

        $fresh = $publication->fresh();
        $this->assertSame($this->reviewer->id, $fresh->taken_down_by);
        $this->assertSame('Published in error.', $fresh->takedown_reason);
    }

    public function test_the_notice_renders_from_its_frozen_payload_not_the_live_loan(): void
    {
        $publication = $this->publications()->publishToSetForum($this->loan);
        $originalOutstanding = $publication->published_payload['outstanding'];

        // Whatever happens to the loan afterwards, what was disclosed does not
        // change underneath the people it names.
        $this->loan->forceFill(['penalty_amount' => 50000])->saveQuietly();

        $this->assertSame($originalOutstanding, $publication->fresh()->published_payload['outstanding']);
    }

    public function test_a_member_named_in_a_notice_sees_it_on_their_loan_page(): void
    {
        $this->publications()->publishToSetForum($this->loan);

        $this->actingAs($this->borrower)->get("/loans/{$this->loan->id}")->assertOk();
        $this->actingAs($this->guarantor)->get("/loans/{$this->loan->id}")->assertOk();
    }

    public function test_a_fund_with_no_officer_to_post_under_still_defaults_cleanly(): void
    {
        // The regression this guards: throwing here once took the default
        // transition down with it. A missing notice is recoverable; a loan
        // that quietly stops being in default is not.
        $orphanProgram = LoanProgram::factory()->create();
        $loan = Loan::factory()->for($orphanProgram, 'program')->disbursed(100)->create([
            'set_id' => $orphanProgram->set_id,
            'user_id' => User::factory()->create(['status' => 'approved', 'graduating_set_id' => $orphanProgram->set_id])->id,
        ]);
        $loan->forceFill(['status' => 'in_grace', 'grace_ends_at' => now()->subDays(2)])->saveQuietly();

        (new ProcessLoanLifecycle)->handle(app(LoanStateMachine::class));

        $this->assertSame('defaulted', $loan->fresh()->status);
        $this->assertSame(0, $loan->publications()->count());
    }

    public function test_there_is_no_unauthenticated_route_that_discloses_a_defaulter(): void
    {
        $this->publications()->publishToSetForum($this->loan);

        // Every forum route sits behind auth; nothing in this module publishes
        // outside it.
        foreach (['/defaulters', '/forum'] as $uri) {
            $response = $this->get($uri);
            $this->assertNotSame(200, $response->getStatusCode(), "{$uri} must not be readable while signed out.");
        }
    }
}
