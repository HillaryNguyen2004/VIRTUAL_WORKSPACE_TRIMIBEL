<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function chatBot(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string',
            'k' => 'nullable|integer',
            'lang' => 'nullable|string',
            'user_id' => 'nullable|string',
        ]);

        try {
            $response = Http::timeout(120)->post(
                'http://127.0.0.1:8002/chat',
                [
                    'message' => $data['message'],
                    'k' => $data['k'] ?? 5,
                    'lang' => $data['lang'] ?? 'en',
                    'user_id' => $data['user_id'] ?? null,
                ]
            );

            if ($response->failed()) {
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
}
