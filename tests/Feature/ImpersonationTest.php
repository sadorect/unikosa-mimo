<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Impersonation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->superAdmin = User::factory()->create(['status' => 'approved']);
        $this->superAdmin->assignRole('super_admin');

        $this->member = User::factory()->create(['status' => 'approved']);
        $this->member->assignRole('member');
    }

    public function test_super_admin_can_sign_in_as_a_member(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('impersonate.start', $this->member));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->member);
        $this->assertSame($this->superAdmin->id, session(Impersonation::SESSION_KEY));
    }

    public function test_start_is_written_to_the_activity_log(): void
    {
        $this->actingAs($this->superAdmin)->post(route('impersonate.start', $this->member));

        $entry = Activity::inLog('impersonation')->latest('id')->first();

        $this->assertNotNull($entry);
        $this->assertSame('impersonation.started', $entry->event);
        $this->assertTrue($entry->causer->is($this->superAdmin));
        $this->assertTrue($entry->subject->is($this->member));
    }

    public function test_dashboard_shares_the_impersonation_state(): void
    {
        $this->actingAs($this->superAdmin)->post(route('impersonate.start', $this->member));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.id', $this->member->id)
                ->where('impersonation.impersonator', $this->superAdmin->name));
    }

    public function test_state_is_absent_when_not_impersonating(): void
    {
        $this->actingAs($this->member)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('impersonation', null));
    }

    public function test_stop_returns_to_the_admin(): void
    {
        $this->actingAs($this->superAdmin)->post(route('impersonate.start', $this->member));

        $response = $this->post(route('impersonate.stop'));

        $response->assertRedirect('/admin/users');
        $this->assertAuthenticatedAs($this->superAdmin);
        $this->assertNull(session(Impersonation::SESSION_KEY));
        $this->assertSame('impersonation.stopped', Activity::inLog('impersonation')->latest('id')->first()->event);
    }

    public function test_logging_out_while_impersonating_only_stops(): void
    {
        $this->actingAs($this->superAdmin)->post(route('impersonate.start', $this->member));

        $response = $this->post('/logout');

        $response->assertRedirect('/admin/users');
        $this->assertAuthenticatedAs($this->superAdmin);
    }

    public function test_plain_logout_still_signs_out(): void
    {
        $this->actingAs($this->member)->post('/logout');

        $this->assertGuest();
    }

    public function test_stop_when_not_impersonating_is_harmless(): void
    {
        $this->actingAs($this->member)
            ->post(route('impersonate.stop'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->member);
    }

    public function test_a_member_cannot_impersonate(): void
    {
        $other = User::factory()->create(['status' => 'approved']);

        $this->actingAs($this->member)
            ->post(route('impersonate.start', $other))
            ->assertForbidden();

        $this->assertAuthenticatedAs($this->member);
    }

    public function test_a_set_coordinator_cannot_impersonate(): void
    {
        $coordinator = User::factory()->create(['status' => 'approved']);
        $coordinator->assignRole('set_representative');

        $this->actingAs($coordinator)
            ->post(route('impersonate.start', $this->member))
            ->assertForbidden();
    }

    public function test_super_admins_cannot_be_impersonated_even_by_a_super_admin(): void
    {
        $otherAdmin = User::factory()->create(['status' => 'approved']);
        $otherAdmin->assignRole('super_admin');

        $this->actingAs($this->superAdmin)
            ->post(route('impersonate.start', $otherAdmin))
            ->assertForbidden();

        $this->assertAuthenticatedAs($this->superAdmin);
    }

    public function test_cannot_impersonate_yourself(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('impersonate.start', $this->superAdmin))
            ->assertForbidden();
    }

    public function test_cannot_nest_impersonation(): void
    {
        $other = User::factory()->create(['status' => 'approved']);

        $this->actingAs($this->superAdmin)->post(route('impersonate.start', $this->member));

        // The impersonated member is not a super admin, and even if they were,
        // nesting is refused before the role is looked at.
        $this->post(route('impersonate.start', $other))->assertForbidden();
        $this->assertAuthenticatedAs($this->member);
    }

    public function test_deleted_members_cannot_be_impersonated(): void
    {
        $this->member->delete();

        $this->actingAs($this->superAdmin)
            ->post(route('impersonate.start', $this->member))
            ->assertNotFound();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->post(route('impersonate.start', $this->member))->assertRedirect(route('login'));
    }

    public function test_impersonated_browsing_does_not_mark_the_member_online(): void
    {
        $this->assertNull($this->member->last_seen_at);

        $this->actingAs($this->superAdmin)->post(route('impersonate.start', $this->member));
        $this->get(route('dashboard'))->assertOk();

        $this->assertNull($this->member->fresh()->last_seen_at);
    }

    public function test_denial_reasons_are_the_single_source_of_truth(): void
    {
        $service = app(Impersonation::class);

        $this->assertNull($service->denialReason($this->superAdmin, $this->member));
        $this->assertNotNull($service->denialReason($this->member, $this->superAdmin));
        $this->assertNotNull($service->denialReason($this->superAdmin, $this->superAdmin));
        $this->assertNotNull($service->denialReason(null, $this->member));
    }
}
