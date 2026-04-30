<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AIWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatbotController extends Controller
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

        $userRole = strtolower((string) ($data['user_role'] ?? 'user'));
        $normalizedRole = match ($userRole) {
            'admin', 'subadmin' => 'admin',
            'staff', 'substaff' => 'staff',
            default => 'user',
        };

        $workspaceScope = $this->resolveWorkspaceScope($data['workspace_id'] ?? null);

        return response()->stream(function () use ($data, $normalizedRole, $workspaceScope) {
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
            @ob_implicit_flush(true);

            $payload = json_encode([
                'message' => $data['message'],
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
                CURLOPT_WRITEFUNCTION => function ($ch, string $chunk): int {
                    echo $chunk;
                    @flush();
                    return strlen($chunk);
                },
            ]);

            curl_exec($ch);

            if (curl_errno($ch)) {
                Log::warning('Chatbot stream proxy error', [
                    'error' => curl_error($ch),
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
}
