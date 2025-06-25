<?php

namespace App\Repositories;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

    public function updateUser(User $user, array $data): void
    {
        $originalRole = $user->getRoleNames()->first();
        $user->name = $data['name'];

        if ($data['role'] === 'staff') {
            User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);

            if (!empty($data['team_members'])) {
                User::whereIn('id', $data['team_members'])->update(['team_leader_id' => $user->id]);
            }
        } else {
            User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);
        }

        $user->save();
        $user->syncRoles([$data['role']]);
        $newRole = $user->getRoleNames()->first();

        // ✅ Log if role changed
        if ($originalRole !== $newRole) {
            ActivityLog::create([
                'user_id' => auth()->id(), // admin performing the action
                'action' => 'Changed User Role',
                'description' => "Admin changed role of user {$user->name} (ID: {$user->id}) from '$originalRole' to '$newRole'."
            ]);
        }
    }

    public function deleteUser(User $user): bool
    {
        if (auth()->id() === $user->id || $user->hasRole('admin')) {
            return false;
        }

        return $user->delete();
    }

    public function createUser(array $data): User
    {
        $password = Hash::make(Str::random(12));

        $user = $this->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password,
            'team_leader_id' => $data['team_leader_id'] ?? null,
        ]);

        $user->assignRole($data['roles']);
        ActivityLog::create([
        'user_id' => Auth::id(), // admin performing the creation
        'action' => 'User Created',
        'description' => "Admin created a {$data['roles']} with ID {$user->id} and email {$user->email}."
    ]);

        return $user;
    }
}