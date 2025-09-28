<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\ChatController;

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
Route::get('/sanctum/csrf-cookie', function() {
    return response()->json(['message' => 'CSRF cookie set']);
});

// Chat API Routes (using Sanctum with web session support)
Route::middleware(['auth:sanctum'])->prefix('chat')->group(function () {
    Route::get('/conversations', [ChatController::class, 'getConversations']);
    Route::post('/conversations', [ChatController::class, 'createConversation']);
    Route::get('/conversations/{conversation}', [ChatController::class, 'getConversation']);
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages']);
    Route::post('/conversations/{conversation}/read', [ChatController::class, 'markAsRead']);
    Route::post('/conversations/{conversation}/typing', [ChatController::class, 'setTyping']);
    Route::get('/users/search', [ChatController::class, 'searchUsers']);
    Route::get('/users/online', [ChatController::class, 'getOnlineUsers']);
    
    // File upload routes
    Route::post('/conversations/{conversation}/upload-file', [ChatController::class, 'uploadFile']);
    Route::post('/conversations/{conversation}/upload-image', [ChatController::class, 'uploadImage']);
    
    // Video meeting integration
    Route::post('/conversations/{conversation}/video-call', [ChatController::class, 'createVideoCall']);
    Route::post('/conversations/{conversation}/join-video', [ChatController::class, 'joinVideoCall']);
});