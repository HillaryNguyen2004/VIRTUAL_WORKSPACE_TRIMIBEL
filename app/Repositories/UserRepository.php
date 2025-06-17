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
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName);
        $timestamp = time();
        $extension = $file->getClientOriginalExtension();
        $filename = $cleanName . '_' . $timestamp . '.' . $extension;

        // Move the file to the desired directory
        $file->move(public_path('img/user_avatar/'), $filename);

        // Save the file name in the user_profile_photo column
        $user->user_profile_photo = $filename;
        $user->save();
    }
}