<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'owner_id',
        'title',
        'type',
        'html_path',
        'searchable_text',
        'docx_path',
        'xlsx_path',
        'pptx_path',
        'last_edited_by',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_shares')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function getUserPermission(User $user): ?string
    {
        if ($this->owner_id === $user->id) {
            return 'edit';
        }

        $share = $this->sharedUsers()->where('users.id', $user->id)->first();
        return $share?->pivot?->permission;
    }
}
