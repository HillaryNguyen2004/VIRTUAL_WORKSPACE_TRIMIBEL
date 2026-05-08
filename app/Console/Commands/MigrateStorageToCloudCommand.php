<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\PersonalFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateStorageToCloudCommand extends Command
{
    protected $signature = 'storage:migrate-to-cloud
        {--chunk=300 : Chunk size for batch updates}
        {--documents : Migrate online docs files}
        {--personal : Migrate personal storage files}
        {--dry-run : Only report what would be copied}
        {--delete-local : Delete local files after successful copy}';

    protected $description = 'Copy online docs and personal storage files from local disk to the default disk';

    public function handle(): int
    {
        $chunkSize = max(50, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $deleteLocal = (bool) $this->option('delete-local');
        $migrateDocuments = (bool) $this->option('documents');
        $migratePersonal = (bool) $this->option('personal');

        if (!$migrateDocuments && !$migratePersonal) {
            $migrateDocuments = true;
            $migratePersonal = true;
        }

        $this->info('Source disk: local');
        $this->info('Destination disk: ' . config('filesystems.default'));
        if ($dryRun) {
            $this->warn('Dry run: no files will be copied.');
        }

        $stats = [
            'scanned' => 0,
            'copied' => 0,
            'skipped' => 0,
            'missing' => 0,
            'failed' => 0,
        ];

        if ($migrateDocuments) {
            $this->migrateDocuments($chunkSize, $dryRun, $deleteLocal, $stats);
        }

        if ($migratePersonal) {
            $this->migratePersonal($chunkSize, $dryRun, $deleteLocal, $stats);
        }

        $this->newLine();
        $this->info('Migration summary');
        $this->line('Scanned: ' . $stats['scanned']);
        $this->line('Copied: ' . $stats['copied']);
        $this->line('Skipped: ' . $stats['skipped']);
        $this->line('Missing: ' . $stats['missing']);
        $this->line('Failed: ' . $stats['failed']);

        return self::SUCCESS;
    }

    private function migrateDocuments(int $chunkSize, bool $dryRun, bool $deleteLocal, array &$stats): void
    {
        $total = Document::query()->count();
        if ($total === 0) {
            $this->info('No documents found.');
            return;
        }

        $this->info("Migrating {$total} documents...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Document::query()
            ->select(['id', 'html_path', 'docx_path', 'xlsx_path', 'pptx_path'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($documents) use (&$stats, $dryRun, $deleteLocal, $bar): void {
                foreach ($documents as $document) {
                    $paths = array_filter([
                        $document->html_path,
                        $document->docx_path,
                        $document->xlsx_path,
                        $document->pptx_path,
                    ], fn ($value) => is_string($value) && $value !== '');

                    foreach ($paths as $path) {
                        $this->copyPath($path, $dryRun, $deleteLocal, $stats);
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
    }

    private function migratePersonal(int $chunkSize, bool $dryRun, bool $deleteLocal, array &$stats): void
    {
        $total = PersonalFile::query()->count();
        if ($total === 0) {
            $this->info('No personal files found.');
            return;
        }

        $this->info("Migrating {$total} personal files...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        PersonalFile::query()
            ->select(['id', 'stored_path'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($files) use (&$stats, $dryRun, $deleteLocal, $bar): void {
                foreach ($files as $file) {
                    if (is_string($file->stored_path) && $file->stored_path !== '') {
                        $this->copyPath($file->stored_path, $dryRun, $deleteLocal, $stats);
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
    }

    private function copyPath(string $path, bool $dryRun, bool $deleteLocal, array &$stats): void
    {
        $stats['scanned']++;

        $localDisk = Storage::disk('local');
        $destDisk = Storage::disk();

        if ($destDisk->exists($path)) {
            $stats['skipped']++;
            return;
        }

        if (!$localDisk->exists($path)) {
            $stats['missing']++;
            return;
        }

        if ($dryRun) {
            $stats['copied']++;
            return;
        }

        $stream = $localDisk->readStream($path);
        try {
            if ($stream !== false) {
                $destDisk->put($path, $stream);
            } else {
                $destDisk->put($path, $localDisk->get($path));
            }
        } catch (\Throwable $error) {
            $stats['failed']++;
            if (is_resource($stream)) {
                fclose($stream);
            }
            return;
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($deleteLocal) {
            $localDisk->delete($path);
        }

        $stats['copied']++;
    }
}
