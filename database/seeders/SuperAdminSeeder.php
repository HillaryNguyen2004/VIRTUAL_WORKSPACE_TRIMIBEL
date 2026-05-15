<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::updateOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['level' => 200]
        );

        $user = User::firstOrCreate(
            ['email' => 'user31@mail.com'],
            [
                'name' => 'Hillary Nguyen',
                'username' => 'hillary.nguyen',
                'password' => Hash::make('password'),
            ]
        );

        if (!$user->hasRole($role->name)) {
            $user->assignRole($role);
        }
    }
}
