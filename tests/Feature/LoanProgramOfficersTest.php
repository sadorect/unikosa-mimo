<?php

namespace Tests\Feature;

use App\Filament\Resources\LoanProgramResource\Pages\EditLoanProgram;
use App\Filament\Resources\LoanProgramResource\RelationManagers\OfficersRelationManager;
use App\Models\LoanProgram;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
