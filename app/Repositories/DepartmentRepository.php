<?php

namespace App\Repositories;

use App\Models\Department;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Collection;

class DepartmentRepository
{
    /**
     * Get all departments with their users
     */
    public function getDepartmentsWithUsers(): Collection
    {
        return Department::with(['users' => function ($query) {
            $query->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('name', 'admin');
            })->orderBy('name');
        }])->orderBy('name')->get();
    }

    /**
     * Get available users (not assigned to any department, excluding admins)
     */
    public function getAvailableUsers(): Collection
    {
        return User::whereNull('department_id')
            ->orWhere('department_id', 0)
            ->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('name', 'admin');
            })
            ->with('department')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new department
     */
    public function createDepartment(array $data): Department
    {
        return Department::create($data);
    }

    /**
     * Update a department
     */
    public function updateDepartment(Department $department, array $data): bool
    {
        return $department->update($data);
    }

    /**
     * Delete a department
     */
    public function deleteDepartment(Department $department): bool
    {
        return $department->delete();
    }

    /**
     * Assign a user to a department
     */
    public function assignUserToDepartment(int $userId, int $departmentId): bool
    {
        $user = User::findOrFail($userId);
        $user->department_id = $departmentId;
        return $user->save();
    }

    /**
     * Remove a user from a department
     */
    public function removeUserFromDepartment(User $user): bool
    {
        $user->department_id = null;
        return $user->save();
    }

    /**
     * Update department permissions
     */
    public function updateDepartmentPermissions(Department $department, array $permissionIds): void
    {
        $department->permissions()->sync($permissionIds);

        // Apply to all users in this department except admin
        $users = User::query()
            ->where('department_id', $department->id)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
            ->get();

        foreach ($users as $user) {
            $user->syncDepartmentAddonPermissions();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
