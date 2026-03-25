<?php

namespace App\Policies;

use App\Models\AIWorkspace;
use App\Models\User;

class AIWorkspacePolicy
{
    /**
     * Perform pre-authorization checks.
     * Allow admins to bypass all checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Allow admins to do anything
        if ($user->hasRole('admin')) {
            return true;
        }

        return null; // proceed to the ability
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AIWorkspace $workspace): bool
    {
        // User can always view their own workspace
        if ((int) $workspace->user_id === (int) $user->id) {
            return true;
        }

        // Public workspaces can be viewed by anyone
        if ($workspace->visibility === 'public') {
            return true;
        }

        // Team workspaces - implement your team logic here if needed
        if ($workspace->visibility === 'team') {
            // Add team checking logic here
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AIWorkspace $workspace): bool
    {
        return (int) $workspace->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AIWorkspace $workspace): bool
    {
        return (int) $workspace->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AIWorkspace $workspace): bool
    {
        return (int) $workspace->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AIWorkspace $workspace): bool
    {
        return (int) $workspace->user_id === (int) $user->id;
    }
}
