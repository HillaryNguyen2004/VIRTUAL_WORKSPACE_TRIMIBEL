<?php

namespace App\Repositories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DocumentRepository
{
    /**
     * Get all owned documents for a user
     */
    public function getOwnedDocuments(User $user): Collection
    {
        return $user->ownedDocuments()
            ->with('owner')
            ->latest()
            ->get();
    }

    /**
     * Get owned documents for a user filtered by type
     */
    public function getOwnedDocumentsByType(User $user, string $type): Collection
    {
        return $user->ownedDocuments()
            ->where('type', $type)
            ->with('owner')
            ->latest()
            ->get();
    }

    /**
     * Get all shared documents for a user
     */
    public function getSharedDocuments(User $user): Collection
    {
        return $user->sharedDocuments()
            ->with('owner')
            ->latest()
            ->get();
    }

    /**
     * Get shared documents for a user filtered by type
     */
    public function getSharedDocumentsByType(User $user, string $type): Collection
    {
        return $user->sharedDocuments()
            ->where('type', $type)
            ->with('owner')
            ->latest()
            ->get();
    }

    /**
     * Get shared users for a document
     */
    public function getSharedUsers(Document $document): Collection
    {
        return $document->sharedUsers()
            ->orderBy('email')
            ->get();
    }

    /**
     * Get share candidates (users excluding owner)
     */
    public function getShareCandidates(Document $document): Collection
    {
        return User::query()
            ->whereKeyNot($document->owner_id)
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email']);
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
