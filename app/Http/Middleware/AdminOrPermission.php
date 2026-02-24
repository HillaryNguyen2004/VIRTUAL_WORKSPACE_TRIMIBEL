<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOrPermission
{
    public function handle(Request $request, Closure $next, string $ability, ...$permissions)
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // Admin always allowed
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Subadmin uses direct Spatie permissions
        if ($user->hasRole('subadmin')) {
            // Check primary ability
            if ($user->can($ability)) {
                return $next($request);
            }
            // Check any additional permissions passed
            foreach ($permissions as $permission) {
                if ($user->can($permission)) {
                    return $next($request);
                }
            }

            abort(403);
        }

        $roleAbilityMap = [
            'admin' => 'admin.dashboard.view',
            'staff' => 'staff.dashboard.view',
            'user'  => 'user.dashboard.view',
        ];

        foreach ($roleAbilityMap as $role => $perm) {
            if ($ability === $perm && $user->hasRole($role)) {
                return $next($request);
            }
        }


        // Department-based permission for user/staff
        if (method_exists($user, 'hasDepartmentRolePermission') && $user->hasDepartmentRolePermission($ability)) {
            return $next($request);
        }

        abort(403);
    }
}
