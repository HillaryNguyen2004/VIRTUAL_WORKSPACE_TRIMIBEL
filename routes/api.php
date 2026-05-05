<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatBotController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\MeetingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/meetings/{roomName}/details', [MeetingController::class, 'meteredRoomDetails']);

// Public check-in (creates token)
Route::post('/check-in', [CheckInController::class, 'checkIn']);

// Authenticated routes (require token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/check-out', [CheckInController::class, 'checkOut']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// CSRF Cookie route for Sanctum
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});

// Chat API Routes (using Sanctum with web session support)
Route::middleware(['auth:sanctum'])->prefix('chat')->group(function () {
    Route::get('/conversations', [ChatController::class, 'getConversations']);
    Route::post('/conversations', [ChatController::class, 'createConversation']);
    Route::get('/conversations/{conversation}', [ChatController::class, 'getConversation']);
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/conversations/{conversation}/files', [ChatController::class, 'sendFile']);
    Route::post('/conversations/{conversation}/images', [ChatController::class, 'sendImage']);
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages']);
    Route::post('/conversations/{conversation}/read', [ChatController::class, 'markAsRead']);
    Route::post('/conversations/{conversation}/typing', [ChatController::class, 'setTyping']);
    Route::get('/users/search', [ChatController::class, 'searchUsers']);
    Route::post('/conversations/{conversation}/participants', [ChatController::class, 'addParticipants']);
    Route::get('/users/online', [ChatController::class, 'getOnlineUsers']);

    // Video meeting integration
    Route::post('/conversations/{conversation}/video-call', [ChatController::class, 'createVideoCall']);
    Route::post('/conversations/{conversation}/join-video', [ChatController::class, 'joinVideoCall']);

    // Channels (similar to Discord channels)
    Route::get('/channels', [ChannelController::class, 'index']);
    Route::post('/channels', [ChannelController::class, 'store']);
    Route::get('/channels/{channel}', [ChannelController::class, 'show']);
    Route::post('/channels/{channel}/join', [ChannelController::class, 'join']);
    Route::post('/channels/{channel}/leave', [ChannelController::class, 'leave']);
    Route::get('/channels/{channel}/rules', [ChannelController::class, 'rules']);
    Route::post('/channels/{channel}/rules', [ChannelController::class, 'addRule']);
    Route::post('/channels/{channel}/messages', [ChannelController::class, 'postMessage']);
});

// Chat bot - DISABLED (controller missing)
Route::post('/chat-bot', [ChatBotController::class, 'chatBot']);
Route::post('/chat-bot/stream', [ChatBotController::class, 'chatBotStream']);
Route::post('/chat-bot/stop', [ChatBotController::class, 'stopChatBot']);

// Task API Routes for Kanban board (support both Sanctum and web auth)
Route::prefix('tasks')->group(function () {
    Route::post('/{task}/move', [\App\Http\Controllers\Api\TaskController::class, 'moveToPhase'])->middleware('auth:sanctum');
    Route::post('/reorder', [\App\Http\Controllers\Api\TaskController::class, 'reorder'])->middleware('auth:sanctum');
    Route::patch('/{task}/status', [\App\Http\Controllers\Api\TaskController::class, 'updateStatus'])->middleware('auth:sanctum');
});
