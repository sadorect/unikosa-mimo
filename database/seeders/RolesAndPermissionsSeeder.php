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

        // firstOrCreate, not create: several permissions ('moderate events', the
        // election ones) are already introduced by migrations, so a plain create
        // collides on any freshly-migrated database.
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'set_representative', 'guard_name' => 'web'])
            ->syncPermissions(['manage members', 'manage events']);

        Role::firstOrCreate(['name' => 'chapter_head', 'guard_name' => 'web'])
            ->syncPermissions(['manage events']);

        Role::firstOrCreate(['name' => 'content_moderator', 'guard_name' => 'web'])
            ->syncPermissions(['moderate content', 'manage forum', 'manage blog', 'moderate events', 'moderate elections']);

        Role::firstOrCreate(['name' => 'finance_admin', 'guard_name' => 'web'])
            ->syncPermissions(['manage campaigns', 'manage payments', 'view financial reports', 'manage settings']);

        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    }
}
