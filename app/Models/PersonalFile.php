<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalFile extends Model
{
    protected $fillable = [
        'user_id',
        'folder_id',
        'document_id',
        'stored_path',
        'original_name',
        'mime_type',
        'size',
        'searchable_text',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(PersonalFolder::class, 'folder_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
