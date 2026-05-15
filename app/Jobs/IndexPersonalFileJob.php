<?php

namespace App\Jobs;

use App\Models\PersonalFile;
use App\Services\RagIndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IndexPersonalFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public function __construct(private int $fileId, private ?string $userId)
    {
    }

    public function handle(RagIndexService $ragIndex): void
    {
        set_time_limit(0);

        $file = PersonalFile::find($this->fileId);
        if (!$file) {
            return;
        }

        try {
            $chunkCount = $ragIndex->indexFile(
                storedPath: $file->stored_path,
                workspaceId: 'personal_file_' . $file->id,
                originalName: $file->original_name,
                userId: $this->userId,
            );

            $file->update([
                'ingest_status' => 'completed',
                'chunk_count'   => $chunkCount,
                'ingested_at'   => now(),
                'ingest_error'  => null,
            ]);
        } catch (\Throwable $error) {
            $file->update([
                'ingest_status' => 'failed',
                'ingest_error'  => $error->getMessage(),
            ]);

            Log::warning('IndexPersonalFileJob failed', [
                'file_id' => $file->id,
                'error' => $error->getMessage(),
            ]);
        }
    }
}
