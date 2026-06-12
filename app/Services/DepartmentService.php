<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Repositories\DepartmentRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DepartmentService
{
    protected DepartmentRepository $repository;

    public function __construct(DepartmentRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all departments with their users and available staff
     */
    public function getDepartmentsWithUsers(): array
    {
        try {
            $departments = $this->repository->getDepartmentsWithUsers();
            $availableUsers = $this->repository->getAvailableUsers();

            return [
                'departments' => $departments,
                'availableUsers' => $availableUsers,
            ];
        } catch (\Exception $e) {
            Log::error('Get Departments Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new department
     */
    public function createDepartment(array $data): Department
    {
        try {
            return $this->repository->createDepartment($data);
        } catch (\Exception $e) {
            Log::error('Create Department Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a department
     */
    public function updateDepartment(Department $department, array $data): bool
    {
        try {
            return $this->repository->updateDepartment($department, $data);
        } catch (\Exception $e) {
            Log::error('Update Department Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a department
     */
    public function deleteDepartment(Department $department): bool
    {
        try {
            return $this->repository->deleteDepartment($department);
        } catch (\Exception $e) {
            Log::error('Delete Department Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign staff to a department
     */
    public function assignStaffToDepartment(int $userId, int $departmentId): bool
    {
        try {
            // Verify department exists
            Department::findOrFail($departmentId);
            
            return $this->repository->assignUserToDepartment($userId, $departmentId);
        } catch (\Exception $e) {
            Log::error('Assign Staff Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove staff from a department
     */
    public function removeStaffFromDepartment(Department $department, User $user): bool
    {
        try {
            // Verify user belongs to department
            if ($user->department_id !== $department->id) {
                throw new \InvalidArgumentException('Nhân viên không thuộc phòng ban này.');
            }

            return $this->repository->removeUserFromDepartment($user);
        } catch (\Exception $e) {
            Log::error('Remove Staff Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update department permissions
     */
    public function updateDepartmentPermissions(Department $department, array $permissionIds): void
    {
        try {
            // Ensure it's an array
            if (!is_array($permissionIds)) {
                $permissionIds = [];
            }

            $this->repository->updateDepartmentPermissions($department, $permissionIds);
        } catch (\Exception $e) {
            Log::error('Update Department Permissions Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
