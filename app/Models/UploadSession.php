<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'original_filename',
        'total_size',
        'total_chunks',
        'uploaded_chunks',
        'folder_id',
        'status',
        'assembled_path',
        'error_message',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'total_size' => 'integer',
        'total_chunks' => 'integer',
        'uploaded_chunks' => 'integer',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(FileChunk::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && Carbon::now()->isAfter($this->expires_at);
    }

    public function isComplete(): bool
    {
        return (int) $this->uploaded_chunks >= (int) $this->total_chunks;
    }

    public function progressPercent(): float
    {
        if ($this->total_chunks <= 0) return 0.0;
        return round(($this->uploaded_chunks / $this->total_chunks) * 100, 1);
    }
}
