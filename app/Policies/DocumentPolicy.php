<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($document->owner_id === $user->id) {
            return true;
        }

        return $document->sharedUsers()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Document $document): bool
    {
        if ($document->owner_id === $user->id) {
            return true;
        }

        $share = $document->sharedUsers()->where('users.id', $user->id)->first();
        return $share?->pivot?->permission === 'edit';
    }

    public function share(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id;
    }
}
