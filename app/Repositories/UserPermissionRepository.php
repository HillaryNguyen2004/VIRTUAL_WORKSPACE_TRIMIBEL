<?php

namespace App\Repositories;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Repositories\UserPermissionRepositoryInterface;

class UserPermissionRepository implements UserPermissionRepositoryInterface
{
    public function getStaffWithPermissions()
    {
        return User::role('staff')->with('permissions')->get();
    }

    public function getAllPermissions()
    {
        return Permission::all();
    }

    // public function updateUserPermissions(int $userId, array $permissions): void
    // {
    //     $user = User::findOrFail($userId);
    //     $user->syncPermissions($permissions);
    // }

    public function updateRolePermissions(string $roleName, array $permissions): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        
        // IMPORTANT: Use sync() to REPLACE all permissions (removes unchecked ones)
        // DO NOT use attach() which would ADD permissions without removing
        $role->syncPermissions($permissions);
        
        // Clear Spatie permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
