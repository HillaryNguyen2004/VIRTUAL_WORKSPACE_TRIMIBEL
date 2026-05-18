<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['conversation_id', 'user_id', 'content', 'type', 'metadata', 'file_name', 'file_path', 'file_size', 'file_type', 'platform', 'direction', 'sent_by_user_id'];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $appends = ['file_url'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function readBy()
    {
        return $this->belongsToMany(User::class, 'message_reads')
                    ->withTimestamps()
                    ->withPivot('read_at');
    }

    /**
     * Check if message has a file attachment
     */
    public function hasFile()
    {
        return !empty($this->file_path);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->hasFile()) {
            return null;
        }

        $disk = Storage::disk();

        // For S3 private objects, generate a presigned URL (60 min expiry)
        if (config('filesystems.default') === 's3') {
            try {
                return $disk->temporaryUrl($this->file_path, now()->addMinutes(60));
            } catch (\Exception) {
                // Fallback to plain URL if bucket is public or temporaryUrl unsupported
                return $disk->url($this->file_path);
            }
        }

        return storageUrl($this->file_path);
    }

    /**
     * Check if file is an image
     */
    public function isImage()
    {
        if (!$this->hasFile()) {
            return false;
        }
        
        $imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($this->file_type, $imageTypes);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSize()
    {
        if (!$this->file_size) {
            return '';
        }
        
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
