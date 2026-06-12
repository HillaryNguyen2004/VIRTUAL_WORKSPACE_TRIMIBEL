<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileChunk extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'upload_session_id',
        'chunk_number',
        'chunk_size',
        'chunk_hash',
        'stored_path',
        'status',
    ];
    
    protected $casts = [
        'chunk_size' => 'integer',
        'chunk_number' => 'integer',
    ];
    
    public function session()
    {
        return $this->belongsTo(UploadSession::class, 'upload_session_id');
    }
    
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
