<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

// this service handles the login logic for the AuthController
class LoginService
{
    public function login(array $credentials, bool $remember = false): User
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => __('auth.no_user_found'),
            ]);
        }

        if ($user->isBlocked()) {
            throw ValidationException::withMessages([
                'email' => __('auth.account_blocked'),
            ]);
        }

        if (Auth::attempt($credentials, $remember)) {
            $user->resetLoginAttempts();
            return $user;
        } else {
            $user->incrementLoginAttempts();

            if ($user->isBlocked()) {
                throw ValidationException::withMessages([
                    'email' => __('auth.account_blocked'),
                ]);
            }

            throw ValidationException::withMessages([
                'password' => __('auth.incorrect_password'),
            ]);
        }
    }
}
