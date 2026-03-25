<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AIWorkspace extends Model
{
    use HasFactory;

    protected $table = 'ai_workspaces';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'slug',
        'visibility',
        'folder_path',
        'file_count',
        'storage_size',
        'status',
        'last_ingested_at',
    ];

    protected $casts = [
        'last_ingested_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create a new instance of the model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Relationship: A workspace belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A workspace has many files
     */
    public function files(): HasMany
    {
        return $this->hasMany(AIWorkspaceFile::class, 'workspace_id');
    }

    /**
     * Get pending files to be ingested
     */
    public function pendingFiles()
    {
        return $this->files()->where('ingest_status', 'pending');
    }

    /**
     * Get ingested files
     */
    public function ingestedFiles()
    {
        return $this->files()->where('ingest_status', 'completed');
    }

    /**
     * Generate a unique slug from name
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = 1;
        $originalSlug = $slug;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Get the folder path for this workspace
     */
    public function getFolderPath(): string
    {
        return $this->folder_path;
    }

    /**
     * Get the full storage path
     */
    public static function getStoragePath(): string
    {
        return base_path('chatbot_service/data');
    }

    /**
     * Create workspace folder
     */
    public function createFolder(): bool
    {
        $path = $this->getFolderPath();
        if (!is_dir($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }

    /**
     * Scope: Filter by active status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Update storage size
     */
    public function updateStorageSize(): void
    {
        $this->storage_size = $this->files()->sum('file_size');
        $this->save();
    }

    /**
     * Update file count
     */
    public function updateFileCount(): void
    {
        $this->file_count = $this->files()->count();
        $this->save();
    }
}
