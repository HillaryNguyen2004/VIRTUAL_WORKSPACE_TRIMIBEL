<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AIWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SearchAgentController extends Controller
{
    private const BASE_URL           = 'http://127.0.0.1:8002';
    private const MAX_EXECUTION_TIME = 600;
    private const REQUEST_TIMEOUT    = 590;
    private const CANCEL_TIMEOUT     = 5;
    private const HISTORY_LIMIT      = 10; // max conversation pairs forwarded

    // -------------------------------------------------------------------------
    // POST /agent/answer  — blocking full RAG answer
    // -------------------------------------------------------------------------
    public function answer(Request $request)
    {
        @ini_set('max_execution_time', (string) self::MAX_EXECUTION_TIME);
        @set_time_limit(self::MAX_EXECUTION_TIME);

        $data = $request->validate([
            'query'             => 'required|string',
            'k'                 => 'nullable|integer|min:1|max:50',
            'lang'              => 'nullable|string',
            'user_role'         => 'nullable|string|in:admin,staff,user,subadmin,substaff',
            'workspace_id'      => 'nullable|string',
            'request_id'        => 'nullable|string|max:128',
            'history'           => 'nullable|array',
            'history.*.role'    => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
        ]);

        $normalizedRole = $this->normalizeRole($data['user_role'] ?? 'user');
        $workspaceScope = $this->resolveWorkspaceScope($data['workspace_id'] ?? null);
        [$history, $historyText] = $this->buildHistory($data['history'] ?? []);

        try {
            $response = Http::connectTimeout(10)
                ->timeout(self::REQUEST_TIMEOUT)
                ->post(self::BASE_URL . '/agent/answer', [
                    'query'        => $data['query'],
                    'workspace_id' => $workspaceScope,
                    'user_role'    => $normalizedRole,
                    'k'            => $data['k'] ?? 5,
                    'lang'         => $data['lang'] ?? null,
                    'history'      => $history,
                    'history_text' => $historyText,
                    'where'        => null,
                    'request_id'   => $data['request_id'] ?? null,
                ]);

            if ($response->failed()) {
                if ($response->status() === 499) {
                    return response()->json([
                        'message'    => 'Request canceled',
                        'request_id' => $data['request_id'] ?? null,
                    ], 499);
                }

                Log::error('SearchAgent answer error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return response()->json([
                    'message' => 'Search agent service error',
                    'status'  => $response->status(),
                    'body'    => $response->json() ?? $response->body(),
                ], 500);
            }

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('SearchAgent answer exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Internal server error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /agent/answer/stream  — streaming RAG answer
    // -------------------------------------------------------------------------
    public function answerStream(Request $request): StreamedResponse
    {
        @ini_set('max_execution_time', (string) self::MAX_EXECUTION_TIME);
        @set_time_limit(self::MAX_EXECUTION_TIME);

        $data = $request->validate([
            'query'             => 'required|string',
            'k'                 => 'nullable|integer|min:1|max:50',
            'lang'              => 'nullable|string',
            'user_role'         => 'nullable|string|in:admin,staff,user,subadmin,substaff',
            'workspace_id'      => 'nullable|string',
            'request_id'        => 'nullable|string|max:128',
            'history'           => 'nullable|array',
            'history.*.role'    => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
        ]);

        $normalizedRole = $this->normalizeRole($data['user_role'] ?? 'user');
        $workspaceScope = $this->resolveWorkspaceScope($data['workspace_id'] ?? null);
        [$history, $historyText] = $this->buildHistory($data['history'] ?? []);

        return response()->stream(function () use ($data, $normalizedRole, $workspaceScope, $history, $historyText) {
            ignore_user_abort(false);

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
            @ob_implicit_flush(true);

            $payload = json_encode([
                'query'        => $data['query'],
                'workspace_id' => $workspaceScope,
                'user_role'    => $normalizedRole,
                'k'            => $data['k'] ?? 5,
                'lang'         => $data['lang'] ?? null,
                'history'      => $history,
                'history_text' => $historyText,
                'where'        => null,
                'request_id'   => $data['request_id'] ?? null,
            ]);

            $ch = curl_init(self::BASE_URL . '/agent/answer/stream');
            curl_setopt_array($ch, [
                CURLOPT_POST            => true,
                CURLOPT_POSTFIELDS      => $payload,
                CURLOPT_HTTPHEADER      => [
                    'Content-Type: application/json',
                    'Accept: text/plain',
                ],
                CURLOPT_RETURNTRANSFER  => false,
                CURLOPT_HEADER          => false,
                CURLOPT_FOLLOWLOCATION  => false,
                CURLOPT_CONNECTTIMEOUT  => 10,
                CURLOPT_TIMEOUT         => self::REQUEST_TIMEOUT,
                CURLOPT_NOPROGRESS      => false,
                CURLOPT_XFERINFOFUNCTION => function () {
                    return connection_aborted() ? 1 : 0;
                },
                CURLOPT_WRITEFUNCTION   => function ($ch, string $chunk): int {
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
                Log::warning('SearchAgent stream proxy error', [
                    'error'      => curl_error($ch),
                    'request_id' => $data['request_id'] ?? null,
                ]);
            }

            if ($result === false && connection_aborted()) {
                Log::info('SearchAgent stream aborted by client', [
                    'request_id' => $data['request_id'] ?? null,
                ]);
            }

            curl_close($ch);
        }, 200, [
            'Content-Type'      => 'text/plain; charset=utf-8',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /agent/search  — retrieval only, no generation
    // -------------------------------------------------------------------------
    public function search(Request $request)
    {
        $data = $request->validate([
            'query'             => 'required|string',
            'workspace_id'      => 'nullable|string',
            'k'                 => 'nullable|integer|min:1|max:50',
            'lang'              => 'nullable|string',
            'history'           => 'nullable|array',
            'history.*.role'    => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
        ]);

        $workspaceScope = $this->resolveWorkspaceScope($data['workspace_id'] ?? null);
        [$history]      = $this->buildHistory($data['history'] ?? []);

        try {
            $response = Http::connectTimeout(10)
                ->timeout(60)
                ->post(self::BASE_URL . '/agent/search', [
                    'query'        => $data['query'],
                    'workspace_id' => $workspaceScope,
                    'k'            => $data['k'] ?? 5,
                    'lang'         => $data['lang'] ?? null,
                    'history'      => $history,
                    'where'        => null,
                ]);

            if ($response->failed()) {
                Log::error('SearchAgent search error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return response()->json([
                    'message' => 'Search service error',
                    'status'  => $response->status(),
                ], 500);
            }

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('SearchAgent search exception', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Internal server error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /agent/stop  — cancel an in-flight agent/answer request
    // -------------------------------------------------------------------------
    public function stop(Request $request)
    {
        $data = $request->validate([
            'request_id' => 'required|string|max:128',
        ]);

        try {
            $response = Http::connectTimeout(2)
                ->timeout(self::CANCEL_TIMEOUT)
                ->post(self::BASE_URL . '/chat/cancel', [
                    'request_id' => $data['request_id'],
                ]);

            if ($response->failed()) {
                Log::warning('SearchAgent cancel error', [
                    'status'     => $response->status(),
                    'request_id' => $data['request_id'],
                ]);
                return response()->json([
                    'ok'         => false,
                    'message'    => 'Cancel request failed',
                    'request_id' => $data['request_id'],
                ], 502);
            }

            return response()->json($response->json(), $response->status());

        } catch (\Exception $e) {
            Log::error('SearchAgent cancel exception', [
                'request_id' => $data['request_id'],
                'error'      => $e->getMessage(),
            ]);
            return response()->json([
                'ok'         => false,
                'message'    => 'Cancel request exception',
                'request_id' => $data['request_id'],
            ], 500);
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function normalizeRole(string $role): string
    {
        return match (strtolower($role)) {
            'admin', 'subadmin' => 'admin',
            'staff', 'substaff' => 'staff',
            default             => 'user',
        };
    }

    /**
     * Trim history to the last N pairs and build both the structured list
     * and the plain-text string expected by the Python agent.
     *
     * @return array [structured_history, history_text]
     */
    private function buildHistory(array $raw): array
    {
        $pairs = array_slice($raw, -(self::HISTORY_LIMIT * 2));

        $structured = array_map(fn($m) => [
            'role'    => $m['role'],
            'content' => $m['content'],
        ], $pairs);

        $lines = array_map(
            fn($m) => ucfirst($m['role']) . ': ' . $m['content'],
            $pairs
        );

        return [$structured, implode("\n", $lines)];
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

        return $workspace->visibility === 'public' ? 'public' : (string) $workspace->id;
    }
}
