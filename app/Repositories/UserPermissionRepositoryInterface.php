<?php

namespace App\Repositories;

interface UserPermissionRepositoryInterface
{
    public function getStaffWithPermissions();
    public function getAllPermissions();
    // public function updateUserPermissions(int $userId, array $permissions): void;
    public function updateRolePermissions(string $roleName, array $permissions): void;
}
