<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReindexOnlineDocsContentCommand extends Command
{
    public function __construct(private DocumentService $documentService)
    {
        parent::__construct();
    }

    protected $signature = 'online-docs:reindex-content {--chunk=500 : Chunk size for batch updates}';

    protected $description = 'Rebuild searchable content index for Online Docs from stored HTML files';

    public function handle(): int
    {
        $chunkSize = max(50, (int) $this->option('chunk'));
        $total = Document::query()->count();

        if ($total === 0) {
            $this->info('No documents found.');
            return self::SUCCESS;
        }

        $this->info("Reindexing {$total} documents...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Document::query()
            ->select(['id', 'title', 'type', 'html_path', 'docx_path'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($documents) use ($bar): void {
                foreach ($documents as $document) {
                    $html = '';
                    if ($document->html_path && Storage::disk()->exists($document->html_path)) {
                        try {
                            $html = (string) Storage::disk()->get($document->html_path);
                        } catch (\Throwable $error) {
                            $html = '';
                        }
                    }

                    if (trim($html) === '' && $document->type === 'docs' && $document->docx_path) {
                        $this->documentService->syncHtmlAndSearchFromDocx($document);

                        if ($document->html_path && Storage::disk()->exists($document->html_path)) {
                            try {
                                $html = (string) Storage::disk()->get($document->html_path);
                            } catch (\Throwable $error) {
                                $html = '';
                            }
                        }
                    }

                    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

                    $document->update([
                        'searchable_text' => $text,
                    ]);

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Reindex completed.');

        return self::SUCCESS;
    }
}
