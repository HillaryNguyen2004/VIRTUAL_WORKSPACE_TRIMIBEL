<?php

// app/Policies/UserPolicy.php
namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Spatie\Permission\Models\Role;

class UserPolicy
{
    private function maxLevel(User $u): int
    {
        return (int) ($u->roles()->max('level') ?? 0);
    }

    public function assignRole(User $actor, User $target, string $roleName): Response
    {
        if ($actor->id === $target->id) {
            return Response::deny("You can't change your own role.");
        }
        if ($roleName === 'substaff') {
            $hasPermission = $actor->hasRole('admin')
                || $actor->can('staff.substaff.create')                         // subadmin / direct perms
                || $actor->hasDepartmentRolePermission('staff.substaff.create'); // staff via dept-role table
            if (!$hasPermission) {
                return Response::deny("You don't have permission to create substaff.");
            }
        }

        $role = Role::where('name', $roleName)->where('guard_name', 'web')->firstOrFail();

        $actorMax = $this->maxLevel($actor);
        $targetMax = $this->maxLevel($target);

        if ($actorMax <= (int) $role->level) {
            return Response::deny("You can't assign a role equal/higher than yours.");
        }

        if ($actorMax <= $targetMax) {
            $isAdminDemotion = $actor->hasRole('admin') && $target->hasRole('admin') && $roleName !== 'admin';
            if (!$isAdminDemotion) {
                return Response::deny("You can't manage a user with equal/higher role.");
            }
        }

        return Response::allow();
    }

    /**
     * This controls what permissions actor can grant to target (direct perms).
     * Waterfall: requested perms must be subset of actor's effective perms.
     */
    public function syncPermissions(User $actor, User $target, array $permissionNames): Response
    {
        if ($actor->id === $target->id) {
            return Response::deny("You can't change your own permissions.");
        }

        $actorMax = $this->maxLevel($actor);
        $targetMax = $this->maxLevel($target);

        if ($actorMax <= $targetMax) {
            return Response::deny("You can't manage a user with equal/higher role.");
        }

        $allowed = collect($actor->delegatablePermissionNames());
        $requested = collect($permissionNames);

        $diff = $requested->diff($allowed);
        if ($diff->isNotEmpty()) {
            return Response::deny("You tried to grant permissions you don't have: " . $diff->implode(', '));
        }

        return Response::allow();
    }
}
