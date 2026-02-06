<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Spatie tables must exist
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        $permissionName = 'staff.dashboard.view';
        $guard = 'web';

        // Create permission if missing
        $permission = Permission::query()->firstOrCreate([
            'name' => $permissionName,
            'guard_name' => $guard,
        ]);

        // Assign to role substaff (if role exists)
        $substaffRole = Role::query()->where('name', 'substaff')->where('guard_name', $guard)->first();
        if ($substaffRole) {
            $substaffRole->givePermissionTo($permission);
        }

        // Clear permission cache so it applies immediately
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        $permissionName = 'staff.dashboard.view';
        $guard = 'web';

        $permission = Permission::query()->where('name', $permissionName)->where('guard_name', $guard)->first();

        if ($permission) {
            // Detach from substaff role only (do not delete permission if you might reuse it)
            $substaffRole = Role::query()->where('name', 'substaff')->where('guard_name', $guard)->first();
            if ($substaffRole) {
                $substaffRole->revokePermissionTo($permission);
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
