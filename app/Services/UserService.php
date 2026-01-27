<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Repositories\UserRepositoryInterface;
use App\Models\ActivityLog;

class UserService
{
    protected $userRepo;

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
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

        if ($originalRole !== $newRole) {
            ActivityLog::create([
                'user_id' => Auth::id(),
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

    // public function createUser(array $data): User
    // {
    //     $password = Hash::make(Str::random(12));

    //     $user = $this->userRepo->create([
    //         'name' => $data['name'],
    //         'email' => $data['email'],
    //         'password' => $password,
    //         'team_leader_id' => $data['team_leader_id'] ?? null,
    //     ]);

    //     $user->assignRole($data['roles']);
    //     ActivityLog::create([
    //     'user_id' => Auth::id(), // admin performing the creation
    //     'action' => 'User Created',
    //     'description' => "Admin created a {$data['roles']} with ID {$user->id} and email {$user->email}."
    // ]);

    //     return $user;
    // }

    public function createUser(array $data): User
{
    $password = Hash::make(Str::random(12));

    // Generate unique username
    $username = $this->generateUniqueUsername();

    $user = $this->userRepo->create([
        'name'          => $data['name'],
        'email'         => $data['email'],
        'password'      => $password,
        'department_id' => $data['department_id'] ?? null,
        'team_leader_id'=> $data['team_leader_id'] ?? null,
        'username'      => $username,
    ]);

    $user->assignRole($data['roles']);

    ActivityLog::create([
        'user_id'    => Auth::id(), // admin performing the creation
        'action'     => 'User Created',
        'description'=> "Admin created a {$data['roles']} with ID {$user->id}, email {$user->email}, and username {$user->username}.",
    ]);

    return $user;
}

    /**
     * Generate a unique alphanumeric username.
     */
    private function generateUniqueUsername(): string
{
    do {
        // Example: random string of 6–10 chars (letters + numbers, no special chars)
        $username = Str::upper(Str::random(8)); 
        // Or if you want "userX" pattern:
        // $username = 'user' . (User::max('id') + 1);
    } while (User::where('username', $username)->exists());

    return $username;
}


}
