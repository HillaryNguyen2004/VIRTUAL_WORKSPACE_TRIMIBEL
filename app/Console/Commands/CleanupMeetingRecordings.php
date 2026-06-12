<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupMeetingRecordings extends Command
{
    protected $signature = 'meeting:cleanup-recordings {--hours=2 : Delete files older than this many hours}';
    protected $description = 'Delete orphaned temporary recording files from storage/app/recordings';

    public function handle()
    {
        $dir = storage_path('app/recordings');

        if (!is_dir($dir)) {
            $this->info('No recordings directory found.');
            return 0;
        }

        $maxAge = (int) $this->option('hours') * 3600;
        $cutoff = time() - $maxAge;
        $deleted = 0;

        foreach (glob("{$dir}/*.{mp4,flac,webm}", GLOB_BRACE) as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
                $this->line("Deleted: " . basename($file));
            }
        }

        $this->info("Cleanup done. {$deleted} file(s) removed.");
        return 0;
    }
}
