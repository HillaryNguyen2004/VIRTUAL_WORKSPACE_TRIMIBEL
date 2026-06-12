<?php

namespace App\Services;

use Carbon\CarbonInterface;
use App\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class OnlineDocsSearchService
{
    private const BM25_K1 = 1.2;
    private const BM25_B = 0.75;
    private const ESTIMATED_LINES_PER_PAGE = 35;
    private const ESTIMATED_PARAGRAPHS_PER_PAGE = 4.5;
    private const QUERY_STOP_WORDS = [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'how', 'in', 'is', 'it',
        'of', 'on', 'or', 'that', 'the', 'this', 'to', 'what', 'when', 'where', 'who', 'why', 'with',
        'ban', 'bao', 'cho', 'cua', 'duoc', 'gi', 'hay', 'la', 'minh', 'mot', 'nhung', 'nguoi', 'o',
        'phan', 'roi', 'se', 'tai', 'thi', 'tim', 'toi', 'tren', 'trong', 'tu', 'va', 've', 'voi',
    ];
    private const TOKEN_SYNONYMS = [
        'definition' => ['dinh', 'nghia', 'khai', 'niem'],
        'dinh' => ['definition', 'concept'],
        'nghia' => ['definition', 'concept'],
        'lesson' => ['bai', 'hoc', 'lecture'],
        'bai' => ['lesson', 'lecture'],
        'hoc' => ['lesson', 'study'],
        'macroeconomics' => ['macroeconomic', 'kinhtevimo', 'vimo'],
        'macroeconomic' => ['macroeconomics', 'kinhtevimo', 'vimo'],
        'vimo' => ['macroeconomics', 'macroeconomic', 'kinhtevimo'],
        'kinhte' => ['economics', 'economic'],
    ];
    private const PHRASE_SYNONYMS = [
        'kinh te vi mo' => ['macroeconomics', 'macroeconomic'],
        'kinhtevimo' => ['macroeconomics', 'macroeconomic'],
    ];

    /**
     * @param array<string, float> $options
     */
    public function rankDocuments(Collection $documents, string $query, array $options = []): Collection
    {
        $queryFeatures = $this->buildQueryFeatures($query);
        $queryTokens = $queryFeatures['query_tokens'];
        $expandedTokens = $queryFeatures['expanded_tokens'];
        $queryPhrase = $queryFeatures['normalized_query'];
        $queryShingles = $queryFeatures['query_shingles'];

        if ($queryTokens === []) {
            return $documents;
        }

        $titleBoost = max(0.1, (float) ($options['title_boost'] ?? 2.0));
        $ownerBoost = max(0.1, (float) ($options['owner_boost'] ?? 0.8));
        $contentBoost = max(0.1, (float) ($options['content_boost'] ?? 1.2));
        $phraseBoost = max(0.0, (float) ($options['phrase_boost'] ?? 1.5));
        $contentPhraseBoost = max(0.0, (float) ($options['content_phrase_boost'] ?? 2.0));
        $prefixBoost = max(0.0, (float) ($options['prefix_boost'] ?? 0.35));
        $coverageBoost = max(0.0, (float) ($options['coverage_boost'] ?? 1.6));
        $expandedCoverageBoost = max(0.0, (float) ($options['expanded_coverage_boost'] ?? 0.35));
        $minTokenCoverage = max(0.0, min(1.0, (float) ($options['min_token_coverage'] ?? 0.12)));
        $recencyWeight = max(0.0, (float) ($options['recency_weight'] ?? 0.15));
        $minScore = max(0.0, (float) ($options['min_score'] ?? 0.05));
        $expandedTokenWeight = max(0.0, (float) ($options['expanded_token_weight'] ?? 0.55));
        $exactSentenceBoost = max(0.0, (float) ($options['exact_sentence_boost'] ?? 5.5));
        $exactSentenceInContentBoost = max(0.0, (float) ($options['exact_sentence_content_boost'] ?? 2.0));
        $shingleBoost = max(0.0, (float) ($options['shingle_boost'] ?? 0.4));

        $totalDocs = max(1, $documents->count());
        $docVectors = [];
        $docFrequency = [];
        $totalLength = 0;

        foreach ($documents as $document) {
            $title = (string) ($document->title ?? '');
            $ownerName = (string) ($document->owner?->name ?? '');
            $ownerEmail = (string) ($document->owner?->email ?? '');
            $content = $this->normalizeText((string) ($document->searchable_text ?? ''));
            $titleText = $this->normalizeText($title);
            $ownerText = $this->normalizeText($ownerName . ' ' . $ownerEmail);
            $text = trim($titleText . ' ' . $ownerText . ' ' . $content);
            $tokens = $this->tokenize($text);
            $tokenLength = max(1, count($tokens));
            $termFrequency = array_count_values($tokens);

            $titleTerms = array_count_values($this->tokenize($titleText));
            $ownerTerms = array_count_values($this->tokenize($ownerText));
            $contentTerms = array_count_values($this->tokenize($content));

            $docVectors[$document->id] = [
                'text' => $text,
                'title' => $titleText,
                'content' => $content,
                'tf' => $termFrequency,
                'title_tf' => $titleTerms,
                'owner_tf' => $ownerTerms,
                'content_tf' => $contentTerms,
                'length' => $tokenLength,
                'document' => $document,
            ];
            $totalLength += $tokenLength;

            foreach (array_keys($termFrequency) as $token) {
                $docFrequency[$token] = ($docFrequency[$token] ?? 0) + 1;
            }
        }

        $avgDocLength = max(1.0, $totalLength / $totalDocs);

        $scoredDocuments = [];
        foreach ($docVectors as $vector) {
            $score = 0.0;
            $matchedTokens = [];
            $matchedExpandedTokens = [];

            foreach ($queryTokens as $token) {
                $tf = (int) ($vector['tf'][$token] ?? 0);
                if ($tf <= 0) {
                    continue;
                }

                $matchedTokens[$token] = true;

                $df = (int) ($docFrequency[$token] ?? 0);
                $idf = log(1 + (($totalDocs - $df + 0.5) / ($df + 0.5)));
                $lengthNorm = self::BM25_K1 * (1 - self::BM25_B + self::BM25_B * ($vector['length'] / $avgDocLength));
                $baseScore = $idf * (($tf * (self::BM25_K1 + 1)) / ($tf + $lengthNorm));
                $titleTf = (int) ($vector['title_tf'][$token] ?? 0);
                $ownerTf = (int) ($vector['owner_tf'][$token] ?? 0);
                $contentTf = (int) ($vector['content_tf'][$token] ?? 0);
                $zoneWeight = 1.0
                    + ($titleTf > 0 ? $titleBoost : 0.0)
                    + ($ownerTf > 0 ? $ownerBoost : 0.0)
                    + ($contentTf > 0 ? $contentBoost : 0.0);
                $score += $baseScore * $zoneWeight;
            }

            if ($queryPhrase !== '' && str_contains($vector['text'], $queryPhrase)) {
                $score += $phraseBoost;
            }

            if ($queryPhrase !== '' && str_contains($vector['content'], $queryPhrase)) {
                $score += $contentPhraseBoost;
            }

            foreach ($expandedTokens as $token) {
                $tf = (int) ($vector['tf'][$token] ?? 0);
                if ($tf <= 0) {
                    continue;
                }

                $matchedExpandedTokens[$token] = true;
                $df = (int) ($docFrequency[$token] ?? 0);
                $idf = log(1 + (($totalDocs - $df + 0.5) / ($df + 0.5)));
                $lengthNorm = self::BM25_K1 * (1 - self::BM25_B + self::BM25_B * ($vector['length'] / $avgDocLength));
                $baseScore = $idf * (($tf * (self::BM25_K1 + 1)) / ($tf + $lengthNorm));
                $titleTf = (int) ($vector['title_tf'][$token] ?? 0);
                $ownerTf = (int) ($vector['owner_tf'][$token] ?? 0);
                $contentTf = (int) ($vector['content_tf'][$token] ?? 0);
                $zoneWeight = 1.0
                    + ($titleTf > 0 ? $titleBoost : 0.0)
                    + ($ownerTf > 0 ? $ownerBoost : 0.0)
                    + ($contentTf > 0 ? $contentBoost : 0.0);
                $score += $baseScore * $zoneWeight * $expandedTokenWeight;
            }

            $hasExactSentence = false;
            if ($queryPhrase !== '' && str_contains($vector['text'], $queryPhrase)) {
                $score += $exactSentenceBoost;
                $hasExactSentence = true;
            }
            if ($queryPhrase !== '' && str_contains($vector['content'], $queryPhrase)) {
                $score += $exactSentenceInContentBoost;
                $hasExactSentence = true;
            }

            $matchedShingles = 0;
            foreach ($queryShingles as $shingle) {
                if (str_contains($vector['text'], $shingle)) {
                    $matchedShingles++;
                }
            }
            if ($matchedShingles > 0) {
                $score += min(2.4, $matchedShingles * $shingleBoost);
            }

            foreach ($queryTokens as $token) {
                if (str_starts_with($vector['title'], $token)) {
                    $score += $prefixBoost;
                }
            }

            $coverage = count($matchedTokens) / max(1, count($queryTokens));
            if ($hasExactSentence) {
                $coverage = max($coverage, 0.85);
            }

            $expandedCoverage = count($matchedExpandedTokens) / max(1, count($expandedTokens));
            if ($coverage <= 0.0 && $expandedCoverage > 0.0) {
                $coverage = min(0.4, $expandedCoverage * 0.4);
            }
            $score += $coverageBoost * $coverage;
            $score += $expandedCoverageBoost * $expandedCoverage;

            $updatedAt = $vector['document']->updated_at;
            if ($updatedAt instanceof CarbonInterface) {
                $daysAgo = max(0, now()->diffInDays($updatedAt));
                $score += $recencyWeight * exp(-$daysAgo / 30);
            }

            $vector['document']->setAttribute('search_score', $score);
            $vector['document']->setAttribute('token_coverage', $coverage);
            $scoredDocuments[] = $vector['document'];
        }

        return collect($scoredDocuments)
            ->filter(function ($document) use ($minScore, $minTokenCoverage): bool {
                if (!is_object($document)) {
                    return false;
                }

                return (float) ($document->search_score ?? 0) >= $minScore
                    && (float) ($document->token_coverage ?? 0) >= $minTokenCoverage;
            })
            ->sort(function ($a, $b): int {
                if (!is_object($a) || !is_object($b)) {
                    return 0;
                }

                $scoreCompare = ((float) $b->search_score) <=> ((float) $a->search_score);
                if ($scoreCompare !== 0) {
                    return $scoreCompare;
                }

                return $b->updated_at <=> $a->updated_at;
            })
            ->values();
    }

    private function normalizeText(string $value): string
    {
        $ascii = Str::ascii(mb_strtolower($value));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $ascii) ?? '';
        return trim($normalized);
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\s+/', $value) ?: [];

        return array_values(array_filter($parts, static fn ($token) => strlen($token) >= 2));
    }

    /**
     * @return array{normalized_query: string, query_tokens: array<int, string>, expanded_tokens: array<int, string>, query_shingles: array<int, string>}
     */
    private function buildQueryFeatures(string $query): array
    {
        $normalizedQuery = $this->normalizeText($query);
        $allTokens = $this->tokenize($normalizedQuery);
        $stopWordMap = array_fill_keys(self::QUERY_STOP_WORDS, true);

        $queryTokens = array_values(array_filter(
            $allTokens,
            static fn (string $token): bool => !isset($stopWordMap[$token])
        ));

        if ($queryTokens === []) {
            $queryTokens = $allTokens;
        }

        $expandedTokens = $this->expandQueryTokens($queryTokens, $normalizedQuery);
        $queryShingles = $this->buildShingles($queryTokens, 3, 5, 14);

        return [
            'normalized_query' => $normalizedQuery,
            'query_tokens' => $queryTokens,
            'expanded_tokens' => $expandedTokens,
            'query_shingles' => $queryShingles,
        ];
    }

    /**
     * @param array<int, string> $queryTokens
     * @return array<int, string>
     */
    private function expandQueryTokens(array $queryTokens, string $normalizedQuery): array
    {
        $expanded = [];

        foreach ($queryTokens as $token) {
            foreach (self::TOKEN_SYNONYMS[$token] ?? [] as $synonym) {
                if (strlen($synonym) >= 2) {
                    $expanded[] = $synonym;
                }
            }
        }

        foreach (self::PHRASE_SYNONYMS as $phrase => $synonyms) {
            if (!str_contains($normalizedQuery, $phrase)) {
                continue;
            }

            foreach ($synonyms as $synonym) {
                if (strlen($synonym) >= 2) {
                    $expanded[] = $synonym;
                }
            }
        }

        $queryTokenSet = array_fill_keys($queryTokens, true);
        $expanded = array_values(array_filter(array_unique($expanded), static function (string $token) use ($queryTokenSet): bool {
            return !isset($queryTokenSet[$token]);
        }));

        return array_slice($expanded, 0, 24);
    }

    /**
     * @param array<int, string> $tokens
     * @return array<int, string>
     */
    private function buildShingles(array $tokens, int $minSize, int $maxSize, int $maxShingles): array
    {
        if ($tokens === []) {
            return [];
        }

        $shingles = [];
        $tokenCount = count($tokens);
        for ($size = $maxSize; $size >= $minSize; $size--) {
            if ($tokenCount < $size) {
                continue;
            }

            for ($start = 0; $start <= $tokenCount - $size; $start++) {
                $shingles[] = implode(' ', array_slice($tokens, $start, $size));
                if (count($shingles) >= $maxShingles) {
                    return array_values(array_unique($shingles));
                }
            }
        }

        return array_values(array_unique($shingles));
    }

    /**
     * Attach best matching snippet and estimated page/line to ranked results.
     */
    public function enrichDocumentsWithMatchLocation(Collection $documents, string $query): Collection
    {
        if ($documents->isEmpty()) {
            return $documents;
        }

        $queryTokens = $this->buildQueryFeatures($query)['query_tokens'];
        if ($queryTokens === []) {
            return $documents;
        }

        return $documents->map(function ($document) use ($queryTokens) {
            if (!$document instanceof Document) {
                return $document;
            }

            $context = $this->extractBestMatchContext($document, $queryTokens);
            if ($context === null) {
                return $document;
            }

            $document->setAttribute('search_match_snippet', $context['snippet']);
            $document->setAttribute('search_match_line', $context['line']);
            $document->setAttribute('search_match_page', $context['page']);
            $document->setAttribute('search_match_is_indexed', $context['is_indexed'] ?? false);
            return $document;
        });
    }

    /**
     * @param array<int, string> $queryTokens
     * @return array{snippet: string, line: int, page: int, is_indexed?: bool}|null
     */
    private function extractBestMatchContext(Document $document, array $queryTokens): ?array
    {
        $segments = $this->extractSegmentsFromDocument($document);
        if ($segments === []) {
            return null;
        }

        $best = null;
        foreach ($segments as $segment) {
            $normalizedSegment = $this->normalizeText($segment['text']);
            if ($normalizedSegment === '') {
                continue;
            }

            $segmentTokens = $this->tokenize($normalizedSegment);
            if ($segmentTokens === []) {
                continue;
            }

            $segmentTokenSet = array_fill_keys($segmentTokens, true);
            $matchCount = 0;
            foreach ($queryTokens as $token) {
                if (isset($segmentTokenSet[$token])) {
                    $matchCount++;
                }
            }

            if ($matchCount <= 0) {
                continue;
            }

            $density = $matchCount / max(1, count($segmentTokens));
            $score = ($matchCount * 2.0) + $density;
            if ($best === null || $score > $best['score']) {
                $best = [
                    'score' => $score,
                    'text' => $segment['text'],
                    'line' => $segment['line'],
                    'para_index' => $segment['para_index'] ?? null,
                    'para_index_guess' => $segment['para_index_guess'] ?? null,
                ];
            }
        }

        if ($best === null) {
            return null;
        }

        $line = max(1, (int) $best['line']);
        $paraIndex = $best['para_index'] ?? null;
        $paraIndexGuess = $best['para_index_guess'] ?? null;

        if ($paraIndex !== null) {
            // Use stored paragraph index for more accurate page calculation (~4-5 paragraphs per page).
            $page = (int) floor($paraIndex / self::ESTIMATED_PARAGRAPHS_PER_PAGE) + 1;
            $isIndexed = true;
        } elseif ($paraIndexGuess !== null) {
            // Use document-order paragraph index as a fallback for HTML-only content.
            $page = (int) floor($paraIndexGuess / self::ESTIMATED_PARAGRAPHS_PER_PAGE) + 1;
            $isIndexed = false;
        } else {
            // Fallback to line-based estimation.
            $page = (int) floor(($line - 1) / self::ESTIMATED_LINES_PER_PAGE) + 1;
            $isIndexed = false;
        }

        return [
            'snippet' => Str::limit($best['text'], 220),
            'line' => $line,
            'page' => max(1, $page),
            'is_indexed' => $isIndexed,
        ];
    }

    /**
     * @return array<int, array{line: int, text: string, para_index?: int, para_index_guess?: int}>
     */
    private function extractSegmentsFromDocument(Document $document): array
    {
        $segments = [];
        $line = 1;

        $html = '';
        if ($document->html_path && Storage::disk()->exists($document->html_path)) {
            try {
                $html = (string) Storage::disk()->get($document->html_path);
            } catch (\Throwable $error) {
                $html = '';
            }
        }

        if (trim($html) !== '') {
            // Try to parse using DOM to preserve para-index structure.
            $segments = $this->extractSegmentsFromHtmlDom($html);
            if ($segments !== []) {
                return $segments;
            }

            // Fallback: parse without para-index for older documents or malformed HTML.
            $lineAwareHtml = preg_replace('/<(\/p|\/div|\/li|\/h[1-6]|br\s*\/?)>/i', "$0\n", $html) ?? $html;
            $plain = html_entity_decode(strip_tags($lineAwareHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $rows = preg_split('/\R+/', $plain) ?: [];

            foreach ($rows as $row) {
                $text = trim((string) preg_replace('/\s+/u', ' ', $row));
                if ($text !== '') {
                    $segments[] = [
                        'line' => $line,
                        'text' => $text,
                    ];

                    $line++;
                }
            }
        }

        if ($segments !== []) {
            return $segments;
        }

        $fallbackText = trim((string) ($document->searchable_text ?? ''));
        if ($fallbackText === '') {
            return [];
        }

        $parts = preg_split('/(?<=[\.!?])\s+/u', $fallbackText) ?: [];
        foreach ($parts as $part) {
            $text = trim((string) preg_replace('/\s+/u', ' ', $part));
            if ($text === '') {
                continue;
            }

            $segments[] = [
                'line' => $line,
                'text' => $text,
            ];
            $line++;
        }

        return $segments;
    }

    /**
     * Parse HTML via DOMDocument to extract segments with para indices.
     * @return array<int, array{line: int, text: string, para_index?: int, para_index_guess?: int}>
     */
    private function extractSegmentsFromHtmlDom(string $html): array
    {
        $segments = [];
        $line = 1;

        try {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            libxml_clear_errors();

            $blockTags = ['p', 'li', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'td', 'th'];
            $xpath = new \DOMXPath($dom);
            $conditions = array_map(static fn (string $tag): string => 'self::' . $tag, $blockTags);
            $query = '//*[' . implode(' or ', $conditions) . ']';
            $nodes = $xpath->query($query);

            if ($nodes === false) {
                return [];
            }

            foreach ($nodes as $element) {
                if (!$element instanceof \DOMElement) {
                    continue;
                }

                if ($this->elementHasBlockDescendant($element, $blockTags)) {
                    continue;
                }

                $text = trim((string) preg_replace('/\s+/u', ' ', $element->textContent));
                if ($text === '') {
                    continue;
                }

                $paraIndexAttr = trim((string) $element->getAttribute('data-para-index'));
                $paraIndex = $paraIndexAttr !== '' ? (int) $paraIndexAttr : null;

                $segments[] = [
                    'line' => $line,
                    'text' => $text,
                    'para_index' => $paraIndex,
                    'para_index_guess' => $paraIndex === null ? max(0, $line - 1) : null,
                ];
                $line++;
            }
        } catch (\Throwable $error) {
            return [];
        }

        return $segments;
    }

    /**
     * @param array<int, string> $blockTags
     */
    private function elementHasBlockDescendant(\DOMElement $element, array $blockTags): bool
    {
        $stack = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $stack[] = $child;
            }
        }

        while ($stack !== []) {
            $node = array_pop($stack);
            $tag = strtolower($node->tagName);
            if (in_array($tag, $blockTags, true)) {
                return true;
            }

            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $stack[] = $child;
                }
            }
        }

        return false;
    }
}
