<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $file_name
 * @property string $original_name
 * @property string $file_path
 * @property string $mime_type
 * @property int $file_size
 * @property int $chunk_count
 * @property string $ingest_status
 * @property string|null $ingest_error
 * @property \Illuminate\Support\Carbon|null $ingested_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AIWorkspaceFile extends Model
{
    use HasFactory;

    public const SUPPORTED_EXTENSIONS = ['pdf', 'txt', 'md', 'docx', 'xlsx', 'csv'];

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
