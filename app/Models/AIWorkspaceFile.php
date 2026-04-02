<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIWorkspaceFile extends Model
{
    use HasFactory;

    public const SUPPORTED_EXTENSIONS = ['pdf', 'txt', 'md', 'docx', 'pptx', 'xlsx'];

    protected $table = 'ai_workspace_files';

    protected $fillable = [
        'workspace_id',
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'chunk_count',
        'ingest_status',
        'ingest_error',
        'ingested_at',
    ];

    protected $casts = [
        'ingested_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: A file belongs to a workspace
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(AIWorkspace::class);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('ingest_status', $status);
    }

    /**
     * Scope: Filter pending files
     */
    public function scopePending($query)
    {
        return $query->where('ingest_status', 'pending');
    }

    /**
     * List supported upload formats
     */
    public static function supportedFormats(): array
    {
        return self::SUPPORTED_EXTENSIONS;
    }

    /**
     * Check if file is a supported format
     */
    public static function isSupportedFormat(string $extension): bool
    {
        return in_array(strtolower($extension), self::SUPPORTED_EXTENSIONS, true);
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }
}
