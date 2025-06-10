<?php

namespace App\Repositories;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// All create,get,update, delete methods related to User model is here
class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function updatePassword(User $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
        ])->save();
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function findOrCreateFromGoogle($googleUser): User
    {
        return $this->firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'password' => bcrypt(\Str::random(24)),
            ]
        );
    }

    public function createFromRequest($request): User
    {
        return User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
    }
}