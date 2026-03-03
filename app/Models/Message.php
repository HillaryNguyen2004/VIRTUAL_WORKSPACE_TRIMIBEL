<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['conversation_id', 'user_id', 'content', 'type', 'metadata', 'file_name', 'file_path', 'file_size', 'file_type', 'platform', 'direction', 'sent_by_user_id'];

    protected $casts = [
        'metadata' => 'array',
    ];

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

    public function whatsappStatus()
    {
        return $this->hasOne(WhatsAppMessageStatus::class);
    }

    /**
     * Check if message has a file attachment
     */
    public function hasFile()
    {
        return !empty($this->file_path);
    }

    /**
     * Get file URL for download
     */
    public function getFileUrl()
    {
        if (!$this->hasFile()) {
            return null;
        }
        
        return asset('storage/' . $this->file_path);
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
