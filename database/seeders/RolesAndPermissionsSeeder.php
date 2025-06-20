<?php

// database/seeders/RolesAndPermissionsSeeder.php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'create task',
            'edit task',
            'delete task',
            'view all tasks',
            'manage team',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $staff = Role::firstOrCreate(['name' => 'staff']);

        // Assign permissions
        $admin->syncPermissions(Permission::all());
        $staff->syncPermissions(['create task', 'edit task', 'view all tasks']);
    }
}

