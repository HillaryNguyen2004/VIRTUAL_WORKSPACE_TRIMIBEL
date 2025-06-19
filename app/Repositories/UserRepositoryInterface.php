<?php

namespace App\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function updatePassword(User $user, string $newPassword): void;
    public function findOrCreateFromGoogle($googleUser): User;
    public function createFromRequest($request): User;
    public function findByEmail(string $email): ?User;
    public function updateName($user, $firstName, $lastName): void;
    // New methods:
    public function filterUsers(array $filters);
    public function updateUser(User $user, array $data): void;
    public function deleteUser(User $user): bool;
    public function createUser(array $data): User;
}
