<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDocxRequest;
use App\Http\Requests\RemoveShareRequest;
use App\Http\Requests\ShareDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Requests\UpdateShareRequest;
use App\Jobs\IndexPersonalFileJob;
use App\Models\Document;
use App\Models\PersonalDocumentLink;
use App\Models\PersonalFile;
use App\Models\PersonalFolder;
use App\Models\PersonalFolderShare;
use App\Repositories\DocumentRepository;
use App\Services\DocumentService;
use App\Services\OnlineDocsSearchService;
use App\Services\PersonalFileSearchService;
use App\Services\RagIndexService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OnlineDocumentController extends Controller
{
    private const PROCESSING_STALE_MINUTES = 30;

    public function __construct(
        private DocumentRepository $repository,
        private DocumentService $service,
        private OnlineDocsSearchService $searchService,
        private PersonalFileSearchService $personalFileSearchService,
        private RagIndexService $ragIndex
    ) {
    }

    public function landing(Request $request)
    {
        $user = auth()->user();
        $recentDocuments = $this->repository->getRecentDocumentsForUser($user, 5, 'recent_page');

        $currentFolder = null;
        $currentFolderId = $request->query('folder');
        if ($currentFolderId) {
            $currentFolder = PersonalFolder::query()
                ->where('user_id', $user->id)
                ->whereKey($currentFolderId)
                ->first();

            // Also check shared folders the user has access to
            if (!$currentFolder) {
                $sharedFolderIds = PersonalFolderShare::query()
                    ->where('user_id', $user->id)
                    ->pluck('folder_id');
                $currentFolder = PersonalFolder::query()
                    ->whereKey($currentFolderId)
                    ->whereIn('id', $sharedFolderIds)
                    ->first();
            }

            if (!$currentFolder) {
                abort(404);
            }
        }

        $storageCanEdit = !$currentFolder || $currentFolder->user_id === $user->id;

        $ownedFolders = PersonalFolder::query()
            ->where('user_id', $user->id)
            ->where('parent_id', $currentFolder?->id)
            ->orderBy('name')
            ->get();

        $sharedFolders = collect();
        if ($currentFolder === null) {
            $sharedFolderIds = PersonalFolderShare::query()
                ->where('user_id', $user->id)
                ->pluck('folder_id');
            $sharedFolders = PersonalFolder::query()
                ->whereIn('id', $sharedFolderIds)
                ->where('user_id', '!=', $user->id)
                ->orderBy('name')
                ->get();
        }
        $folders = $ownedFolders->merge($sharedFolders)->unique('id')->values();

        $files = PersonalFile::query()
            ->where('user_id', $user->id)
            ->where('folder_id', $currentFolder?->id)
            ->latest('created_at')
            ->get();

        $links = PersonalDocumentLink::query()
            ->with('document')
            ->where('user_id', $user->id)
            ->where('folder_id', $currentFolder?->id)
            ->latest('created_at')
            ->get();

        $allFolders = PersonalFolder::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        $folderBreadcrumbs = $this->buildFolderBreadcrumbs($currentFolder);

        // Global search
        $globalSearchQuery = trim((string) $request->query('doc_query', ''));
        $globalSearchResults = collect();
        $personalSearchResults = collect();

        if ($globalSearchQuery !== '') {
            $candidates = $this->repository->getSearchCandidatesForUser($user, $globalSearchQuery);
            $globalSearchResults = $this->searchService->rankDocuments($candidates, $globalSearchQuery);
            $globalSearchResults = $this->searchService->enrichDocumentsWithMatchLocation($globalSearchResults, $globalSearchQuery);

            $personalSearchResults = $this->personalFileSearchCandidates($user->id, $globalSearchQuery);
            $personalSearchResults = $this->personalFileSearchService->rankFiles($personalSearchResults, $globalSearchQuery);
        }

        $pendingIngestCount = PersonalFile::where('user_id', $user->id)
            ->whereIn('ingest_status', ['pending', 'failed'])
            ->count();
        $failedIngestCount = PersonalFile::where('user_id', $user->id)
            ->where('ingest_status', 'failed')
            ->count();

        return view('online-docs.docs', compact(
            'recentDocuments',
            'currentFolder',
            'folders',
            'files',
            'links',
            'allFolders',
            'folderBreadcrumbs',
            'storageCanEdit',
            'globalSearchQuery',
            'globalSearchResults',
            'personalSearchResults',
            'pendingIngestCount',
            'failedIngestCount'
        ));
    }

    private function personalFileSearchCandidates(int $userId, string $query): \Illuminate\Support\Collection
    {
        $rawQuery = mb_strtolower(trim($query));
        $normalizedQuery = trim(preg_replace('/[^a-z0-9\s]+/i', ' ', \Illuminate\Support\Str::ascii($rawQuery)) ?? '');
        $tokens = array_values(array_filter(
            preg_split('/\s+/', $normalizedQuery) ?: [],
            static fn (string $t): bool => strlen($t) >= 2
        ));

        return PersonalFile::query()
            ->where('user_id', $userId)
            ->where(function ($searchQuery) use ($tokens, $rawQuery, $normalizedQuery): void {
                $searchTerms = $tokens !== []
                    ? $tokens
                    : array_values(array_unique(array_filter([$rawQuery, $normalizedQuery], fn ($t) => $t !== '')));
                foreach ($searchTerms as $term) {
                    $like = '%' . $term . '%';
                    $searchQuery->orWhere('original_name', 'like', $like)
                        ->orWhere('searchable_text', 'like', $like)
                        ->orWhere('mime_type', 'like', $like);
                }
            })
            ->limit(200)
            ->get();
    }

    public function shareFolder(Request $request, PersonalFolder $folder)
    {
        $user = auth()->user();
        if ($folder->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'email' => ['required', 'email'],
            'permission' => ['sometimes', 'in:view,edit'],
        ]);

        $targetUser = \App\Models\User::where('email', $data['email'])->first();
        if (!$targetUser || $targetUser->id === $user->id) {
            return $this->redirectToStorageFolder($request, $folder->parent_id)
                ->with('storage_error', __('online_docs.folder_share_invalid_user'));
        }

        PersonalFolderShare::updateOrCreate(
            ['folder_id' => $folder->id, 'user_id' => $targetUser->id],
            ['shared_by' => $user->id, 'permission' => $data['permission'] ?? 'view']
        );

        return $this->redirectToStorageFolder($request, $folder->parent_id)
            ->with('storage_success', __('online_docs.folder_shared'));
    }

    public function removeFolderShare(Request $request, PersonalFolder $folder, PersonalFolderShare $share)
    {
        $user = auth()->user();
        if ($folder->user_id !== $user->id || $share->folder_id !== $folder->id) {
            abort(403);
        }

        $share->delete();

        return $this->redirectToStorageFolder($request, $folder->parent_id)
            ->with('storage_success', __('online_docs.folder_share_removed'));
    }

    public function generateFolderShareLink(Request $request, PersonalFolder $folder)
    {
        $user = auth()->user();
        if ($folder->user_id !== $user->id) {
            abort(403);
        }

        if (!$folder->share_token) {
            $folder->update([
                'share_token' => Str::random(32),
                'share_link_enabled' => true,
            ]);
        }

        $shareUrl = route('online-docs.folders.share.open', ['token' => $folder->share_token]);

        if ($request->expectsJson()) {
            return response()->json(['url' => $shareUrl]);
        }

        return $this->redirectToStorageFolder($request, $folder->parent_id);
    }

    public function openFolderShareLink(Request $request, string $token)
    {
        $folder = PersonalFolder::query()
            ->where('share_token', $token)
            ->where('share_link_enabled', true)
            ->firstOrFail();

        $user = auth()->user();

        if ($folder->user_id !== $user->id) {
            $hasAccess = PersonalFolderShare::where('folder_id', $folder->id)
                ->where('user_id', $user->id)
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('online-docs.home')
                    ->with('storage_error', __('online_docs.folder_share_link_opened'));
            }
        }

        return redirect()->route('online-docs.home', ['folder' => $folder->id])
            ->with('storage_success', __('online_docs.folder_share_link_opened'));
    }

    public function summarizeDocument(Document $document)
    {
        $this->authorize('view', $document);

        $plainText = $this->service->getDocumentPlainText($document);

        if ($plainText === '') {
            return response()->json(['summary' => __('online_docs.ai_summary_empty')]);
        }

        $chatbotUrl = rtrim((string) (config('services.chatbot.base_url') ?: config('services.chatbot.url', '')), '/');
        if ($chatbotUrl === '') {
            return response()->json(['error' => __('online_docs.ai_summary_error')], 503);
        }

        $locale = app()->getLocale();
        $lang = in_array($locale, ['vi', 'en']) ? $locale : 'auto';

        try {
            $response = Http::timeout(120)->post($chatbotUrl . '/summary/text', [
                'text'       => mb_substr($plainText, 0, 50000),
                'lang'       => $lang,
                'style'      => 'bullet',
                'n_clusters' => 8,
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => __('online_docs.ai_summary_error')], 500);
            }

            $data    = $response->json();
            $summary = (string) ($data['summary'] ?? '');

            if ($summary === '' && isset($data['error'])) {
                return response()->json(['error' => $data['error']], 500);
            }

            return response()->json(['summary' => $summary]);
        } catch (\Throwable $e) {
            return response()->json(['error' => __('online_docs.ai_summary_error')], 500);
        }
    }

    public function actionItems(Document $document)
    {
        $this->authorize('view', $document);

        $plainText = $this->service->getDocumentPlainText($document);

        if ($plainText === '') {
            return response()->json(['items' => []]);
        }

        $chatbotUrl = rtrim((string) (config('services.chatbot.base_url') ?: config('services.chatbot.url', '')), '/');
        if ($chatbotUrl === '') {
            return response()->json(['error' => __('online_docs.action_items_error')], 503);
        }

        try {
            $prompt = "Extract all action items, tasks, to-dos, deadlines, and assignments from the following document. "
                . "Return a JSON array of objects with fields: task (string), owner (string or null), due_date (string or null). "
                . "Only return the JSON array, no explanation.\n\nDocument:\n"
                . mb_substr($plainText, 0, 6000);

            $response = Http::timeout(30)->post($chatbotUrl . '/chat', [
                'message' => $prompt,
                'k' => 1,
                'lang' => app()->getLocale(),
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => __('online_docs.action_items_error')], 500);
            }

            $answer = (string) ($response->json('answer') ?? '');
            // Extract JSON array from the answer
            if (preg_match('/\[.*\]/s', $answer, $matches)) {
                $items = json_decode($matches[0], true);
                if (is_array($items)) {
                    return response()->json(['items' => $items]);
                }
            }

            return response()->json(['items' => [], 'raw' => $answer]);
        } catch (\Throwable $e) {
            return response()->json(['error' => __('online_docs.action_items_error')], 500);
        }
    }

    public function searchAgent(Request $request)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $user  = auth()->user();
        $query = trim($data['query']);

        $chatbotUrl = rtrim((string) (config('services.chatbot.base_url') ?: ''), '/');
        if ($chatbotUrl !== '') {
            try {
                $userRole = strtolower((string) ($user->role ?? $user->position ?? 'user'));
                $normalizedRole = match ($userRole) {
                    'admin', 'subadmin' => 'admin',
                    'staff', 'substaff' => 'staff',
                    default             => 'user',
                };

                // Build the list of workspace IDs that belong to this user.
                // Each online doc is indexed under its own workspace: "online_doc_{id}".
                // Each personal file is indexed under: "personal_file_{id}".
                $docWorkspaces = Document::query()
                    ->where('owner_id', $user->id)
                    ->pluck('id')
                    ->map(fn($id) => 'online_doc_' . $id)
                    ->values()
                    ->all();

                $fileWorkspaces = PersonalFile::query()
                    ->where('user_id', $user->id)
                    ->pluck('id')
                    ->map(fn($id) => 'personal_file_' . $id)
                    ->values()
                    ->all();

                $workspaceIds = array_merge($docWorkspaces, $fileWorkspaces);

                // Nothing indexed yet — go straight to BM25 fallback.
                if (empty($workspaceIds)) {
                    return $this->searchAgentBm25Fallback($user, $query);
                }

                $response = Http::timeout(45)->post($chatbotUrl . '/agent/answer/multi', [
                    'query'         => $query,
                    'workspace_ids' => $workspaceIds,
                    'user_role'     => $normalizedRole,
                    'user_id'       => (string) $user->id,
                    'k'             => 8,
                    'lang'          => app()->getLocale(),
                    'history'       => [],
                    'history_text'  => '',
                ]);

                if ($response->successful()) {
                    $answer   = (string) ($response->json('answer') ?? '');
                    $passages = $response->json('passages') ?? [];

                    $citations         = $this->passagesToCitations($passages);
                    $confidence        = $this->inferConfidence($passages);
                    $enrichedCitations = $this->enrichCitations($citations, $user);

                    return response()->json([
                        'answer'    => $answer,
                        'citations' => $enrichedCitations,
                        'confidence' => $confidence,
                        'source'    => 'rag',
                    ]);
                }
            } catch (\Throwable) {
                // Chatbot unavailable — fall through to BM25 fallback.
            }
        }

        return $this->searchAgentBm25Fallback($user, $query);
    }

    /**
     * Convert agent passage dicts to the citation shape enrichCitations() expects.
     */
    private function passagesToCitations(array $passages): array
    {
        $citations = [];
        foreach ($passages as $i => $p) {
            $meta        = $p['metadata'] ?? [];
            $source      = $meta['source'] ?? $meta['file_name'] ?? $meta['storage_file'] ?? '';
            $workspaceId = (string) ($meta['workspace_id'] ?? '');

            $numericId  = '';
            $sourceType = 'unknown';

            if (preg_match('/^online_doc_(\d+)$/', $workspaceId, $m)) {
                $numericId  = $m[1];
                $sourceType = 'doc';
            } elseif (preg_match('/^personal_file_(\d+)$/', $workspaceId, $m)) {
                $numericId  = $m[1];
                $sourceType = 'file';
            }

            $citations[] = [
                'rank'        => $i + 1,
                'id'          => $numericId ?: $source,
                'source'      => $source,
                'source_type' => $sourceType,
                'page'        => (int) ($meta['page'] ?? 0),
                'line'        => (int) ($meta['chunk_index'] ?? 0),
            ];
        }
        return $citations;
    }

    /**
     * Derive a confidence dict from the top passage scores.
     */
    private function inferConfidence(array $passages): array
    {
        if (empty($passages)) {
            return ['level' => 'low', 'score' => 0.0, 'reason' => 'No matching documents found.'];
        }

        $topScore = (float) ($passages[0]['final_score'] ?? $passages[0]['_final_score'] ?? $passages[0]['rrf_score'] ?? 0.0);

        if ($topScore >= 0.7) {
            $level  = 'high';
            $reason = 'Strong match found in indexed documents.';
        } elseif ($topScore >= 0.4) {
            $level  = 'medium';
            $reason = 'Partial match found in indexed documents.';
        } else {
            $level  = 'low';
            $reason = 'Weak match — answer may be approximate.';
        }

        return ['level' => $level, 'score' => $topScore, 'reason' => $reason];
    }

    private function enrichCitations(array $citations, $user): array
    {
        $enriched = [];
        foreach ($citations as $citation) {
            $sourceId   = (string) ($citation['id'] ?? '');
            $source     = (string) ($citation['source'] ?? '');
            $hintType   = (string) ($citation['source_type'] ?? 'unknown');
            $rank       = (int)    ($citation['rank'] ?? 0);
            $page       = (int)    ($citation['page'] ?? 0);
            $line       = (int)    ($citation['line'] ?? 0);

            $docLink     = null;
            $fileLink    = null;
            $displayName = $source;

            // Use source_type hint from passagesToCitations() to avoid unnecessary DB queries.
            if ($hintType === 'doc' && is_numeric($sourceId)) {
                $doc = Document::query()
                    ->where(function ($q) use ($user) {
                        $q->where('owner_id', $user->id)
                            ->orWhereHas('sharedUsers', fn ($q2) => $q2->where('user_id', $user->id));
                    })
                    ->find((int) $sourceId);

                if ($doc) {
                    $docLink     = route('online-docs.docs.show', $doc);
                    $displayName = $doc->title ?: $source;
                }
            } elseif ($hintType === 'file' && is_numeric($sourceId)) {
                $file = PersonalFile::query()
                    ->where('user_id', $user->id)
                    ->find((int) $sourceId);

                if ($file) {
                    $fileLink    = route('online-docs.files.preview', $file);
                    $displayName = $file->original_name ?: $source;
                }
            }

            // Fallback: if hint was wrong or lookup failed, try both
            if (!$docLink && !$fileLink && is_numeric($sourceId)) {
                $doc = Document::query()
                    ->where(function ($q) use ($user) {
                        $q->where('owner_id', $user->id)
                            ->orWhereHas('sharedUsers', fn ($q2) => $q2->where('user_id', $user->id));
                    })
                    ->find((int) $sourceId);

                if ($doc) {
                    $docLink     = route('online-docs.docs.show', $doc);
                    $displayName = $doc->title ?: $source;
                } else {
                    $file = PersonalFile::query()
                        ->where('user_id', $user->id)
                        ->find((int) $sourceId);

                    if ($file) {
                        $fileLink    = route('online-docs.files.preview', $file);
                        $displayName = $file->original_name ?: $source;
                    }
                }
            }

            $enriched[] = [
                'rank'         => $rank,
                'id'           => $sourceId,
                'source'       => $source,
                'display_name' => $displayName,
                'doc_link'     => $docLink,
                'file_link'    => $fileLink,
                'page'         => $page > 0 ? $page : null,
                'line'         => $line > 0 ? $line : null,
                'source_type'  => $docLink ? 'doc' : ($fileLink ? 'file' : 'unknown'),
            ];
        }
        return $enriched;
    }

    private function searchAgentBm25Fallback($user, string $query): \Illuminate\Http\JsonResponse
    {
        // Search documents
        $candidates = $this->repository->getSearchCandidatesForUser($user, $query);
        $rankedDocs = $this->searchService->rankDocuments($candidates, $query);
        $rankedDocs = $this->searchService->enrichDocumentsWithMatchLocation($rankedDocs, $query);

        // Search personal files
        $fileCandidates = $this->personalFileSearchCandidates($user->id, $query);
        $rankedFiles = $this->personalFileSearchService->rankFiles($fileCandidates, $query);

        if ($rankedDocs->isEmpty() && $rankedFiles->isEmpty()) {
            return response()->json([
                'answer'     => __('online_docs.search_agent_empty'),
                'citations'  => [],
                'confidence' => ['level' => 'low', 'score' => 0.0],
                'source'     => 'bm25',
            ]);
        }

        $citations = [];
        $rank = 1;
        $docLines = [];
        $fileLines = [];

        foreach ($rankedDocs->take(5) as $doc) {
            $rawSnippet = $doc->search_match_snippet ?? strip_tags((string) ($doc->searchable_text ?? ''));
            $snippet = Str::limit($this->stripChunkHeader($rawSnippet), 180);
            $page    = $doc->search_match_page ?? null;
            $locTag  = $page ? " *(trang {$page})*" : '';
            $docLines[] = "- **{$doc->title}**{$locTag}  \n  {$snippet}";

            $citations[] = [
                'rank'         => $rank++,
                'id'           => (string) $doc->id,
                'source'       => $doc->title,
                'display_name' => $doc->title,
                'doc_link'     => route('online-docs.docs.show', $doc),
                'file_link'    => null,
                'page'         => $page,
                'line'         => $doc->search_match_line ?? null,
                'source_type'  => 'doc',
            ];
        }

        foreach ($rankedFiles->take(3) as $file) {
            $snippet = Str::limit($this->stripChunkHeader(strip_tags((string) ($file->searchable_text ?? ''))), 180);
            $fileLines[] = "- **{$file->original_name}**  \n  {$snippet}";

            $ext = strtolower(pathinfo((string) $file->original_name, PATHINFO_EXTENSION));
            $isOffice = in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);

            $citations[] = [
                'rank'         => $rank++,
                'id'           => (string) $file->id,
                'source'       => $file->original_name,
                'display_name' => $file->original_name,
                'doc_link'     => $isOffice ? route('online-docs.files.open', $file) : null,
                'file_link'    => route('online-docs.files.preview', $file),
                'page'         => null,
                'line'         => null,
                'source_type'  => 'file',
            ];
        }

        $sections = [];
        if ($docLines) {
            $sections[] = "### Tài liệu\n" . implode("\n", $docLines);
        }
        if ($fileLines) {
            $sections[] = "### Tệp cá nhân\n" . implode("\n", $fileLines);
        }
        $answer = implode("\n\n", $sections);

        $topScore = (float) ($rankedDocs->first()?->search_score ?? $rankedFiles->first()?->search_score ?? 0.0);
        $level = $topScore >= 3.0 ? 'high' : ($topScore >= 1.0 ? 'medium' : 'low');

        return response()->json([
            'answer'     => $answer,
            'citations'  => $citations,
            'confidence' => [
                'level' => $level,
                'score' => round(min(1.0, $topScore / 5.0), 2),
            ],
            'source' => 'bm25',
        ]);
    }

    public function createFolder(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parentId = $data['parent_id'] ?? null;
        if ($parentId) {
            $parentFolder = PersonalFolder::query()
                ->where('user_id', $user->id)
                ->whereKey($parentId)
                ->firstOrFail();
            $parentId = $parentFolder->id;
        }

        PersonalFolder::create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'name' => $data['name'],
        ]);

        return redirect()->route('online-docs.home', $parentId ? ['folder' => $parentId] : []);
    }

    public function uploadPersonalFile(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'file' => ['required', 'file'],
            'folder_id' => ['nullable', 'integer'],
            'target_name' => ['nullable', 'string', 'max:255'],
            'conflict_strategy' => ['nullable', 'in:error,auto_rename,replace'],
        ]);

        $folderId = $data['folder_id'] ?? null;
        if ($folderId) {
            $folder = PersonalFolder::query()
                ->where('user_id', $user->id)
                ->whereKey($folderId)
                ->firstOrFail();
            $folderId = $folder->id;
        }

        $file = $request->file('file');
        $requestedName = isset($data['target_name']) ? (string) $data['target_name'] : (string) $file->getClientOriginalName();
        $originalName = $this->normalizeStorageName($requestedName, 255, 'untitled');
        $conflictStrategy = in_array($data['conflict_strategy'] ?? null, ['error', 'auto_rename', 'replace'], true)
            ? $data['conflict_strategy']
            : 'auto_rename';

        // Check for duplicate (case-insensitive)
        $existingFile = PersonalFile::query()
            ->where('user_id', $user->id)
            ->where('folder_id', $folderId)
            ->whereRaw('LOWER(original_name) = ?', [mb_strtolower($originalName)])
            ->first();

        if ($existingFile) {
            if ($conflictStrategy === 'error') {
                return response()->json(['message' => __('online_docs.file_name_conflict')], 409);
            }

            if ($conflictStrategy === 'replace') {
                Storage::disk()->delete($existingFile->stored_path);
                $existingFile->forceDelete();
            } else {
                // auto_rename - find unique name
                $pathInfo = pathinfo($originalName);
                $baseName = $pathInfo['filename'] ?? 'untitled';
                $extension = $pathInfo['extension'] ?? '';
                $counter = 1;
                while (true) {
                    $newName = $extension ? $baseName . '(' . $counter . ').' . $extension : $baseName . '(' . $counter . ')';
                    $exists = PersonalFile::query()
                        ->where('user_id', $user->id)
                        ->where('folder_id', $folderId)
                        ->whereRaw('LOWER(original_name) = ?', [mb_strtolower($newName)])
                        ->exists();
                    if (!$exists) {
                        $originalName = $newName;
                        break;
                    }
                    $counter++;
                }
            }
        }

        $extension = $file->getClientOriginalExtension();
        $filename = $extension ? Str::uuid() . '.' . $extension : (string) Str::uuid();
        $baseDir = 'personal-files/' . $user->id . '/' . ($folderId ? ('folder-' . $folderId) : 'root');

        // Extract searchable text BEFORE storeAs() moves the temp file
        $searchableText = $this->personalFileSearchService->buildSearchableTextForUpload($file);
        if ($originalName !== (string) $file->getClientOriginalName()) {
            $searchableText = $this->personalFileSearchService->withUpdatedFileName($searchableText, $originalName);
        }

        $storedPath = $file->storeAs($baseDir, $filename, config('filesystems.default'));

        $personalFile = PersonalFile::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'stored_path' => $storedPath,
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'searchable_text' => $searchableText,
            'ingest_status' => 'pending',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'created',
                'name' => $originalName,
                'message' => __('online_docs.upload_done'),
            ]);
        }

        return redirect()->route('online-docs.home', $folderId ? ['folder' => $folderId] : []);
    }

    public function downloadPersonalFile(PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            $hasAccess = $file->folder_id !== null && PersonalFolderShare::where('folder_id', $file->folder_id)
                ->where('user_id', $user->id)
                ->exists();
            if (!$hasAccess) {
                abort(403);
            }
        }

        if (!Storage::disk()->exists($file->stored_path)) {
            abort(404);
        }

        return Storage::disk()->download($file->stored_path, $file->original_name);
    }

    public function previewPersonalFile(PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            $hasAccess = $file->folder_id !== null && PersonalFolderShare::where('folder_id', $file->folder_id)
                ->where('user_id', $user->id)
                ->exists();
            if (!$hasAccess) {
                abort(403);
            }
        }

        if (!Storage::disk()->exists($file->stored_path)) {
            abort(404);
        }

        $stream = Storage::disk()->readStream($file->stored_path);
        if (!\is_resource($stream)) {
            abort(404);
        }

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (\is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($file->original_name) . '"',
        ]);
    }

    public function openPersonalFile(Request $request, PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            $hasAccess = $file->folder_id !== null && PersonalFolderShare::where('folder_id', $file->folder_id)
                ->where('user_id', $user->id)
                ->exists();
            if (!$hasAccess) {
                abort(403);
            }
        }

        if (!Storage::disk()->exists($file->stored_path)) {
            abort(404);
        }

        $mode = $request->query('mode') === 'view' ? 'view' : 'edit';
        $extension = strtolower(pathinfo((string) $file->original_name, PATHINFO_EXTENSION));
        $title = pathinfo((string) $file->original_name, PATHINFO_FILENAME) ?: $file->original_name;

        $type = match ($extension) {
            'doc', 'docx' => 'docs',
            'xls', 'xlsx' => 'excel',
            'ppt', 'pptx' => 'powerpoint',
            default => null,
        };

        if (!$type) {
            abort(422, 'Unsupported office file format');
        }

        $linkedDocument = $file->document;
        if (
            $linkedDocument
            && $linkedDocument->owner_id === $user->id
            && $linkedDocument->type === $type
        ) {
            return redirect()->route('online-docs.docs.show', [
                'document' => $linkedDocument,
                'mode' => $mode,
            ]);
        }

        $document = $this->service->createDocument($user, $title, $type);

        if ($type === 'docs') {
            $targetPath = "documents/{$document->id}/document.docx";
            Storage::disk()->put($targetPath, Storage::disk()->get($file->stored_path));
            $document->update([
                'docx_path' => $targetPath,
                'last_edited_by' => $user->id,
            ]);
        } elseif ($type === 'excel') {
            $targetPath = "documents/{$document->id}/sheet.xlsx";
            Storage::disk()->put($targetPath, Storage::disk()->get($file->stored_path));
            $document->update([
                'xlsx_path' => $targetPath,
                'last_edited_by' => $user->id,
            ]);
        } else {
            $targetPath = "documents/{$document->id}/presentation.pptx";
            Storage::disk()->put($targetPath, Storage::disk()->get($file->stored_path));
            $document->update([
                'pptx_path' => $targetPath,
                'last_edited_by' => $user->id,
            ]);
        }

        $file->update([
            'document_id' => $document->id,
        ]);

        return redirect()->route('online-docs.docs.show', [
            'document' => $document,
            'mode' => $mode,
        ]);
    }

    public function addDocumentLink(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        $user = auth()->user();
        $data = $request->validate([
            'folder_id' => ['nullable', 'integer'],
        ]);

        $folderId = $data['folder_id'] ?? null;
        if ($folderId) {
            $folder = PersonalFolder::query()
                ->where('user_id', $user->id)
                ->whereKey($folderId)
                ->firstOrFail();
            $folderId = $folder->id;
        }

        PersonalDocumentLink::create([
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'document_id' => $document->id,
            'name' => $document->title,
        ]);

        return $this->redirectToStorageFolder($request, $folderId)
            ->with('storage_success', __('online_docs.link_added'));
    }

    public function renameDocumentLink(Request $request, PersonalDocumentLink $link)
    {
        $user = auth()->user();
        if ($link->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $link->update(['name' => $data['name']]);

        return $this->redirectToStorageFolder($request, $link->folder_id)
            ->with('storage_success', __('online_docs.link_renamed'));
    }

    public function deleteDocumentLink(Request $request, PersonalDocumentLink $link)
    {
        $user = auth()->user();
        if ($link->user_id !== $user->id) {
            abort(403);
        }

        $folderId = $link->folder_id;
        $link->delete();

        return $this->redirectToStorageFolder($request, $folderId)
            ->with('storage_success', __('online_docs.link_deleted'));
    }

    public function renameFolder(Request $request, PersonalFolder $folder)
    {
        $user = auth()->user();
        if ($folder->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        try {
            $folder->update(['name' => $data['name']]);
        } catch (QueryException $error) {
            return $this->redirectToStorageFolder($request, $folder->parent_id)
                ->with('storage_error', __('online_docs.folder_rename_failed'));
        }

        return $this->redirectToStorageFolder($request, $folder->parent_id)
            ->with('storage_success', __('online_docs.folder_renamed'));
    }

    public function deleteFolder(Request $request, PersonalFolder $folder)
    {
        $user = auth()->user();
        if ($folder->user_id !== $user->id) {
            abort(403);
        }

        $this->deleteFolderContents($folder);
        $folder->delete();

        return $this->redirectToStorageFolder($request, $folder->parent_id)
            ->with('storage_success', __('online_docs.folder_deleted'));
    }

    public function renameFile(Request $request, PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $updates = ['original_name' => $data['name']];
        if ($file->searchable_text) {
            $updates['searchable_text'] = $this->personalFileSearchService
                ->withUpdatedFileName((string) $file->searchable_text, $data['name']);
        }
        $file->update($updates);

        return $this->redirectToStorageFolder($request, $file->folder_id)
            ->with('storage_success', __('online_docs.file_renamed'));
    }

    public function deleteFile(Request $request, PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            abort(403);
        }

        if (Storage::disk()->exists($file->stored_path)) {
            Storage::disk()->delete($file->stored_path);
        }

        $folderId = $file->folder_id;
        $this->ragIndex->removeDocument(
            storageFile: basename($file->stored_path),
            workspaceId: 'personal_file_' . $file->id,
            userId: (string) $user->id,
        );
        $file->delete();

        return $this->redirectToStorageFolder($request, $folderId)
            ->with('storage_success', __('online_docs.file_deleted'));
    }

    public function ingestPersonalFile(Request $request, PersonalFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) {
            abort(403);
        }

        $staleAt = now()->subMinutes(self::PROCESSING_STALE_MINUTES);
        if ($file->ingest_status === 'processing' && $file->updated_at && $file->updated_at->greaterThan($staleAt)) {
            return back()->with('storage_warning', __('online_docs.ingest_processing_active'));
        }

        if (!in_array($file->ingest_status, ['pending', 'failed', 'processing'], true)) {
            return back()->with('storage_warning', __('online_docs.file_not_eligible_for_ingest'));
        }

        $file->update(['ingest_status' => 'processing', 'ingest_error' => null]);

        IndexPersonalFileJob::dispatch($file->id, (string) $user->id);

        return back()->with('storage_success', __('online_docs.ingest_queued_single', ['name' => $file->original_name]));
    }

    public function ingestAllPersonalFiles(Request $request)
    {
        $user = auth()->user();

        $staleAt = now()->subMinutes(self::PROCESSING_STALE_MINUTES);
        $pending = PersonalFile::where('user_id', $user->id)
            ->where(function ($query) use ($staleAt) {
                $query->whereIn('ingest_status', ['pending', 'failed'])
                    ->orWhere(function ($query) use ($staleAt) {
                        $query->where('ingest_status', 'processing')
                            ->where('updated_at', '<=', $staleAt);
                    });
            })
            ->get();

        if ($pending->isEmpty()) {
            return back()->with('storage_warning', __('online_docs.no_files_to_ingest'));
        }

        $queued = 0;

        foreach ($pending as $file) {
            $file->update(['ingest_status' => 'processing', 'ingest_error' => null]);
            IndexPersonalFileJob::dispatch($file->id, (string) $user->id);
            $queued++;
        }

        return back()->with('storage_success', __('online_docs.ingest_queued', ['count' => $queued]));
    }

    public function moveStorageItem(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'item_type' => ['required', 'string'],
            'item_id' => ['required', 'integer'],
            'target_folder_id' => ['nullable', 'integer'],
        ]);

        $targetFolderId = $this->resolveTargetFolderId($user->id, $data['target_folder_id'] ?? null);
        $this->moveItem($user->id, $data['item_type'], $data['item_id'], $targetFolderId);

        return response()->json(['status' => 'ok']);
    }

    public function bulkMoveStorageItems(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.type' => ['required', 'string'],
            'items.*.id' => ['required', 'integer'],
            'target_folder_id' => ['nullable', 'integer'],
        ]);

        $targetFolderId = $this->resolveTargetFolderId($user->id, $data['target_folder_id'] ?? null);
        foreach ($data['items'] as $item) {
            $this->moveItem($user->id, $item['type'], $item['id'], $targetFolderId);
        }

        return response()->json(['status' => 'ok']);
    }

    public function bulkDeleteStorageItems(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.type' => ['required', 'string'],
            'items.*.id' => ['required', 'integer'],
        ]);

        foreach ($data['items'] as $item) {
            $this->deleteStorageItem($user->id, $item['type'], $item['id']);
        }

        return response()->json(['status' => 'ok']);
    }

    public function docsIndex()
    {
        return $this->renderTypeIndex(
            'docs',
            __('online_docs.docs_page_title'),
            __('online_docs.docs_page_subtitle'),
            'online-docs.docs.store'
        );
    }

    public function excelIndex()
    {
        return $this->renderTypeIndex(
            'excel',
            __('online_docs.excel_page_title'),
            __('online_docs.excel_page_subtitle'),
            'online-docs.excel.create'
        );
    }

    public function powerpointIndex()
    {
        return $this->renderTypeIndex(
            'powerpoint',
            __('online_docs.powerpoint_page_title'),
            __('online_docs.powerpoint_page_subtitle'),
            'online-docs.powerpoint.create'
        );
    }

    public function store(StoreDocumentRequest $request)
    {
        $document = $this->service->createDocument(
            auth()->user(),
            $request->validated()['title'],
            'docs'
        );

        $this->ragIndex->indexDocument($document, userId: (string) auth()->id());

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function createExcel(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $title = trim($validated['title']);
        $document = $this->service->createDocument(auth()->user(), $title, 'excel');

        $this->service->updateDocument($document, auth()->user(), [
            'title' => $title,
            'content' => '',
        ]);

        $this->createEmptySpreadsheet($document);

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function createPowerpoint(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $title = trim($validated['title']);
        $document = $this->service->createDocument(auth()->user(), $title, 'powerpoint');

        $this->service->updateDocument($document, auth()->user(), [
            'title' => $title,
            'content' => '',
        ]);

        $this->service->ensurePptxPath($document);

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function show(Request $request, Document $document)
    {
        $this->authorize('view', $document);
        $canEdit = auth()->user()->can('update', $document);
        $forcedView = $request->query('mode') === 'view';
        $editorCanEdit = $canEdit && !$forcedView;

        $html = $this->service->getDocumentContent($document);
        $sharedUsers = $this->repository->getSharedUsers($document);
        $shareCandidates = $this->repository->getShareCandidates($document);

        $onlyofficeConfig = null;
        if (in_array($document->type, ['docs', 'powerpoint'], true)) {
            $fileType = $document->type === 'powerpoint' ? 'pptx' : 'docx';
            $documentType = $document->type === 'powerpoint' ? 'slide' : 'word';
            if ($document->type === 'powerpoint') {
                $this->service->ensurePptxPath($document);
            } else {
                $this->service->ensureDocxPath($document);
            }

            $fileUrl = $this->onlyofficeSignedRoute('onlyoffice.files', ['document' => $document]);
            $callbackUrl = $this->onlyofficeSignedRoute('onlyoffice.callback', ['document' => $document]);
            $key = $this->onlyofficeDocumentKey($document);

            $onlyofficeConfig = [
                'documentType' => $documentType,
                'type' => 'desktop',
                'height' => '720px',
                'width' => '100%',
                'document' => [
                    'fileType' => $fileType,
                    'key' => $key,
                    'title' => $document->title ?: 'Document',
                    'url' => $fileUrl,
                    'permissions' => [
                        'edit' => $editorCanEdit,
                    ],
                ],
                'editorConfig' => [
                    'callbackUrl' => $callbackUrl,
                    'mode' => $editorCanEdit ? 'edit' : 'view',
                    'lang' => app()->getLocale() === 'vi' ? 'vi' : 'en',
                    'region' => app()->getLocale() === 'vi' ? 'vi-VN' : 'en-US',
                    'customization' => [
                        'uiTheme' => 'theme-light',
                        'compactHeader' => false,
                        'compactToolbar' => false,
                        'toolbarNoTabs' => false,
                        'hideRightMenu' => false,
                        'hideRulers' => false,
                        'autosave' => true,
                        'forcesave' => true,
                        'help' => false,
                        'feedback' => false,
                    ],
                    'user' => [
                        'id' => (string) auth()->id(),
                        'name' => auth()->user()->name ?? auth()->user()->email,
                    ],
                ],
            ];

            $onlyofficeConfig['token'] = $this->onlyofficeJwt($onlyofficeConfig);
        }

        return view('online-docs.show', compact(
            'document',
            'html',
            'editorCanEdit',
            'forcedView',
            'sharedUsers',
            'shareCandidates',
            'onlyofficeConfig'
        ));
    }

    public function update(UpdateDocumentRequest $request, Document $document)
    {
        $this->authorize('update', $document);

        $this->service->updateDocument($document, auth()->user(), $request->validated());

        // Re-index after content update so search agent stays up to date
        $this->ragIndex->indexDocument($document, userId: (string) auth()->id());

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->back();
    }

    public function rename(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $this->service->updateDocument($document, auth()->user(), [
            'title' => $data['title'],
        ]);

        return redirect()->back();
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        Storage::disk()->deleteDirectory('documents/' . $document->id);

        if ($document->docx_path && Storage::disk()->exists($document->docx_path)) {
            Storage::disk()->delete($document->docx_path);
        }
        if ($document->pptx_path && Storage::disk()->exists($document->pptx_path)) {
            Storage::disk()->delete($document->pptx_path);
        }

        $this->ragIndex->deleteDocument($document, userId: (string) auth()->id());
        $document->delete();

        return redirect()->back();
    }

    public function importDocx(ImportDocxRequest $request, Document $document)
    {
        $this->authorize('update', $document);

        @set_time_limit(600);

        try {
            $this->service->importDocx($document, auth()->user(), $request->file('docx'));
        } catch (\Throwable $error) {
            $message = $error->getMessage() ?: __('online_docs.import_failed');
            return redirect()->back()->with('docx_error', $message);
        }

        $this->ragIndex->indexDocument($document, userId: (string) auth()->id());

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function importXlsx(Request $request, Document $document)
    {
        $this->authorize('update', $document);
        abort_unless($document->type === 'excel', 404);

        $data = $request->validate([
            'xlsx' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        try {
            $path = $this->ensureSpreadsheetPath($document);
            $this->service->putToDefaultDisk($path, file_get_contents($data['xlsx']->getRealPath()));

            $document->update([
                'xlsx_path' => $path,
                'last_edited_by' => auth()->id(),
            ]);
        } catch (\Throwable $error) {
            return redirect()->back()->with('xlsx_error', __('online_docs.import_failed'));
        }

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function importPptx(Request $request, Document $document)
    {
        $this->authorize('update', $document);
        abort_unless($document->type === 'powerpoint', 404);

        $data = $request->validate([
            'pptx' => ['required', 'file', 'mimes:pptx,ppt'],
        ]);

        try {
            $path = $this->service->ensurePptxPath($document);
            $this->service->putToDefaultDisk($path, file_get_contents($data['pptx']->getRealPath()));

            $document->update([
                'pptx_path' => $path,
                'last_edited_by' => auth()->id(),
            ]);
        } catch (\Throwable $error) {
            return redirect()->back()->with('pptx_error', __('online_docs.import_failed'));
        }

        return redirect()->route('online-docs.docs.show', $document);
    }

    public function exportDocx(Document $document)
    {
        $this->authorize('view', $document);

        if (in_array($document->type, ['docs', 'powerpoint'], true)) {
            $this->forceSaveOnlyofficeDocument($document);
        }

        if ($document->type === 'powerpoint') {
            $path = $this->service->ensurePptxPath($document);
            $filename = Str::slug($document->title ?: 'presentation') . '.pptx';
            return Storage::disk()->download($path, $filename);
        }

        $docxPath = $this->service->exportDocx($document);
        $filename = Str::slug($document->title ?: 'document') . '.docx';

        return Storage::disk()->download($docxPath, $filename);
    }

    public function downloadXlsx(Document $document)
    {
        $this->authorize('view', $document);

        $path = $this->ensureSpreadsheetPath($document);
        if (!Storage::disk()->exists($path)) {
            $this->createEmptySpreadsheet($document);
        }
        $filename = Str::slug($document->title ?: 'spreadsheet') . '.xlsx';

        return Storage::disk()->download($path, $filename);
    }

    public function onlyofficeFile(Document $document)
    {
        if ($document->type === 'powerpoint') {
            $path = $this->service->ensurePptxPath($document);
        } else {
            $path = $this->service->ensureDocxPath($document);
        }

        if (!Storage::disk()->exists($path)) {
            abort(404);
        }

        return Storage::disk()->response($path, basename($path));
    }

    public function onlyofficeCallback(Request $request, Document $document)
    {
        if (!$this->validateOnlyofficeJwt($request)) {
            return response()->json(['error' => 1, 'message' => 'Invalid token']);
        }

        $status = (int) $request->input('status', 0);
        // 2: document is ready for saving, 6: forcesave was requested
        if (!in_array($status, [2, 6], true)) {
            return response()->json(['error' => 0]);
        }

        $url = (string) $request->input('url');
        if ($url === '') {
            return response()->json(['error' => 1, 'message' => 'Missing file URL']);
        }

        $response = Http::get($url);
        if (!$response->successful()) {
            return response()->json(['error' => 1, 'message' => 'Download failed']);
        }

        if ($document->type === 'powerpoint') {
            $path = $this->service->ensurePptxPath($document);
        } else {
            $path = $this->service->ensureDocxPath($document);
        }
        Storage::disk()->put($path, $response->body());

        $document->update([
            'last_edited_by' => auth()->id(),
        ]);

        $this->syncLinkedPersonalFilesFromContent($document, $response->body());

        return response()->json(['error' => 0]);
    }

    public function saveXlsx(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'sheets' => ['required', 'array'],
        ]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($data['sheets'] as $index => $sheet) {
            $name = is_array($sheet) && isset($sheet['name'])
                ? (string) $sheet['name']
                : 'Sheet ' . ($index + 1);
            $worksheet = new Worksheet($spreadsheet, mb_substr($name, 0, 31));
            $spreadsheet->addSheet($worksheet, $index);

            if (!is_array($sheet) || !isset($sheet['data']) || !is_array($sheet['data'])) {
                continue;
            }

            foreach ($sheet['data'] as $rowIndex => $row) {
                if (!is_array($row)) {
                    continue;
                }
                foreach ($row as $colIndex => $cell) {
                    if (!is_array($cell)) {
                        continue;
                    }
                    $value = $cell['v'] ?? null;
                    $formula = $cell['f'] ?? null;
                    if ($value === null && $formula === null) {
                        continue;
                    }

                    $cellRef = Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 1);
                    if ($formula) {
                        $worksheet->setCellValue($cellRef, str_starts_with($formula, '=') ? $formula : '=' . $formula);
                    } else {
                        $worksheet->setCellValue($cellRef, $value);
                    }
                }
            }
        }

        $path = $this->ensureSpreadsheetPath($document);
        [$tempRelative, $tempPath] = $this->createLocalTempPath('xlsx');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        $this->service->putToDefaultDisk($path, fopen($tempPath, 'rb'));
        Storage::disk('local')->delete($tempRelative);

        $document->update([
            'last_edited_by' => auth()->id(),
        ]);

        $this->syncLinkedPersonalFilesFromPath($document, $path);

        return response()->json(['status' => 'ok']);
    }

    public function share(ShareDocumentRequest $request, Document $document)
    {
        $this->authorize('share', $document);

        $data = $request->validated();
        $this->service->shareDocument($document, $data['email'], $data['permission']);

        return redirect()->back();
    }

    public function updateShare(UpdateShareRequest $request, Document $document)
    {
        $this->authorize('share', $document);

        $data = $request->validated();
        $this->service->updateSharePermission($document, $data['user_id'], $data['permission']);

        return redirect()->back();
    }

    public function removeShare(RemoveShareRequest $request, Document $document)
    {
        $this->authorize('share', $document);

        $this->service->removeShare($document, $request->validated()['user_id']);

        return redirect()->back();
    }

    public function presence(Document $document)
    {
        $this->authorize('view', $document);

        return response()->json([
            'editors' => $this->activeEditors($document),
        ]);
    }

    public function touchPresence(Document $document)
    {
        $this->authorize('view', $document);

        $user = auth()->user();
        if ($user && $user->can('update', $document)) {
            $key = $this->presenceKey($document);
            $presence = Cache::get($key, []);
            if (!is_array($presence)) {
                $presence = [];
            }

            $presence[(string) $user->id] = [
                'id' => $user->id,
                'name' => $user->name ?: $user->email,
                'at' => now()->getTimestamp(),
            ];

            Cache::put($key, $presence, now()->addMinutes(2));
        }

        return response()->json([
            'editors' => $this->activeEditors($document),
        ]);
    }

    private function buildSpreadsheetHtml(int $rows = 12, int $cols = 8): string
    {
        $safeRows = max(1, min($rows, 40));
        $safeCols = max(1, min($cols, 20));
        $colLabels = [];

        for ($i = 0; $i < $safeCols; $i += 1) {
            $colLabels[] = chr(65 + $i);
        }

        $thead = '<thead><tr><th></th>';
        foreach ($colLabels as $label) {
            $thead .= '<th>' . $label . '</th>';
        }
        $thead .= '</tr></thead>';

        $tbody = '<tbody>';
        for ($row = 1; $row <= $safeRows; $row += 1) {
            $tbody .= '<tr><th>' . $row . '</th>';
            for ($col = 0; $col < $safeCols; $col += 1) {
                $tbody .= '<td></td>';
            }
            $tbody .= '</tr>';
        }
        $tbody .= '</tbody>';

        return '<table data-table-style="sheet" class="table-sheet">'
            . $thead
            . $tbody
            . '</table><p></p>';
    }

    private function presenceKey(Document $document): string
    {
        return 'online_docs_presence_' . $document->id;
    }

    private function activeEditors(Document $document): array
    {
        $presence = Cache::get($this->presenceKey($document), []);
        if (!is_array($presence)) {
            return [];
        }

        $nowTs = now()->getTimestamp();
        $ttlSeconds = 20;
        $nextPresence = [];
        $editors = [];

        foreach ($presence as $userId => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $lastSeen = (int) ($entry['at'] ?? 0);
            if ($nowTs - $lastSeen > $ttlSeconds) {
                continue;
            }

            $name = (string) ($entry['name'] ?? '');
            $initials = collect(preg_split('/\s+/', trim($name)) ?: [])
                ->filter()
                ->take(2)
                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('');
            if ($initials === '') {
                $initials = 'U';
            }

            $normalized = [
                'id' => (int) ($entry['id'] ?? $userId),
                'name' => $name,
                'initials' => $initials,
            ];

            $nextPresence[(string) $normalized['id']] = [
                ...$entry,
                'id' => $normalized['id'],
                'name' => $name,
                'at' => $lastSeen,
            ];
            $editors[] = $normalized;
        }

        Cache::put($this->presenceKey($document), $nextPresence, now()->addMinutes(2));

        usort($editors, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return $editors;
    }

    private function ensureSpreadsheetPath(Document $document): string
    {
        if ($document->xlsx_path) {
            return $document->xlsx_path;
        }

        $path = "documents/{$document->id}/sheet.xlsx";
        // removed makeDirectory as S3 does not need it and it fails with ACL error
        $document->update(['xlsx_path' => $path]);

        return $path;
    }

    private function createLocalTempPath(string $extension): array
    {
        $normalized = ltrim($extension, '.');
        $relative = 'tmp/online-docs/' . Str::uuid() . ($normalized !== '' ? '.' . $normalized : '');
        Storage::disk('local')->makeDirectory(dirname($relative));

        return [$relative, Storage::disk('local')->path($relative)];
    }

    private function createEmptySpreadsheet(Document $document): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Sheet 1');

        $path = $this->ensureSpreadsheetPath($document);
        [$tempRelative, $tempPath] = $this->createLocalTempPath('xlsx');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        Storage::disk()->put($path, fopen($tempPath, 'rb'));
        Storage::disk('local')->delete($tempRelative);
    }

    private function onlyofficeJwt(array $payload): string
    {
        $secret = (string) config('onlyoffice.jwt_secret');
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $encode = fn ($value) => rtrim(strtr(base64_encode(json_encode($value)), '+/', '-_'), '=');

        $segments = [
            $encode($header),
            $encode($payload),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return implode('.', $segments);
    }

    private function onlyofficeDocumentKey(Document $document): string
    {
        $filePath = $document->type === 'powerpoint'
            ? ($document->pptx_path ?: 'none')
            : ($document->docx_path ?: 'none');

        $version = $document->updated_at?->getTimestamp() ?: 0;

        return 'doc_' . $document->id . '_' . substr(sha1($document->type . '|' . $filePath . '|' . $version), 0, 20);
    }

    private function forceSaveOnlyofficeDocument(Document $document): void
    {
        $server = rtrim((string) config('onlyoffice.document_server_url'), '/');
        if ($server === '') {
            return;
        }

        $storagePath = $document->type === 'powerpoint'
            ? $this->service->ensurePptxPath($document)
            : $this->service->ensureDocxPath($document);
        $beforeModified = Storage::disk()->exists($storagePath)
            ? Storage::disk()->lastModified($storagePath)
            : 0;

        $payload = [
            'c' => 'forcesave',
            'key' => $this->onlyofficeDocumentKey($document),
            'userdata' => 'export',
        ];

        try {
            $token = $this->onlyofficeJwt($payload);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(8)->post($server . '/coauthoring/CommandService.ashx', [
                ...$payload,
                'token' => $token,
            ]);

            $result = $response->json();
            $commandOk = $response->successful() && isset($result['error']) && (int) $result['error'] === 0;
            if (!$commandOk) {
                return;
            }

            // Wait for callback to persist updated DOCX before exporting.
            for ($i = 0; $i < 16; $i += 1) {
                usleep(250000);
                if (!Storage::disk()->exists($storagePath)) {
                    continue;
                }

                $currentModified = Storage::disk()->lastModified($storagePath);
                if ($currentModified > $beforeModified) {
                    break;
                }
            }
        } catch (\Throwable $error) {
            // Ignore command failures and fall back to the latest persisted file.
        }
    }

    private function validateOnlyofficeJwt(Request $request): bool
    {
        $secret = (string) config('onlyoffice.jwt_secret');
        $headerToken = (string) $request->header('Authorization', '');
        if ($headerToken !== '' && str_starts_with(strtolower($headerToken), 'bearer ')) {
            $headerToken = trim(substr($headerToken, 7));
        }

        $token = $request->bearerToken()
            ?: ($headerToken !== '' ? $headerToken : null)
            ?: (string) $request->input('token', '');
        if ($token === '' || $secret === '') {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;
        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $header . '.' . $payload, $secret, true)), '+/', '-_'), '=');

        return hash_equals($expected, $signature);
    }

    private function onlyofficeSignedRoute(string $name, array $parameters): string
    {
        $publicBase = rtrim((string) config('onlyoffice.public_url'), '/');
        if ($publicBase === '') {
            return URL::signedRoute($name, $parameters);
        }

        $originalRoot = rtrim(URL::to('/'), '/');
        $parsed = parse_url($publicBase);
        $scheme = $parsed['scheme'] ?? null;

        URL::forceRootUrl($publicBase);
        if ($scheme) {
            URL::forceScheme($scheme);
        }

        $signed = URL::signedRoute($name, $parameters);

        URL::forceRootUrl($originalRoot);
        if ($scheme) {
            URL::forceScheme(parse_url($originalRoot, PHP_URL_SCHEME) ?: null);
        }

        return $signed;
    }

    private function syncLinkedPersonalFilesFromPath(Document $document, string $sourcePath): void
    {
        if (!Storage::disk()->exists($sourcePath)) {
            return;
        }

        try {
            $content = Storage::disk()->get($sourcePath);
        } catch (\Throwable $error) {
            return;
        }

        $this->syncLinkedPersonalFilesFromContent($document, $content);
    }

    private function syncLinkedPersonalFilesFromContent(Document $document, string $content): void
    {
        $mimeType = $this->officeMimeTypeForDocument($document);
        $size = strlen($content);

        PersonalFile::query()
            ->where('document_id', $document->id)
            ->chunkById(100, function ($files) use ($content, $mimeType, $size): void {
                foreach ($files as $file) {
                    $storedPath = (string) $file->stored_path;
                    if ($storedPath === '') {
                        continue;
                    }

                    try {
                        $this->service->putToDefaultDisk($storedPath, $content);
                    } catch (\Throwable $error) {
                        continue;
                    }

                    $file->update([
                        'mime_type' => $mimeType,
                        'size' => $size,
                    ]);
                }
            });
    }

    private function officeMimeTypeForDocument(Document $document): string
    {
        return match ($document->type) {
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'powerpoint' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }

    private function renderTypeIndex(
        string $type,
        string $pageTitle,
        string $pageSubtitle,
        string $createRouteName
    ) {
        $user = auth()->user();
        $searchQuery = trim((string) request()->query('q', ''));

        $ownedDocuments = $this->repository->getOwnedDocumentsByType($user, $type);
        $sharedDocuments = $this->repository->getSharedDocumentsByType($user, $type);

        if ($searchQuery !== '') {
            $ownedDocuments = collect($this->searchService->rankDocuments($ownedDocuments, $searchQuery));
            $sharedDocuments = collect($this->searchService->rankDocuments($sharedDocuments, $searchQuery));
        }

        return view('online-docs.type', [
            'type' => $type,
            'pageTitle' => $pageTitle,
            'pageSubtitle' => $pageSubtitle,
            'createRouteName' => $createRouteName,
            'ownedDocuments' => $ownedDocuments,
            'sharedDocuments' => $sharedDocuments,
            'searchQuery' => $searchQuery,
        ]);
    }

    private function buildFolderBreadcrumbs(?PersonalFolder $folder): array
    {
        $breadcrumbs = [];
        $current = $folder;

        while ($current) {
            $breadcrumbs[] = [
                'id' => $current->id,
                'name' => $current->name,
            ];
            $current = $current->parent;
        }

        return array_reverse($breadcrumbs);
    }

    private function deleteFolderContents(PersonalFolder $folder): void
    {
        foreach ($folder->files as $file) {
            if (Storage::disk()->exists($file->stored_path)) {
                Storage::disk()->delete($file->stored_path);
            }
            $file->delete();
        }

        foreach ($folder->children as $child) {
            $this->deleteFolderContents($child);
            $child->delete();
        }
    }

    private function resolveTargetFolderId(int $userId, ?int $targetFolderId): ?int
    {
        if (!$targetFolderId) {
            return null;
        }

        return PersonalFolder::query()
            ->where('user_id', $userId)
            ->whereKey($targetFolderId)
            ->value('id');
    }

    private function moveItem(int $userId, string $type, int $id, ?int $targetFolderId): void
    {
        if ($type === 'folder') {
            $folder = PersonalFolder::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();

            if ($this->isDescendantFolder($folder, $targetFolderId)) {
                return;
            }

            $folder->update(['parent_id' => $targetFolderId]);
            return;
        }

        if ($type === 'file') {
            $file = PersonalFile::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            $file->update(['folder_id' => $targetFolderId]);
            return;
        }

        if ($type === 'link') {
            $link = PersonalDocumentLink::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            $link->update(['folder_id' => $targetFolderId]);
        }
    }

    private function deleteStorageItem(int $userId, string $type, int $id): void
    {
        if ($type === 'folder') {
            $folder = PersonalFolder::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            $this->deleteFolderContents($folder);
            $folder->delete();
            return;
        }

        if ($type === 'file') {
            $file = PersonalFile::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            if (Storage::disk()->exists($file->stored_path)) {
                Storage::disk()->delete($file->stored_path);
            }
            $file->delete();
            return;
        }

        if ($type === 'link') {
            $link = PersonalDocumentLink::query()
                ->where('user_id', $userId)
                ->whereKey($id)
                ->firstOrFail();
            $link->delete();
        }
    }

    private function isDescendantFolder(PersonalFolder $folder, ?int $targetFolderId): bool
    {
        if (!$targetFolderId) {
            return false;
        }

        $currentId = $targetFolderId;
        while ($currentId) {
            if ($currentId === $folder->id) {
                return true;
            }

            $currentId = PersonalFolder::query()
                ->where('user_id', $folder->user_id)
                ->whereKey($currentId)
                ->value('parent_id');
        }

        return false;
    }

    private function redirectToStorageFolder(Request $request, ?int $fallbackFolderId = null)
    {
        $redirectFolderId = $request->input('redirect_folder_id');
        $folderId = $redirectFolderId ?: $fallbackFolderId;

        return redirect()->route('online-docs.home', $folderId ? ['folder' => $folderId] : []);
    }

    private function normalizeStorageName(string $name, int $maxLen, string $default): string
    {
        $name = trim((string) $name);
        if (empty($name)) {
            return $default;
        }
        return mb_substr($name, 0, $maxLen, 'UTF-8');
    }

    /**
     * Remove chunk header lines prepended during indexing, e.g.:
     * "[File: report.pdf | Page: 3 | Section: Revenue | Type: pdf]"
     * These appear at the start of searchable_text and are not useful for display.
     */
    private function stripChunkHeader(string $text): string
    {
        $text = trim($text);
        // Remove one or more leading lines that look like "[File: ... | ...]"
        $text = (string) preg_replace('/^\[File:[^\]]+\]\s*/i', '', $text);
        return trim($text);
    }
}
