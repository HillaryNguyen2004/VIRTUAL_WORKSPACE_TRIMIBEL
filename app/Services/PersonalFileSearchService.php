<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class PersonalFileSearchService
{
    /**
     * Rank personal files by BM25 relevance to a query.
     */
    public function rankFiles(Collection $files, string $query): Collection
    {
        if ($files->isEmpty() || trim($query) === '') {
            return $files;
        }

        $tokens = $this->tokenize($query);
        if (empty($tokens)) {
            return $files;
        }

        // Pre-tokenize all docs and compute true average document length
        $docData = $files->map(function ($file) {
            $text = mb_strtolower((string) ($file->searchable_text ?? $file->original_name ?? ''));
            preg_match_all('/\w+/u', $text, $m);
            return ['file' => $file, 'tokens' => $m[0]];
        });

        $totalTokens = $docData->sum(fn ($d) => count($d['tokens']));
        $avgDl = $totalTokens > 0 ? $totalTokens / max(1, $docData->count()) : 300;

        return $docData->map(function ($d) use ($tokens, $avgDl) {
            $score = $this->bm25Score($d['tokens'], $tokens, $avgDl);
            $d['file']->bm25_score  = $score;
            $d['file']->search_score = $score;
            return $d['file'];
        })
        ->filter(fn ($f) => $f->bm25_score > 0)
        ->sortByDesc('bm25_score')
        ->values();
    }

    /**
     * Extract searchable text from an uploaded file before storing.
     */
    public function buildSearchableTextForUpload(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $name = $file->getClientOriginalName();

        try {
            $content = match ($ext) {
                'txt', 'md'   => $this->readText($file),
                'pdf'         => $this->readPdf($file),
                'docx'        => $this->readDocx($file),
                'xlsx', 'csv' => $this->readSpreadsheet($file),
                default       => '',
            };
        } catch (\Throwable) {
            $content = '';
        }

        $text = trim($name . "\n" . $content);
        // Strip invalid UTF-8 bytes so MySQL utf8mb4 column accepts the value
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        if ($clean !== false) {
            $text = $clean;
        }
        // Remove non-printable control characters except tab/newline
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        return mb_substr($text, 0, 65535);
    }

    /**
     * Update the filename portion inside searchable_text after a rename.
     */
    public function withUpdatedFileName(string $searchableText, string $newName): string
    {
        $lines = explode("\n", $searchableText, 2);
        $body = $lines[1] ?? '';
        return mb_substr(trim($newName . "\n" . $body), 0, 65535);
    }

    private function tokenize(string $text): array
    {
        preg_match_all('/\w+/u', strtolower($text), $matches);
        return array_filter($matches[0], fn ($t) => mb_strlen($t) >= 2);
    }

    private function bm25Score(array $docTokens, array $queryTokens, float $avgDl): float
    {
        $k1 = 1.5;
        $b  = 0.75;

        $dl = count($docTokens);
        if ($dl === 0) return 0.0;

        $freq = array_count_values($docTokens);
        $score = 0.0;

        foreach (array_unique($queryTokens) as $term) {
            $tf = $freq[$term] ?? 0;
            if ($tf === 0) continue;
            $score += ($tf * ($k1 + 1)) / ($tf + $k1 * (1 - $b + $b * $dl / $avgDl));
        }

        return $score;
    }

    private function readText(UploadedFile $file): string
    {
        return file_get_contents($file->getRealPath()) ?: '';
    }

    private function readPdf(UploadedFile $file): string
    {
        $src = $file->getRealPath();
        if (!$src || !file_exists($src)) {
            return '';
        }

        // Try pdftotext (poppler) — handles compressed/CID PDFs better
        $pdftotextBin = $this->findPdfToText();
        if ($pdftotextBin) {
            $tmp = tempnam(sys_get_temp_dir(), 'pdftxt_') . '.txt';
            $cmd = escapeshellarg($pdftotextBin) . ' -enc UTF-8 '
                . escapeshellarg($src) . ' '
                . escapeshellarg($tmp);
            exec($cmd, $out, $ret);
            if ($ret === 0 && file_exists($tmp)) {
                $text = (string) file_get_contents($tmp);
                @unlink($tmp);
                if (trim($text) !== '') {
                    return $text;
                }
            }
            @unlink($tmp);
        }

        // Fallback: smalot/pdfparser (pure PHP)
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($src);
            return $pdf->getText();
        } catch (\Throwable) {
            return '';
        }
    }

    private function findPdfToText(): ?string
    {
        foreach (['pdftotext', 'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe'] as $candidate) {
            if (@is_executable($candidate)) {
                return $candidate;
            }
            $which = shell_exec('where ' . escapeshellarg($candidate) . ' 2>nul');
            if ($which && trim($which) !== '') {
                return trim(strtok($which, "\n"));
            }
        }
        return null;
    }

    private function readDocx(UploadedFile $file): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) return '';
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml === false) return '';
            $xml = preg_replace('/<[^>]+>/', ' ', $xml) ?? '';
            return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } catch (\Throwable) {
            return '';
        }
    }

    private function readSpreadsheet(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'csv') {
            $rows = [];
            if (($fh = fopen($file->getRealPath(), 'r')) !== false) {
                while (($row = fgetcsv($fh)) !== false) {
                    $rows[] = implode(' ', $row);
                }
                fclose($fh);
            }
            return implode("\n", $rows);
        }

        try {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) return '';
            $xml = $zip->getFromName('xl/sharedStrings.xml');
            $zip->close();
            if ($xml === false) return '';
            preg_match_all('/<t[^>]*>([^<]*)<\/t>/', $xml, $m);
            return implode(' ', $m[1]);
        } catch (\Throwable) {
            return '';
        }
    }
}
