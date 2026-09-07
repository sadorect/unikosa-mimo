<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The loan permissions are created by both a migration and the seeder, so the
 * seeder must stay firstOrCreate — a plain create() collides on any freshly
 * migrated database and takes the whole suite down with it.
 */
class LoanPermissionSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_after_migration_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        foreach (['manage loans', 'review loans', 'disburse loans', 'publish loan defaults'] as $name) {
            $this->assertSame(1, Permission::where('name', $name)->where('guard_name', 'web')->count());
        }
    }

    public function test_publishing_defaults_is_reserved_to_super_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue(Role::findByName('super_admin')->hasPermissionTo('publish loan defaults'));
        $this->assertFalse(Role::findByName('finance_admin')->hasPermissionTo('publish loan defaults'));
        $this->assertFalse(Role::findByName('loan_officer')->hasPermissionTo('publish loan defaults'));
        $this->assertFalse(Role::findByName('set_representative')->hasPermissionTo('review loans'));
    }

    public function test_loan_roles_carry_their_own_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertTrue(Role::findByName('loan_officer')->hasPermissionTo('review loans'));
        $this->assertTrue(Role::findByName('loan_disburser')->hasPermissionTo('disburse loans'));
        $this->assertTrue(Role::findByName('finance_admin')->hasPermissionTo('manage loans'));
    }
}
