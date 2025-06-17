<?php 
namespace App\Services;

use Illuminate\Support\Facades\Auth;

class UserRoleRedirectService
{
    public function getDashboardRoute(): string
    {
        if (!Auth::check()) {
            return route('login');
        }

        $role = Auth::user()->roles;

        return match ($role) {
            'admin' => route('admin.dashboard'),
            'staff' => route('staff.dashboard'),
            default => route('user.dashboard'),
        };
    }
}