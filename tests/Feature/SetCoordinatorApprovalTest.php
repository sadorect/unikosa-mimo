<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Models\Set;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SetCoordinatorApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Set $ownSet;
    protected Set $otherSet;
    protected User $coordinator;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->ownSet = Set::create(['name' => 'Set of 2005', 'year' => 2005]);
        $this->otherSet = Set::create(['name' => 'Set of 2010', 'year' => 2010]);

        $this->superAdmin = User::factory()->create(['status' => 'approved']);
        $this->superAdmin->assignRole('super_admin');

        $this->coordinator = User::factory()->create(['status' => 'approved']);
        $this->coordinator->assignRole('set_representative');
        $this->coordinator->coordinatedSets()->attach($this->ownSet->id);
    }

    public function test_coordinator_may_review_their_own_sets_signups(): void
    {
        $applicant = User::factory()->create(['graduating_set_id' => $this->ownSet->id, 'status' => 'pending']);

        $this->assertTrue($this->coordinator->canReviewMember($applicant));
    }

    public function test_coordinator_may_not_review_another_sets_signups(): void
    {
        $applicant = User::factory()->create(['graduating_set_id' => $this->otherSet->id, 'status' => 'pending']);

        $this->assertFalse($this->coordinator->canReviewMember($applicant));
    }

    public function test_coordinator_may_not_review_a_member_with_no_set(): void
    {
        $applicant = User::factory()->create(['graduating_set_id' => null, 'status' => 'pending']);

        $this->assertFalse($this->coordinator->canReviewMember($applicant));
        $this->assertTrue($this->superAdmin->canReviewMember($applicant));
    }

    public function test_super_admin_may_review_any_signup(): void
    {
        $applicant = User::factory()->create(['graduating_set_id' => $this->otherSet->id, 'status' => 'pending']);

        $this->assertTrue($this->superAdmin->canReviewMember($applicant));
    }

    public function test_admin_member_list_is_scoped_to_a_coordinators_own_sets(): void
    {
        $mine = User::factory()->create(['graduating_set_id' => $this->ownSet->id]);
        $theirs = User::factory()->create(['graduating_set_id' => $this->otherSet->id]);
        $unassigned = User::factory()->create(['graduating_set_id' => null]);

        Auth::login($this->coordinator);
        $visible = UserResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visible->contains($mine->id));
        $this->assertFalse($visible->contains($theirs->id));
        $this->assertFalse($visible->contains($unassigned->id));
    }

    public function test_super_admin_member_list_is_not_scoped(): void
    {
        $mine = User::factory()->create(['graduating_set_id' => $this->ownSet->id]);
        $theirs = User::factory()->create(['graduating_set_id' => $this->otherSet->id]);

        Auth::login($this->superAdmin);
        $visible = UserResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($visible->contains($mine->id));
        $this->assertTrue($visible->contains($theirs->id));
    }

    public function test_coordinator_cannot_create_edit_or_delete_users(): void
    {
        Auth::login($this->coordinator);

        $this->assertTrue(UserResource::canViewAny());
        $this->assertFalse(UserResource::canCreate());
        $this->assertFalse(UserResource::canEdit($this->superAdmin));
        $this->assertFalse(UserResource::canDelete($this->superAdmin));
        $this->assertFalse(UserResource::canDeleteAny());
    }

    public function test_super_admin_retains_full_member_management(): void
    {
        Auth::login($this->superAdmin);

        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(UserResource::canCreate());
        $this->assertTrue(UserResource::canEdit($this->coordinator));
        $this->assertTrue(UserResource::canDelete($this->coordinator));
    }

    public function test_plain_members_cannot_review_anyone(): void
    {
        $member = User::factory()->create(['status' => 'approved']);
        $member->assignRole('member');
        $applicant = User::factory()->create(['graduating_set_id' => $this->ownSet->id, 'status' => 'pending']);

        $this->assertFalse($member->canReviewMember($applicant));
    }
}
