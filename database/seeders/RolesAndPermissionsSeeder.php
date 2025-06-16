<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'make task']);
        Permission::create(['name' => 'assign user to task']);
        Permission::create(['name' => 'edit task']);
        Permission::create(['name' => 'manage user']);
        Permission::create(['name' => 'view task']);

        // Create roles and assign permissions
        $admin = Role::create(['name' => 'admin']);
        $staff = Role::create(['name' => 'staff']);
        $user = Role::create(['name' => 'user']);

        $admin->givePermissionTo(['make task', 'assign user to task', 'edit task', 'manage user', 'view task']);
        $staff->givePermissionTo(['make task', 'assign user to task', 'edit task', 'view task']);
        $user->givePermissionTo(['view task']);
    }
}
