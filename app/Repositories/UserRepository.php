<?php

namespace App\Repositories;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

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

    // public function findOrCreateFromGoogle($googleUser): User
    // {
    //     return $this->model->firstOrCreate(
    //         ['email' => $googleUser->getEmail()],
    //         [
    //             'name' => $googleUser->getName(),
    //             'password' => bcrypt(Str::random(24)),
    //         ]
    //     );
    // }


    public function findOrCreateFromGoogle($googleUser): User
    {
        $user = $this->model->firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'password' => bcrypt(Str::random(24)),
            ]
        );

        // If user has no role, assign 'user' role
        if (!$user->hasAnyRole()) {
            $user->assignRole('user');
        }

        return $user;
    }


    public function createFromRequest($request): User
    {
        $data = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->numbers()->symbols()],
        ])->validate(); // throws on failure

        $user = $this->create([
            'name' => trim("{$data['first_name']} {$data['last_name']}"),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Ensure the returned value is an instance of App\Models\User
        return User::find($user->id);
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

    public function filterUsers(array $filters, int $perPage = 3)
    {
        $query = $this->model->with('roles');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        $sortOrder = $filters['sort'] ?? 'asc';
        $query->orderBy('name', in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc');

        // return $query->get();
        return $query->paginate($perPage)->appends($filters);
    }

}