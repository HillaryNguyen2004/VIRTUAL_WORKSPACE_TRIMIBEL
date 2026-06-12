<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalFolderShare extends Model
{
    protected $fillable = [
        'folder_id',
        'user_id',
        'shared_by',
        'permission',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(PersonalFolder::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }
}
