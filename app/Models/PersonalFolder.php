<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalFolder extends Model
{
    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'share_token',
        'share_link_enabled',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PersonalFile::class, 'folder_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(PersonalFolderShare::class, 'folder_id');
    }

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'personal_folder_shares', 'folder_id', 'user_id')
            ->withPivot(['permission', 'shared_by'])
            ->withTimestamps();
    }
}
