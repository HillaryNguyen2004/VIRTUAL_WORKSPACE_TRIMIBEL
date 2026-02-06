<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DashboardPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cached roles/permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // access admin dashboard UI
            'admin.dashboard.view',

            // blocks
            'admin.users.view',
            'admin.roles.view',
            'admin.company_hours.view',
            'admin.projects.view',
            'admin.attendance.view',
            'admin.campaigns.view',
            'admin.email_templates.view',
            'admin.activity_logs.view',
        ];

        // Create permissions if not exist
        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // Assign all to admin role (recommended)
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $adminRole->givePermissionTo($permissions);

        // OPTIONAL: if you already have 'subadmin' role and want it created
        // (do NOT give permissions by default; let admin choose per subadmin user)
        Role::firstOrCreate([
            'name' => 'subadmin',
            'guard_name' => 'web',
        ]);

        // ✅ Admin has FULL global access
        $admin->syncPermissions(Permission::where('guard_name', $guard)->get());

        // ✅ Subadmin role should be "base only" (or empty)
        // so every subadmin is customized by direct permissions
        $subadmin->syncPermissions([]); // important

        // Re-cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
