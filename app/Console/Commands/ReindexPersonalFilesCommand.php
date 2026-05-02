<?php

namespace App\Console\Commands;

use App\Models\PersonalFile;
use App\Services\PersonalFileSearchService;
use Illuminate\Console\Command;

class ReindexPersonalFilesCommand extends Command
{
    protected $signature = 'online-docs:reindex-personal-files {--chunk=200 : Chunk size for batch updates}';

    protected $description = 'Rebuild searchable_text for files uploaded in Personal Storage';

    public function __construct(private PersonalFileSearchService $searchService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $chunkSize = max(50, (int) $this->option('chunk'));
        $total = PersonalFile::query()->count();

        if ($total === 0) {
            $this->info('No personal files found.');
            return self::SUCCESS;
        }

        $this->info("Reindexing {$total} personal files...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        PersonalFile::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($files) use ($bar): void {
                foreach ($files as $file) {
                    $text = $this->searchService->buildSearchableTextForStoredFile($file);
                    $file->update(['searchable_text' => $text]);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Personal files reindex completed.');

        return self::SUCCESS;
    }
}
