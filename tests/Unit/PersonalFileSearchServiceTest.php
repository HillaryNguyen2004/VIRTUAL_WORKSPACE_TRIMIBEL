<?php

namespace Tests\Unit;

use App\Models\PersonalFile;
use App\Services\PersonalFileSearchService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PersonalFileSearchServiceTest extends TestCase
{
    public function test_rank_files_prioritizes_matching_content(): void
    {
        $service = new PersonalFileSearchService();

        $target = new PersonalFile([
            'id' => 11,
            'original_name' => 'macroeconomics_lesson.pdf',
            'searchable_text' => 'Definition of macroeconomics and aggregate demand fundamentals.',
            'mime_type' => 'application/pdf',
        ]);

        $noise = new PersonalFile([
            'id' => 12,
            'original_name' => 'random-notes.pdf',
            'searchable_text' => 'General notes not related to economics.',
            'mime_type' => 'application/pdf',
        ]);

        $results = $service->rankFiles(new Collection([$noise, $target]), 'dinh nghia kinh te vi mo');

        $this->assertNotEmpty($results);
        $this->assertSame('macroeconomics_lesson.pdf', $results->first()->original_name);
    }

    public function test_with_updated_file_name_keeps_name_searchable(): void
    {
        $service = new PersonalFileSearchService();

        $updated = $service->withUpdatedFileName('old content here', 'new finance report.pdf');

        // Name is lowercased during normalization
        $this->assertStringContainsString('new finance report', $updated);
        $this->assertStringContainsString('old content here', $updated);
    }

    public function test_rank_files_prioritizes_pdf_when_query_mentions_pdf(): void
    {
        $service = new PersonalFileSearchService();

        $pdf = new PersonalFile([
            'id' => 21,
            'original_name' => 'lesson-notes.pdf',
            'searchable_text' => 'definition of macroeconomics fundamentals',
            'mime_type' => 'application/pdf',
        ]);

        $docx = new PersonalFile([
            'id' => 22,
            'original_name' => 'lesson-notes.docx',
            'searchable_text' => 'definition of macroeconomics fundamentals',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $results = $service->rankFiles(new Collection([$docx, $pdf]), 'dinh nghia kinh te vi mo pdf');

        $this->assertNotEmpty($results);
        $this->assertSame('lesson-notes.pdf', $results->first()->original_name);
    }

    public function test_rank_files_can_match_content_near_the_end(): void
    {
        $service = new PersonalFileSearchService();

        $tailTarget = new PersonalFile([
            'id' => 31,
            'original_name' => 'full-course-notes.docx',
            'searchable_text' => str_repeat('intro section ', 500) . ' unique-tail-topic-omega ',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $noise = new PersonalFile([
            'id' => 32,
            'original_name' => 'other-notes.docx',
            'searchable_text' => str_repeat('intro section ', 500),
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $results = $service->rankFiles(new Collection([$noise, $tailTarget]), 'unique-tail-topic-omega');

        $this->assertNotEmpty($results);
        $this->assertSame('full-course-notes.docx', $results->first()->original_name);
    }

    public function test_with_updated_file_name_does_not_truncate_tail_content(): void
    {
        $service = new PersonalFileSearchService();

        $existing = str_repeat('middle ', 4000) . ' very-end-keyword';
        $updated = $service->withUpdatedFileName($existing, 'archive.pdf');

        $this->assertStringContainsString('very-end-keyword', $updated);
    }
}
