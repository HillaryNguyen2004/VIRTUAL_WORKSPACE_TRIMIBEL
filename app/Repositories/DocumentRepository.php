<?php

namespace App\Repositories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentRepository
{
    private static ?bool $fullTextAvailable = null;
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
     * Get search candidates (owned + shared) for a user with SQL pre-filtering.
     */
    public function getSearchCandidatesForUser(User $user, string $query, int $limit = 500): Collection
    {
        $safeLimit = max(50, min(3000, $limit));
        $queryParts = $this->extractSearchTokens($query);
        $tokens = $queryParts['search_tokens'];
        $fullTextTokens = $queryParts['fulltext_tokens'];

        $baseQuery = Document::query()
            ->with('owner')
            ->where(function ($accessQuery) use ($user) {
                $accessQuery->where('owner_id', $user->id)
                    ->orWhereHas('sharedUsers', function ($shareQuery) use ($user) {
                        $shareQuery->where('users.id', $user->id);
                    });
            });

        $booleanQuery = $this->buildBooleanFullTextQuery($query, $fullTextTokens);
        if ($booleanQuery !== null && $this->supportsFullTextSearch()) {
            $ftsCandidates = (clone $baseQuery)
                ->select('documents.*')
                ->selectRaw('MATCH(title, searchable_text) AGAINST (? IN BOOLEAN MODE) as fts_score', [$booleanQuery])
                ->whereRaw('MATCH(title, searchable_text) AGAINST (? IN BOOLEAN MODE) > 0', [$booleanQuery])
                ->orderByDesc('fts_score')
                ->orderByDesc('updated_at')
                ->limit($safeLimit)
                ->get();

            if ($ftsCandidates->isNotEmpty()) {
                return $ftsCandidates;
            }
        }

        return $baseQuery
            ->where(function ($searchQuery) use ($tokens, $query) {
                if ($tokens === []) {
                    $like = '%' . trim($query) . '%';
                    $searchQuery->where('title', 'like', $like)
                        ->orWhere('searchable_text', 'like', $like)
                        ->orWhereHas('owner', function ($ownerQuery) use ($like) {
                            $ownerQuery->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                    return;
                }

                foreach ($tokens as $token) {
                    $like = '%' . $token . '%';
                    $searchQuery->orWhere(function ($tokenQuery) use ($like) {
                        $tokenQuery->where('title', 'like', $like)
                            ->orWhere('searchable_text', 'like', $like)
                            ->orWhereHas('owner', function ($ownerQuery) use ($like) {
                                $ownerQuery->where('name', 'like', $like)
                                    ->orWhere('email', 'like', $like);
                            });
                        });
                }
            })
            ->latest('updated_at')
            ->limit($safeLimit)
            ->get();
    }

    /**
     * Get exact phrase candidates for recall fallback.
     */
    public function getExactPhraseCandidatesForUser(User $user, string $query, int $limit = 200): Collection
    {
        $phrase = trim($query);
        if ($phrase === '') {
            return new Collection();
        }

        $safeLimit = max(30, min(1500, $limit));
        $like = '%' . $phrase . '%';

        return Document::query()
            ->with('owner')
            ->where(function ($accessQuery) use ($user) {
                $accessQuery->where('owner_id', $user->id)
                    ->orWhereHas('sharedUsers', function ($shareQuery) use ($user) {
                        $shareQuery->where('users.id', $user->id);
                    });
            })
            ->where(function ($searchQuery) use ($like) {
                $searchQuery->where('title', 'like', $like)
                    ->orWhere('searchable_text', 'like', $like)
                    ->orWhereHas('owner', function ($ownerQuery) use ($like) {
                        $ownerQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            })
            ->latest('updated_at')
            ->limit($safeLimit)
            ->get();
    }

    /**
     * @param array<int, string> $tokens
     */
    private function buildBooleanFullTextQuery(string $rawQuery, array $tokens): ?string
    {
        // Normalize rawQuery to ASCII for FTS consistency with stored searchable_text
        $normalizedRawQuery = Str::ascii(mb_strtolower($rawQuery));
        $normalizedRawQuery = preg_replace('/[^a-z0-9\s]+/i', ' ', $normalizedRawQuery) ?? '';
        $normalizedRawQuery = trim($normalizedRawQuery);

        if ($tokens === []) {
            $phrase = $normalizedRawQuery;
            return $phrase !== '' ? '"' . str_replace('"', '', $phrase) . '"' : null;
        }

        $requiredTerms = array_map(static fn ($token) => $token . '*', array_slice($tokens, 0, 10));
        $phrase = $normalizedRawQuery;
        if ($phrase !== '' && mb_strlen($phrase) >= 4) {
            $requiredTerms[] = '"' . str_replace('"', '', $phrase) . '"';
        }

        return implode(' ', $requiredTerms);
    }

    private function supportsFullTextSearch(): bool
    {
        if (self::$fullTextAvailable !== null) {
            return self::$fullTextAvailable;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            self::$fullTextAvailable = false;
            return false;
        }

        try {
            DB::select('SELECT MATCH(title, searchable_text) AGAINST (? IN BOOLEAN MODE) AS score FROM documents LIMIT 1', ['test']);
            self::$fullTextAvailable = true;
        } catch (\Throwable $error) {
            self::$fullTextAvailable = false;
        }

        return self::$fullTextAvailable;
    }

    /**
     * @return array{search_tokens: array<int, string>, fulltext_tokens: array<int, string>}
     */
    private function extractSearchTokens(string $query): array
    {
        $normalized = trim(mb_strtolower($query));
        $rawTokens = preg_split('/\s+/u', $normalized) ?: [];
        $rawTokens = array_values(array_filter($rawTokens, static fn ($token) => mb_strlen((string) $token) >= 2));

        $stopWords = array_fill_keys(self::QUERY_STOP_WORDS, true);
        $significantTokens = array_values(array_filter($rawTokens, static function ($token) use ($stopWords): bool {
            $normalizedToken = Str::ascii((string) $token);
            return !isset($stopWords[$normalizedToken]);
        }));

        if ($significantTokens === []) {
            $significantTokens = $rawTokens;
        }

        // Normalize all tokens to ASCII for consistency with stored searchable_text
        $significantTokens = array_values(array_filter(array_map(static function (string $token): string {
            $ascii = Str::ascii(mb_strtolower($token));
            $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $ascii) ?? '';
            return trim($normalized);
        }, $significantTokens), static fn (string $token): bool => $token !== ''));

        $expandedTokens = $this->expandTokensBySynonyms($significantTokens, $normalized);
        $searchTokens = array_values(array_unique(array_merge($significantTokens, $expandedTokens)));
        $fullTextTokens = array_slice($searchTokens, 0, 16);

        return [
            'search_tokens' => $searchTokens,
            'fulltext_tokens' => $fullTextTokens,
        ];
    }

    /**
     * @param array<int, string> $tokens
     * @return array<int, string>
     */
    private function expandTokensBySynonyms(array $tokens, string $normalizedQuery): array
    {
        $expanded = [];

        foreach ($tokens as $token) {
            $mappedToken = Str::ascii((string) $token);
            foreach (self::TOKEN_SYNONYMS[$mappedToken] ?? [] as $synonym) {
                if (mb_strlen($synonym) >= 2) {
                    $expanded[] = $synonym;
                }
            }
        }

        $asciiQuery = Str::ascii($normalizedQuery);
        foreach (self::PHRASE_SYNONYMS as $phrase => $synonyms) {
            if (!str_contains($asciiQuery, $phrase)) {
                continue;
            }

            foreach ($synonyms as $synonym) {
                if (mb_strlen($synonym) >= 2) {
                    $expanded[] = $synonym;
                }
            }
        }

        return array_slice(array_values(array_unique($expanded)), 0, 16);
    }

    /**
     * Get all owned documents for a user
     */
    public function getOwnedDocuments(User $user): Collection
    {
        return $user->ownedDocuments()
            ->with('owner')
            ->latest()
            ->get();
    }

    /**
     * Get owned documents for a user filtered by type
     */
    public function getOwnedDocumentsByType(User $user, string $type): Collection
    {
        return $user->ownedDocuments()
            ->where('type', $type)
            ->with('owner')
            ->latest()
            ->get();
    }

    /**
     * Get latest owned documents for a user
     */
    public function getOwnedDocumentsLatest(User $user, int $perPage = 5, string $pageName = 'owned_page'): LengthAwarePaginator
    {
        return $user->ownedDocuments()
            ->with('owner')
            ->latest('updated_at')
            ->paginate($perPage, ['*'], $pageName);
    }

    /**
     * Get owned documents in library style order (alphabetical)
     */
    public function getOwnedDocumentsLibrary(User $user, int $perPage = 5, string $pageName = 'owned_page'): LengthAwarePaginator
    {
        return $user->ownedDocuments()
            ->with('owner')
            ->orderByRaw('LOWER(title) asc')
            ->paginate($perPage, ['*'], $pageName);
    }

    /**
     * Get all shared documents for a user
     */
    public function getSharedDocuments(User $user): Collection
    {
        return $user->sharedDocuments()
            ->with('owner')
            ->latest()
            ->get();
    }

    /**
     * Get shared documents for a user filtered by type
     */
    public function getSharedDocumentsByType(User $user, string $type): Collection
    {
        return $user->sharedDocuments()
            ->where('type', $type)
            ->with('owner')
            ->latest()
            ->get();
    }

    /**
     * Get recent documents (owned + shared) for a user
     */
    public function getRecentDocumentsForUser(User $user, int $perPage = 5, string $pageName = 'recent_page'): LengthAwarePaginator
    {
        return Document::query()
            ->with('owner')
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('sharedUsers', function ($shareQuery) use ($user) {
                        $shareQuery->where('users.id', $user->id);
                    });
            })
            ->latest('updated_at')
            ->paginate($perPage, ['*'], $pageName);
    }

    /**
     * Get shared users for a document
     */
    public function getSharedUsers(Document $document): Collection
    {
        return $document->sharedUsers()
            ->orderBy('email')
            ->get();
    }

    /**
     * Get share candidates (users excluding owner)
     */
    public function getShareCandidates(Document $document): Collection
    {
        return User::query()
            ->whereKeyNot($document->owner_id)
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email']);
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
