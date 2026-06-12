<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionCrudSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'web';

        // Base modules (ignore tasks)
        $modules = [
            'admin.users',
            'admin.roles',
            'admin.company_hours',
            'admin.projects',
            'admin.attendance',
            'admin.campaigns',
            'admin.email_templates',
            'admin.activity_logs',
            'staff.substaff',
        ];

        // Actions you want
        $actions = ['create', 'edit', 'delete'];

        // Ensure your existing view permissions also exist (optional but recommended)
        foreach ($modules as $module) {
            Permission::firstOrCreate(['name' => "{$module}.view", 'guard_name' => $guard]);
        }

        // Create create/edit/delete permissions
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => $guard]);
            }
        }
    }
}
