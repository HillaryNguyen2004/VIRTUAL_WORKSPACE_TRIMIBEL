<?php 
namespace App\Services;

use Illuminate\Support\Facades\Auth;

class UserRoleRedirectService
{
    public function getDashboardRoute()
    {
        // if (!Auth::check()) {
        //     return route('login');
        // }

        // $role = Auth::user()->roles;

        // return match ($role) {
        //     'admin' => route('admin.dashboard'),
        //     'staff' => route('staff.dashboard'),
        //     default => route('user.dashboard'),
        // };
            $user = Auth::user();

            if ($user->hasRole('admin')) {
                return route('admin.dashboard');
            }
            elseif ($user->hasRole('subadmin')) {
                return route('subadmin.dashboard');
            }
             elseif ($user->hasRole('staff')) {
                return route('staff.dashboard');
            } elseif ($user->hasRole('user')) {
                return route('user.dashboard');
            }

            return route('login'); // fallback
        }
}