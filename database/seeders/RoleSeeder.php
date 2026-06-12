<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ VERY IMPORTANT
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // =========================
        // Roles
        // =========================
        $admin = Role::updateOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['level' => 100]
        );

        $subadmin = Role::updateOrCreate(
            ['name' => 'subadmin', 'guard_name' => 'web'],
            ['level' => 50]
        );

        $staff = Role::updateOrCreate(
            ['name' => 'staff', 'guard_name' => 'web'],
            ['level' => 30]
        );

        $substaff = Role::updateOrCreate(
            ['name' => 'substaff', 'guard_name' => 'web'],
            ['level' => 20]
        );

        $user = Role::updateOrCreate(
            ['name' => 'user', 'guard_name' => 'web'],
            ['level' => 10]
        );

        // =========================
        // Permissions
        // =========================
        $permissions = [
            // staff → substaff
            'staff.substaff.create',
            'staff.substaff.permissions.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // =========================
        // Admin = FULL ACCESS
        // =========================
        $admin->syncPermissions(
            Permission::where('guard_name', 'web')->pluck('name')->toArray()
        );

        // ❗ DO NOT auto-assign staff permissions here
        // Staff permissions are managed globally elsewhere (as you wanted)

        // Clear cache again for safety
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
