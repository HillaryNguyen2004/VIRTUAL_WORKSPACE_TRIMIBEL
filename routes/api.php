<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\Api\WhatsAppAdminController;
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

    // WhatsApp Team Inbox Routes
    Route::prefix('whatsapp')->group(function () {
        // Inbox & customers
        Route::get('/inbox', [WhatsAppController::class, 'inbox']);
        Route::get('/customers/{whatsapp_customer}/details', [WhatsAppController::class, 'getCustomer']);
        Route::get('/customers/{whatsapp_customer}/messages', [WhatsAppController::class, 'getMessages']);
        Route::patch('/customers/{whatsapp_customer}', [WhatsAppController::class, 'update']);
        Route::post('/customers/{whatsapp_customer}/assign', [WhatsAppController::class, 'assign']);

        // Messaging
        Route::post('/customers/{whatsapp_customer}/reply', [WhatsAppController::class, 'sendReply']);
        Route::post('/customers/{whatsapp_customer}/template', [WhatsAppController::class, 'sendTemplate']);
        Route::post('/customers/{whatsapp_customer}/canned-reply', [WhatsAppController::class, 'useCannedReply']);

        // Follow-ups
        Route::get('/follow-ups', [WhatsAppController::class, 'getFollowUps']);
        Route::post('/customers/{whatsapp_customer}/follow-up', [WhatsAppController::class, 'scheduleFollowUp']);
        Route::patch('/follow-ups/{whatsapp_follow_up}/complete', [WhatsAppController::class, 'completeFollowUp']);

        // Templates & canned replies
        Route::get('/canned-replies', [WhatsAppController::class, 'getCannedReplies']);
        Route::get('/templates', [WhatsAppController::class, 'getTemplates']);
    });

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

// Chat bot
Route::post('/chat-bot', [ChatbotController::class, 'chatBot']);

// WhatsApp Admin Routes (for managing settings, templates, canned replies)
Route::middleware(['auth:sanctum'])->prefix('whatsapp-admin')->group(function () {
    // Settings
    Route::get('/settings', [WhatsAppAdminController::class, 'getSettings']);
    Route::put('/settings', [WhatsAppAdminController::class, 'updateSettings']);

    // Canned replies management
    Route::get('/canned-replies', [WhatsAppAdminController::class, 'listCannedReplies']);
    Route::post('/canned-replies', [WhatsAppAdminController::class, 'createCannedReply']);
    Route::put('/canned-replies/{canned_reply}', [WhatsAppAdminController::class, 'updateCannedReply']);
    Route::delete('/canned-replies/{canned_reply}', [WhatsAppAdminController::class, 'deleteCannedReply']);

    // Template management
    Route::get('/templates', [WhatsAppAdminController::class, 'listTemplates']);
    Route::post('/templates', [WhatsAppAdminController::class, 'createTemplate']);
    Route::put('/templates/{whatsapp_message_template}', [WhatsAppAdminController::class, 'updateTemplate']);
    Route::delete('/templates/{whatsapp_message_template}', [WhatsAppAdminController::class, 'deleteTemplate']);
    Route::post('/templates/{whatsapp_message_template}/sync', [WhatsAppAdminController::class, 'syncTemplateStatus']);
});

// WhatsApp Webhook Routes (public endpoints for Meta to call)
Route::prefix('webhooks/whatsapp')->group(function () {
    Route::get('/', [WhatsAppWebhookController::class, 'verify']); // For webhook verification
    Route::post('/', [WhatsAppWebhookController::class, 'receive']); // For receiving messages & statuses
});