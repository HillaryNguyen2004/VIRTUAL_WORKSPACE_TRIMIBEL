<?php

// database/seeders/RoleSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(['name' => 'admin',    'guard_name' => 'web'], ['level' => 100]);
        Role::updateOrCreate(['name' => 'subadmin', 'guard_name' => 'web'], ['level' => 50]);
        Role::updateOrCreate(['name' => 'staff',    'guard_name' => 'web'], ['level' => 30]);
        Role::updateOrCreate(['name' => 'user',     'guard_name' => 'web'], ['level' => 10]);
    }
}

