<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $manageElections = Permission::firstOrCreate(['name' => 'manage elections', 'guard_name' => 'web']);
        $moderateElections = Permission::firstOrCreate(['name' => 'moderate elections', 'guard_name' => 'web']);

        Role::where('name', 'super_admin')->where('guard_name', 'web')->first()
            ?->givePermissionTo([$manageElections, $moderateElections]);

        Role::where('name', 'content_moderator')->where('guard_name', 'web')->first()
            ?->givePermissionTo($moderateElections);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::where('name', 'manage elections')->where('guard_name', 'web')->first()?->delete();
        Permission::where('name', 'moderate elections')->where('guard_name', 'web')->first()?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
