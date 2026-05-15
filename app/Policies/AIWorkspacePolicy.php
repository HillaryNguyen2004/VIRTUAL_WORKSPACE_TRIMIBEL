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
        // private: only owner
        if ($workspace->visibility === 'private') {
            return (int) $workspace->user_id === (int) $user->id;
        }

        // public: everyone
        if ($workspace->visibility === 'public') {
            return true;
        }

        // team: only same team scope
        if ($workspace->visibility === 'team') {
            return in_array((int) $workspace->user_id, $this->getTeamScopeUserIds($user), true);
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
     * Determine whether the user can upload files to the workspace.
     * Public: any authenticated user. Team: team members only. Private: owner only.
     */
    public function upload(User $user, AIWorkspace $workspace): bool
    {
        if ((int) $workspace->user_id === (int) $user->id) {
            return true;
        }

        if (!$workspace->allow_others_upload) {
            return false;
        }

        if ($workspace->visibility === 'public') {
            return true;
        }

        if ($workspace->visibility === 'team') {
            return in_array((int) $user->id, $this->getTeamScopeUserIds($workspace->user), true);
        }

        return false;
    }

    /**
     * Determine whether the user can trigger ingest for the workspace.
     * Only owner can ingest files.
     */
    public function ingest(User $user, AIWorkspace $workspace): bool
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

    private function getTeamScopeUserIds(User $user): array
    {
        $leaderId = $user->team_leader_id ?: $user->id;

        $ids = User::query()
            ->where('id', $leaderId)
            ->orWhere('team_leader_id', $leaderId)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (!in_array((int) $user->id, $ids, true)) {
            $ids[] = (int) $user->id;
        }

        return $ids;
    }
}
