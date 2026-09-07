<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $manage = Permission::firstOrCreate(['name' => 'manage loans', 'guard_name' => 'web']);
        $review = Permission::firstOrCreate(['name' => 'review loans', 'guard_name' => 'web']);
        $disburse = Permission::firstOrCreate(['name' => 'disburse loans', 'guard_name' => 'web']);
        $publish = Permission::firstOrCreate(['name' => 'publish loan defaults', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'loan_officer', 'guard_name' => 'web'])
            ->givePermissionTo([$review, $manage]);

        Role::firstOrCreate(['name' => 'loan_disburser', 'guard_name' => 'web'])
            ->givePermissionTo([$disburse]);

        // finance_admin administers the fund but cannot publish a defaulter:
        // a reputational sanction stays with the top role.
        Role::where('name', 'finance_admin')->where('guard_name', 'web')->first()
            ?->givePermissionTo([$manage]);

        Role::where('name', 'super_admin')->where('guard_name', 'web')->first()
            ?->givePermissionTo([$manage, $review, $disburse, $publish]);

        // The module switches are written here, not only in SettingsSeeder:
        // CheckModuleEnabled treats a *missing* key as enabled, so migrating
        // without seeding would put /loans live the moment this deploys.
        foreach (['loans_enabled'] as $key) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => 'false',
                    'group' => 'modules',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::where('name', 'loan_officer')->where('guard_name', 'web')->first()?->delete();
        Role::where('name', 'loan_disburser')->where('guard_name', 'web')->first()?->delete();

        foreach (['manage loans', 'review loans', 'disburse loans', 'publish loan defaults'] as $name) {
            Permission::where('name', $name)->where('guard_name', 'web')->first()?->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
