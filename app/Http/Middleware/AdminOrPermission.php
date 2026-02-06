<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOrPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();

        if (!$user) abort(401);

        // Admin bypass: admin can access everything
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Non-admin must have at least one required permission
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
