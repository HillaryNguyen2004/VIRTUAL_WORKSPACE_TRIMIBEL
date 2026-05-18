<?php

namespace App\Logging;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\AbstractProcessingHandler;

class DatabaseLogger extends AbstractProcessingHandler
{
    /**
     * Handle a record.
     */
    protected function write(array $record): void
    {
        try {
            DB::table('logs')->insert([
                'level' => $record['level_name'],
                'message' => $record['message'],
                'context' => json_encode($record['context'] ?? [], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Silently fail if logs table doesn't exist yet
            // This prevents errors during migration
        }
    }
}
