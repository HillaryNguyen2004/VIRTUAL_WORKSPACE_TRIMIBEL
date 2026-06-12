<?php

namespace App\Policies;

use App\Models\AIWorkspaceFile;
use App\Models\User;

class AIWorkspaceFilePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Workspace owner can delete any file; uploader can delete their own file.
     */
    public function delete(User $user, AIWorkspaceFile $file): bool
    {
        $workspace = $file->workspace;

        if ((int) $workspace->user_id === (int) $user->id) {
            return true;
        }

        return (int) $file->uploaded_by === (int) $user->id;
    }
}
