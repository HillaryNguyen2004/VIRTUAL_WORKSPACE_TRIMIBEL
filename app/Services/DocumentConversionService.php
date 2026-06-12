<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class DocumentConversionService
{
    private function nodeBinary(): string
    {
        return config('services.node_binary', 'node');
    }

    public function importDocxToHtml(string $docxPath, string $htmlPath): void
    {
        $process = new Process([
            $this->nodeBinary(),
            base_path('tools/docx-converter/import-docx.js'),
            $docxPath,
            $htmlPath,
        ]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(60);
        $process->mustRun();
    }

    public function exportHtmlToDocx(string $htmlPath, string $docxPath): void
    {
        $process = new Process([
            $this->nodeBinary(),
            base_path('tools/docx-converter/export-docx.js'),
            $htmlPath,
            $docxPath,
        ]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(60);
        $process->mustRun();
    }
}
