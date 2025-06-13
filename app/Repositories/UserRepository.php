<?php

namespace App\Repositories;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;


class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function updatePassword(User $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
        ])->save();
    }

    public function findOrCreateFromGoogle($googleUser): User
    {
        return $this->model->firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'password' => bcrypt(Str::random(24)),
            ]
        );
    }

    public function createFromRequest($request): User
    {
        return $this->create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
    }
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function updateName($user, $firstName, $lastName): void
    {
        $this->update($user, ['name' => $firstName . ' ' . $lastName]);
    }

    public function updateAvatar($user, UploadedFile $file): void
    {
        $safeEmail = str_replace(['@', '.'], '_', $user->email);
        $extension = $file->getClientOriginalExtension();
        $filename = $safeEmail . '.' . $extension;

        $file->move(public_path('img/user_avatar/'), $filename);
    }
}