<?php

namespace Tests\Feature;

use App\Filament\Resources\LoanProgramResource\Pages\EditLoanProgram;
use App\Filament\Resources\LoanProgramResource\RelationManagers\OfficersRelationManager;
use App\Models\LoanProgram;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Tables\Actions\AttachAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Appointing officers to a fund, driven through the actual Filament component.
 *
 * Written after the attach form 500'd on live: Filament derives the inverse
 * relationship from the owner model's name (LoanProgram -> loanPrograms) and
 * the relation on User is loanProgramOfficerships, so opening the form threw
 * "Call to undefined method". Nothing in the suite mounted the form, so
 * nothing caught it.
 */
class LoanProgramOfficersTest extends TestCase
{
    use RefreshDatabase;

    private LoanProgram $program;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->program = LoanProgram::factory()->create();
        $this->admin = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->admin->assignRole('super_admin');
        $this->actingAs($this->admin);
    }

    private function setMember(): User
    {
        return User::factory()->create([
            'status' => 'approved',
            'graduating_set_id' => $this->program->set_id,
        ]);
    }

    private function manager()
    {
        return Livewire::test(OfficersRelationManager::class, [
            'ownerRecord' => $this->program,
            'pageClass' => EditLoanProgram::class,
        ]);
    }

    public function test_the_officers_table_renders(): void
    {
        $this->manager()->assertSuccessful();
    }

    public function test_the_attach_form_opens(): void
    {
        // The regression: this mount is what threw the 500.
        $this->manager()
            ->mountTableAction('attach')
            ->assertHasNoTableActionErrors()
            ->assertSuccessful();
    }

    public function test_an_officer_can_be_appointed_and_appears_in_the_table(): void
    {
        $candidate = $this->setMember();

        $this->manager()
            ->mountTableAction('attach')
            ->setTableActionData(['recordId' => $candidate->id, 'role' => 'reviewer'])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('loan_program_officers', [
            'loan_program_id' => $this->program->id,
            'user_id' => $candidate->id,
            'role' => 'reviewer',
        ]);

        $this->assertTrue($candidate->fresh()->isLoanOfficer($this->program, 'reviewer'));
    }

    public function test_one_person_can_hold_two_offices_in_the_same_fund(): void
    {
        $candidate = $this->setMember();

        foreach (['reviewer', 'treasurer'] as $role) {
            $this->manager()
                ->mountTableAction('attach')
                ->setTableActionData(['recordId' => $candidate->id, 'role' => $role])
                ->callMountedTableAction()
                ->assertHasNoTableActionErrors();
        }

        $this->assertSame(2, $this->program->officers()->where('users.id', $candidate->id)->count());
    }

    public function test_appointing_a_reviewer_grants_the_role_that_carries_the_permission(): void
    {
        // The pivot alone appoints someone to nothing: LoanPolicy::officerWith()
        // wants the permission as well as the office.
        $candidate = $this->setMember();

        $this->attach($candidate, 'reviewer');

        $candidate = $candidate->fresh();

        $this->assertTrue($candidate->hasRole('loan_officer'));
        $this->assertTrue($candidate->can('review loans'));
    }

    public function test_appointing_a_disburser_grants_the_disburser_role_and_not_the_officer_role(): void
    {
        $candidate = $this->setMember();

        $this->attach($candidate, 'disburser');

        $candidate = $candidate->fresh();

        $this->assertTrue($candidate->hasRole('loan_disburser'));
        $this->assertFalse($candidate->hasRole('loan_officer'));
        $this->assertTrue($candidate->can('disburse loans'));
    }

    public function test_a_treasurer_gets_the_role_that_carries_manage_loans(): void
    {
        $candidate = $this->setMember();

        $this->attach($candidate, 'treasurer');

        $this->assertTrue($candidate->fresh()->can('manage loans'));
    }

    public function test_detaching_the_last_office_takes_the_role_back(): void
    {
        $candidate = $this->setMember();

        $this->attach($candidate, 'reviewer');
        $this->assertTrue($candidate->fresh()->hasRole('loan_officer'));

        // Rows are keyed by the pivot, because a row is an office rather than
        // a person.
        $this->manager()
            ->callTableAction('detach', $this->officeRow($candidate, 'reviewer')->id)
            ->assertHasNoTableActionErrors();

        $this->assertFalse($candidate->fresh()->hasRole('loan_officer'));
    }

    public function test_an_office_in_another_fund_keeps_the_role_after_a_detach(): void
    {
        // Roles are recomputed from every officership, not toggled per attach,
        // so resigning one fund must not disarm someone in another.
        $candidate = $this->setMember();
        $other = LoanProgram::factory()->create(['set_id' => $this->program->set_id]);
        $other->officers()->attach($candidate->id, ['role' => 'reviewer']);

        $this->attach($candidate, 'reviewer');

        $this->manager()
            ->callTableAction('detach', $this->officeRow($candidate, 'reviewer')->id)
            ->assertHasNoTableActionErrors();

        $this->assertTrue($candidate->fresh()->hasRole('loan_officer'));
    }

    public function test_the_option_query_never_selects_a_json_column(): void
    {
        // Filament wraps the option query in DISTINCT. Postgres has no equality
        // operator for `json`, and users carries skills / social_links, so
        // `select distinct users.*` is a hard 42883 the moment the form opens.
        // sqlite tolerates it, which is why the suite missed it once already —
        // so assert on the SQL rather than on the query running.
        $manager = $this->manager()->instance();
        $action = $manager->getTable()->getAction('attach');

        $this->assertInstanceOf(AttachAction::class, $action);

        $property = new ReflectionProperty(AttachAction::class, 'modifyRecordSelectOptionsQueryUsing');
        $property->setAccessible(true);
        $modify = $property->getValue($action);

        $this->assertNotNull($modify, 'The option query must be scoped to this fund.');

        $sql = $modify(User::query())->toSql();

        $this->assertStringNotContainsString('*', $sql, 'The option query must name its columns.');
        $this->assertStringContainsString('"users"."id"', $sql);
    }

    public function test_an_office_can_be_changed_in_place(): void
    {
        $candidate = $this->setMember();
        $this->attach($candidate, 'reviewer');

        $pivotId = $this->officeRow($candidate, 'reviewer')->id;

        $this->manager()
            ->callTableAction('edit', $pivotId, ['role' => 'disburser'])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('loan_program_officers', [
            'id' => $pivotId,
            'role' => 'disburser',
        ]);
        $this->assertSame(1, $this->program->officers()->where('users.id', $candidate->id)->count());
    }

    public function test_changing_the_office_moves_the_role_with_it(): void
    {
        $candidate = $this->setMember();
        $this->attach($candidate, 'reviewer');

        $this->manager()
            ->callTableAction('edit', $this->officeRow($candidate, 'reviewer')->id, ['role' => 'disburser'])
            ->assertHasNoTableActionErrors();

        $candidate = $candidate->fresh();

        $this->assertTrue($candidate->hasRole('loan_disburser'));
        $this->assertFalse($candidate->hasRole('loan_officer'), 'The office they no longer hold must not leave its permission behind.');
    }

    public function test_editing_one_of_two_offices_leaves_the_other_alone(): void
    {
        // The reason each row is keyed by the pivot: without it the update
        // matches on user + fund and rewrites both offices at once.
        $candidate = $this->setMember();
        $this->attach($candidate, 'reviewer');
        $this->attach($candidate, 'treasurer');

        $this->manager()
            ->callTableAction('edit', $this->officeRow($candidate, 'reviewer')->id, ['role' => 'disburser'])
            ->assertHasNoTableActionErrors();

        $held = $this->program->officers()
            ->where('users.id', $candidate->id)
            ->pluck('loan_program_officers.role')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['disburser', 'treasurer'], $held);
    }

    public function test_an_office_cannot_be_changed_to_one_already_held(): void
    {
        $candidate = $this->setMember();
        $this->attach($candidate, 'reviewer');
        $this->attach($candidate, 'treasurer');

        $this->manager()
            ->callTableAction('edit', $this->officeRow($candidate, 'reviewer')->id, ['role' => 'treasurer'])
            ->assertHasTableActionErrors(['role']);

        $this->assertSame(2, $this->program->officers()->where('users.id', $candidate->id)->count());
    }

    public function test_the_same_office_cannot_be_attached_twice(): void
    {
        $candidate = $this->setMember();
        $this->attach($candidate, 'reviewer');

        $this->manager()
            ->mountTableAction('attach')
            ->setTableActionData(['recordId' => $candidate->id, 'role' => 'reviewer'])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['role']);

        $this->assertSame(1, $this->program->officers()->where('users.id', $candidate->id)->count());
    }

    public function test_detaching_one_office_leaves_the_person_their_other_one(): void
    {
        $candidate = $this->setMember();
        $this->attach($candidate, 'reviewer');
        $this->attach($candidate, 'treasurer');

        $this->manager()
            ->callTableAction('detach', $this->officeRow($candidate, 'reviewer')->id)
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, $this->program->officers()->where('users.id', $candidate->id)->count());
        $this->assertTrue($candidate->fresh()->isLoanOfficer($this->program, 'treasurer'));
    }

    public function test_an_officer_already_appointed_is_still_offered_for_a_second_office(): void
    {
        // Filament hides already-attached records unless duplicates are
        // allowed, which made the second office unreachable from the UI even
        // though the fund supports it.
        $candidate = $this->setMember();
        $this->attach($candidate, 'reviewer');

        $component = $this->manager()
            ->mountTableAction('attach')
            ->instance()
            ->getMountedTableActionForm()
            ->getFlatFields()['recordId'];

        $this->assertArrayHasKey($candidate->id, $component->getOptions());
    }

    private function officeRow(User $user, string $role): object
    {
        return DB::table('loan_program_officers')
            ->where('loan_program_id', $this->program->id)
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->first();
    }

    private function attach(User $candidate, string $role): void
    {
        $this->manager()
            ->mountTableAction('attach')
            ->setTableActionData(['recordId' => $candidate->id, 'role' => $role])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();
    }

    public function test_the_inverse_relationship_filament_needs_actually_exists(): void
    {
        // What the 500 was really about: guard the name, not just the symptom.
        $inverse = (new OfficersRelationManager)->getInverseRelationshipName();

        $this->assertNotNull($inverse, 'The inverse relationship must be stated, not guessed.');
        $this->assertTrue(
            method_exists(User::class, $inverse),
            "User::{$inverse}() does not exist, so Filament cannot build the attach form."
        );
    }
}
