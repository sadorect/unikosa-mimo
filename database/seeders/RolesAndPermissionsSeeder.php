<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage members',
            'manage sets',
            'manage chapters',
            'manage events',
            'manage blog',
            'manage forum',
            'manage jobs',
            'manage business listings',
            'manage campaigns',
            'manage payments',
            'manage settings',
            'moderate content',
            'view financial reports',
            'manage gallery',
            'moderate events',
            'manage elections',
            'moderate elections',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::create(['name' => 'super_admin', 'guard_name' => 'web'])
            ->givePermissionTo(Permission::all());

        Role::create(['name' => 'set_representative', 'guard_name' => 'web'])
            ->givePermissionTo(['manage members', 'manage events']);

        Role::create(['name' => 'chapter_head', 'guard_name' => 'web'])
            ->givePermissionTo(['manage events']);

        Role::create(['name' => 'content_moderator', 'guard_name' => 'web'])
            ->givePermissionTo(['moderate content', 'manage forum', 'manage blog', 'moderate events', 'moderate elections']);

        Role::create(['name' => 'finance_admin', 'guard_name' => 'web'])
            ->givePermissionTo(['manage campaigns', 'manage payments', 'view financial reports', 'manage settings']);

        Role::create(['name' => 'member', 'guard_name' => 'web']);
    }
}
