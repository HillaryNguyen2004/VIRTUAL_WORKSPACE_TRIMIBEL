<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (auth()->check()) {
        \Log::info('User Role: ' . auth()->user()->role); // Log the user's role
        if (auth()->user()->role === $role) {
            return $next($request);
        }
    }

    abort(403, 'Unauthorized');
    }
}
