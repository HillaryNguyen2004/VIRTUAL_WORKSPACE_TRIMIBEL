<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Models\User;
use App\Services\OnlineDocsSearchService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class OnlineDocsSearchServiceTest extends TestCase
{
    public function test_exact_sentence_is_prioritized_for_long_query(): void
    {
        $service = new OnlineDocsSearchService();

        $target = $this->makeDocument(
            1,
            'Giao trinh kinh te vi mo',
            'Kiem cho toi dinh nghia bai hoc kinh te vi mo trong giao trinh hoc ky nay.'
        );

        $noisy = $this->makeDocument(
            2,
            'Tong hop bai hoc kinh te',
            'Dinh nghia dinh nghia bai hoc bai hoc kinh te kinh te va cac ghi chu khac.'
        );

        $query = 'Kiem cho toi dinh nghia bai hoc kinh te vi mo trong giao trinh hoc ky nay';

        $results = $service->rankDocuments(new Collection([$noisy, $target]), $query, [
            'min_score' => 0.01,
            'min_token_coverage' => 0.1,
        ]);

        $this->assertNotEmpty($results);
        $this->assertSame(1, (int) $results->first()->id);
    }

    public function test_synonym_expansion_can_retrieve_rough_memory_query(): void
    {
        $service = new OnlineDocsSearchService();

        $englishDoc = $this->makeDocument(
            10,
            'Macroeconomics Lesson 1',
            'This lesson introduces the definition of macroeconomics and aggregate demand.'
        );

        $query = 'dinh nghia kinh te vi mo';

        $results = $service->rankDocuments(new Collection([$englishDoc]), $query, [
            'min_score' => 0.01,
            'min_token_coverage' => 0.05,
        ]);

        $this->assertCount(1, $results);
        $this->assertSame(10, (int) $results->first()->id);
    }

    private function makeDocument(int $id, string $title, string $searchableText): Document
    {
        $document = new Document();
        $document->id = $id;
        $document->setAttribute('title', $title);
        $document->setAttribute('searchable_text', $searchableText);
        $document->setRelation('owner', new User([
            'name' => 'Owner',
            'email' => 'owner@example.com',
        ]));

        return $document;
    }
}
