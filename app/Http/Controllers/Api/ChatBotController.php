<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AIWorkspace;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatBotController extends Controller
{
    private const CHATBOT_MAX_EXECUTION_TIME = 600;
    private const CHATBOT_REQUEST_TIMEOUT = 590;
    private const CHATBOT_CANCEL_TIMEOUT = 5;

    public function chatBot(Request $request)
    {
        // Ensure PHP request runtime is not capped at default 30s.
        @ini_set('max_execution_time', (string) self::CHATBOT_MAX_EXECUTION_TIME);
        @set_time_limit(self::CHATBOT_MAX_EXECUTION_TIME);

        $data = $request->validate([
            'message' => 'required|string',
            'k' => 'nullable|integer',
            'lang' => 'nullable|string',
            'user_id' => 'nullable|string',
            'user_role' => 'nullable|string|in:admin,staff,user,subadmin,substaff',
            'workspace_id' => 'nullable|string',
            'request_id' => 'nullable|string|max:128',
        ]);

        $userRole = strtolower((string) ($data['user_role'] ?? 'user'));
        $normalizedRole = match ($userRole) {
            'admin', 'subadmin' => 'admin',
            'staff', 'substaff' => 'staff',
            default => 'user',
        };

        $workspaceScope = $this->resolveWorkspaceScope($data['workspace_id'] ?? null);

        try {
            $response = Http::connectTimeout(10)
                ->timeout(self::CHATBOT_REQUEST_TIMEOUT)
                ->post(
                    'http://127.0.0.1:8002/chat',
                    [
                        'message' => $data['message'],
                        'k' => $data['k'] ?? 5,
                        'lang' => $data['lang'] ?? 'en',
                        'user_id' => $data['user_id'] ?? null,
                        'user_role' => $normalizedRole,
                        'workspace_id' => $workspaceScope,
                        'request_id' => $data['request_id'] ?? null,
                    ]
                );

            if ($response->failed()) {
                if ($response->status() === 499) {
                    return response()->json([
                        'message' => 'Request canceled',
                        'request_id' => $data['request_id'] ?? null,
                    ], 499);
                }

                Log::error('Chatbot service error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'message' => 'Chatbot service error',
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ], 500);
            }

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('Chatbot controller exception', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage(), // keep for debugging
            ], 500);
        }
    }

    public function stopChatBot(Request $request)
    {
        $data = $request->validate([
            'request_id' => 'required|string|max:128',
        ]);

        try {
            $response = Http::connectTimeout(2)
                ->timeout(self::CHATBOT_CANCEL_TIMEOUT)
                ->post('http://127.0.0.1:8002/chat/cancel', [
                    'request_id' => $data['request_id'],
                ]);

            if ($response->failed()) {
                Log::warning('Chatbot cancel service error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request_id' => $data['request_id'],
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Cancel request failed',
                    'request_id' => $data['request_id'],
                ], 502);
            }

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            Log::error('Chatbot cancel exception', [
                'request_id' => $data['request_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Cancel request exception',
                'request_id' => $data['request_id'],
            ], 500);
        }
    }

    public function chatBotStream(Request $request): StreamedResponse
    {
        @ini_set('max_execution_time', (string) self::CHATBOT_MAX_EXECUTION_TIME);
        @set_time_limit(self::CHATBOT_MAX_EXECUTION_TIME);

        $data = $request->validate([
            'message' => 'required|string',
            'k' => 'nullable|integer',
            'lang' => 'nullable|string',
            'user_id' => 'nullable|string',
            'user_role' => 'nullable|string|in:admin,staff,user,subadmin,substaff',
            'workspace_id' => 'nullable|string',
            'request_id' => 'nullable|string|max:128',
        ]);

        $user = auth()->user();

        $userRole = match ($user->role) {
            'admin', 'subadmin' => 'admin',
            'staff', 'substaff' => 'staff',
            default => 'user',
        };
        
        $normalizedRole = match ($userRole) {
            'admin', 'subadmin' => 'admin',
            'staff', 'substaff' => 'staff',
            default => 'user',
        };

        $workspaceScope = $this->resolveWorkspaceScope($data['workspace_id'] ?? null);

        return response()->stream(function () use ($data, $normalizedRole, $workspaceScope) {
            ignore_user_abort(false);

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
            @ob_implicit_flush(true);

            $message = $this->sanitizePrompt($data['message']);

            if ($this->containsPromptInjection($message)) {
                abort(response()->json([
                    'error' => 'Unsafe prompt detected.'
                ], 400));
            }

            $payload = json_encode([
                'message' => $message,
                'k' => $data['k'] ?? 5,
                'lang' => $data['lang'] ?? 'en',
                'user_id' => $data['user_id'] ?? null,
                'user_role' => $normalizedRole,
                'workspace_id' => $workspaceScope,
                'request_id' => $data['request_id'] ?? null,
            ]);

            $ch = curl_init('http://127.0.0.1:8002/chat/stream');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: text/plain',
                ],
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => self::CHATBOT_REQUEST_TIMEOUT,
                CURLOPT_NOPROGRESS => false,
                CURLOPT_XFERINFOFUNCTION => function () {
                    if (connection_aborted()) {
                        return 1;
                    }

                    return 0;
                },
                CURLOPT_WRITEFUNCTION => function ($ch, string $chunk): int {
                    if (connection_aborted()) {
                        return 0;
                    }

                    echo $chunk;
                    @flush();
                    return strlen($chunk);
                },
            ]);

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                Log::warning('Chatbot stream proxy error', [
                    'error' => curl_error($ch),
                    'request_id' => $data['request_id'] ?? null,
                ]);
            }

            if ($result === false && connection_aborted()) {
                Log::info('Chatbot stream aborted by client', [
                    'request_id' => $data['request_id'] ?? null,
                ]);
            }

            curl_close($ch);
        }, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function summarizeConversation(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'lang'  => 'nullable|string|in:auto,vi,en',
            'style' => 'nullable|string|in:bullet,paragraph,short',
            'limit' => 'nullable|integer|min:10|max:500',
        ]);

        $lang  = $data['lang']  ?? 'auto';
        $style = $data['style'] ?? 'bullet';
        $limit = (int) ($data['limit'] ?? 100);

        $messages = $conversation->messages()
            ->with('user:id,name')
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($m) use ($user) {
                $name    = $m->user->name ?? 'Unknown';
                $content = trim($m->content ?? '');
                return [
                    'role'    => $m->user_id === $user->id ? 'user' : 'assistant',
                    'name'    => $name,
                    'content' => "[{$name}]: {$content}",
                    '_raw'    => $content,
                ];
            })
            ->filter(fn($m) => $m['_raw'] !== '')
            ->map(fn($m) => ['role' => $m['role'], 'content' => $m['content']])
            ->values()
            ->all();

        if (empty($messages)) {
            return response()->json(['summary' => '', 'message_count' => 0, 'truncated' => false]);
        }

        try {
            $response = Http::connectTimeout(10)
                ->timeout(self::CHATBOT_REQUEST_TIMEOUT)
                ->post('http://127.0.0.1:8002/summary/messages', [
                    'messages' => $messages,
                    'lang'     => $lang,
                    'style'    => $style,
                ]);

            if ($response->failed()) {
                Log::error('Summary service error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return response()->json(['message' => 'Summary service error'], 500);
            }

            return response()->json($response->json());

        } catch (\Exception $e) {
            Log::error('summarizeConversation exception', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function summarizeWorkspace(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'workspace_id' => 'required|string',
            'lang'         => 'nullable|string|in:auto,vi,en',
            'style'        => 'nullable|string|in:bullet,paragraph,short',
            'n_clusters'   => 'nullable|integer|min:3|max:20',
        ]);

        $workspaceId = $this->resolveWorkspaceScope($data['workspace_id']);

        $workspace = AIWorkspace::find($data['workspace_id']);
        if ($workspace && Gate::denies('view', $workspace)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            @ini_set('max_execution_time', (string) self::CHATBOT_MAX_EXECUTION_TIME);
            @set_time_limit(self::CHATBOT_MAX_EXECUTION_TIME);

            $response = Http::connectTimeout(10)
                ->timeout(self::CHATBOT_REQUEST_TIMEOUT)
                ->post('http://127.0.0.1:8002/summary/workspace', [
                    'workspace_id' => $workspaceId,
                    'lang'         => $data['lang']       ?? 'auto',
                    'style'        => $data['style']      ?? 'bullet',
                    'n_clusters'   => $data['n_clusters'] ?? 10,
                ]);

            if ($response->failed()) {
                Log::error('summarizeWorkspace service error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['message' => 'Summary service error', 'error' => $response->body()], 500);
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('summarizeWorkspace exception', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function summarizeWorkspaceStream(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'workspace_id' => 'required|string',
            'lang'         => 'nullable|string|in:auto,vi,en',
            'style'        => 'nullable|string|in:bullet,paragraph,short',
            'n_clusters'   => 'nullable|integer|min:3|max:20',
        ]);

        $workspaceId = $this->resolveWorkspaceScope($data['workspace_id']);

        $workspace = AIWorkspace::find($data['workspace_id']);
        if ($workspace && Gate::denies('view', $workspace)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->stream(function () use ($data, $workspaceId) {
            while (ob_get_level() > 0) { @ob_end_flush(); }
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
            @ob_implicit_flush(true);

            $payload = json_encode([
                'workspace_id' => $workspaceId,
                'lang'         => $data['lang']       ?? 'auto',
                'style'        => $data['style']       ?? 'bullet',
                'n_clusters'   => $data['n_clusters']  ?? 10,
            ]);

            $ch = curl_init('http://127.0.0.1:8002/summary/workspace/stream');
            curl_setopt_array($ch, [
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => $payload,
                CURLOPT_HTTPHEADER      => ['Content-Type: application/json', 'Accept: text/plain'],
                CURLOPT_RETURNTRANSFER  => false,
                CURLOPT_HEADER          => false,
                CURLOPT_FOLLOWLOCATION  => false,
                CURLOPT_CONNECTTIMEOUT  => 10,
                CURLOPT_TIMEOUT         => self::CHATBOT_REQUEST_TIMEOUT,
                CURLOPT_NOPROGRESS      => false,
                CURLOPT_XFERINFOFUNCTION => function () {
                    return connection_aborted() ? 1 : 0;
                },
                CURLOPT_WRITEFUNCTION   => function ($ch, string $chunk): int {
                    if (connection_aborted()) return 0;
                    echo $chunk;
                    @flush();
                    return strlen($chunk);
                },
            ]);

            curl_exec($ch);

            if (curl_errno($ch)) {
                Log::warning('summarizeWorkspaceStream proxy error', ['error' => curl_error($ch)]);
            }

            curl_close($ch);
        }, 200, [
            'Content-Type'      => 'text/plain; charset=utf-8',
            'X-Accel-Buffering' => 'no',
            'Cache-Control'     => 'no-cache',
        ]);
    }

    public function summarizeDocument(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'workspace_id' => 'required|string',
            's3_key'       => 'nullable|string',
            'lang'         => 'nullable|string|in:auto,vi,en',
            'style'        => 'nullable|string|in:bullet,paragraph,short',
            'n_clusters'   => 'nullable|integer|min:3|max:20',
        ]);

        $rawWorkspaceId = trim((string) $data['workspace_id']);

        // Personal file workspaces (personal_file_{id}) are stored entirely in ChromaDB.
        // Use /summary/workspace which reads stored embeddings directly — no S3 download needed.
        $isPersonalFile = str_starts_with($rawWorkspaceId, 'personal_file_');
        if ($isPersonalFile) {
            $personalFileId = (int) str_replace('personal_file_', '', $rawWorkspaceId);
            $file = \App\Models\PersonalFile::where('id', $personalFileId)
                ->where('user_id', $user->id)
                ->first();

            if (!$file) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            try {
                @ini_set('max_execution_time', (string) self::CHATBOT_MAX_EXECUTION_TIME);
                @set_time_limit(self::CHATBOT_MAX_EXECUTION_TIME);

                $response = Http::connectTimeout(10)
                    ->timeout(self::CHATBOT_REQUEST_TIMEOUT)
                    ->post('http://127.0.0.1:8002/summary/workspace', [
                        'workspace_id' => $rawWorkspaceId,
                        'user_id'      => (string) $user->id,
                        'lang'         => $data['lang']       ?? 'auto',
                        'style'        => $data['style']      ?? 'bullet',
                        'n_clusters'   => $data['n_clusters'] ?? 10,
                    ]);

                if ($response->failed()) {
                    Log::error('summarizeDocument(personal) service error', ['status' => $response->status(), 'body' => $response->body()]);
                    return response()->json(['message' => 'Summary service error', 'error' => $response->body()], 500);
                }

                return response()->json($response->json());
            } catch (\Exception $e) {
                Log::error('summarizeDocument(personal) exception', ['error' => $e->getMessage()]);
                return response()->json(['message' => $e->getMessage()], 500);
            }
        }

        $workspaceId = $this->resolveWorkspaceScope($rawWorkspaceId);

        $workspace = AIWorkspace::find($rawWorkspaceId);
        if ($workspace && Gate::denies('view', $workspace)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $s3Key = trim((string) ($data['s3_key'] ?? ''));
        if ($s3Key === '') {
            return response()->json(['message' => 'Validation error: s3_key is required for workspace documents'], 422);
        }

        try {
            @ini_set('max_execution_time', (string) self::CHATBOT_MAX_EXECUTION_TIME);
            @set_time_limit(self::CHATBOT_MAX_EXECUTION_TIME);

            $response = Http::connectTimeout(10)
                ->timeout(self::CHATBOT_REQUEST_TIMEOUT)
                ->post('http://127.0.0.1:8002/summary/document', [
                    'workspace_id' => $workspaceId,
                    's3_key'       => $s3Key,
                    'lang'         => $data['lang']       ?? 'auto',
                    'style'        => $data['style']      ?? 'bullet',
                    'n_clusters'   => $data['n_clusters'] ?? 10,
                ]);

            if ($response->failed()) {
                Log::error('summarizeDocument service error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['message' => 'Summary service error', 'error' => $response->body()], 500);
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('summarizeDocument exception', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function resolveWorkspaceScope(?string $workspaceInput): string
    {
        $raw = trim((string) ($workspaceInput ?? ''));
        if ($raw === '') {
            return 'global';
        }

        $workspace = ctype_digit($raw)
            ? AIWorkspace::query()->find((int) $raw)
            : AIWorkspace::query()->where('slug', $raw)->orWhere('id', $raw)->first();

        if (!$workspace) {
            return preg_replace('/[^A-Za-z0-9_-]/', '_', $raw) ?: 'global';
        }

        if ($workspace->visibility === 'public') {
            return 'public';
        }

        return (string) $workspace->id;
    }

    private function sanitizePrompt(string $input): string
    {
        // Remove null bytes/control chars
        $input = preg_replace('/[\x00-\x1F\x7F]/u', '', $input);

        // Normalize whitespace
        $input = preg_replace('/\s+/', ' ', $input);

        return trim($input);
    }

    private function containsPromptInjection(string $text): bool
    {
        $patterns = [
            '/ignore\s+(all|previous)\s+instructions/i',
            '/reveal\s+(system|prompt)/i',
            '/you\s+are\s+now/i',
            '/act\s+as/i',
            '/developer\s+mode/i',
            '/jailbreak/i',
            '/\bDAN\b/i',
            '/bypass/i',
            '/disable\s+safety/i',
            '/print\s+your\s+instructions/i',
            '/show\s+hidden\s+prompt/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }
}
