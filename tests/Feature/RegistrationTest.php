<?php

namespace Tests\Feature;

use App\Models\Set;
use App\Models\User;
use App\Notifications\MemberPendingApprovalNotification;
use App\Notifications\NewMemberAwaitingReviewNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected Set $set;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->set = Set::create(['name' => 'Set of 2005', 'year' => 2005]);
    }

    protected function register(array $overrides = []): User
    {
        return (new \App\Actions\Fortify\CreateNewUser())->create(array_merge([
            'name' => 'Ada Eze',
            'email' => 'ada@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'graduating_set_id' => $this->set->id,
        ], $overrides));
    }

    public function test_new_registrations_enter_pending_status_and_get_member_role(): void
    {
        $user = $this->register();

        $this->assertSame('pending', $user->status);
        $this->assertTrue($user->hasRole('member'));
        $this->assertSame($this->set->id, $user->graduating_set_id);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->register(['email' => 'taken@example.com']);
    }

    public function test_registration_requires_a_graduating_set(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->register(['graduating_set_id' => null]);
    }

    public function test_registration_rejects_an_unknown_graduating_set(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->register(['graduating_set_id' => 99999]);
    }

    public function test_applicant_is_told_their_account_is_awaiting_review(): void
    {
        Notification::fake();

        $user = $this->register();

        Notification::assertSentTo($user, MemberPendingApprovalNotification::class);
    }

    public function test_set_coordinators_and_super_admins_are_alerted_to_a_new_signup(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $coordinator = User::factory()->create();
        $coordinator->coordinatedSets()->attach($this->set->id);

        $otherCoordinator = User::factory()->create();
        $otherCoordinator->coordinatedSets()->attach(Set::create(['name' => 'Set of 1999', 'year' => 1999])->id);

        $applicant = $this->register();

        Notification::assertSentTo($superAdmin, NewMemberAwaitingReviewNotification::class);
        Notification::assertSentTo($coordinator, NewMemberAwaitingReviewNotification::class);
        Notification::assertNotSentTo($otherCoordinator, NewMemberAwaitingReviewNotification::class);
        Notification::assertNotSentTo($applicant, NewMemberAwaitingReviewNotification::class);
    }
}
