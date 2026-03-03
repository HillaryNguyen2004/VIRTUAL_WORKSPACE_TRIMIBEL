<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\WhatsAppCustomer;
use App\Models\WhatsAppConversation;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WhatsAppMessageStatus;
use App\Services\WhatsAppService;
use App\Events\MessageSent;

class WhatsAppWebhookController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Webhook verification endpoint (GET)
     */
    public function verify(Request $request)
    {
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($this->whatsappService->verifyWebhookToken($token)) {
            return $challenge;
        }

        return response('Unauthorized', 403);
    }

    /**
     * Webhook receiver (POST)
     */
    public function receive(Request $request)
    {
        try {
            $payload = $request->getContent();
            
            // Log for debugging
            Log::info('WhatsApp webhook received', [
                'payload' => $payload
            ]);

            $data = $request->json()->all();

            // Handle different webhook events
            if ($request->input('entry.0.changes')) {
                foreach ($request->input('entry.0.changes') as $change) {
                    $value = $change['value'] ?? [];

                    // Handle incoming messages
                    if (!empty($value['messages'])) {
                        foreach ($value['messages'] as $message) {
                            $this->handleIncomingMessage($message, $value);
                        }
                    }

                    // Handle delivery/read status updates
                    if (!empty($value['statuses'])) {
                        foreach ($value['statuses'] as $status) {
                            $this->handleStatusUpdate($status);
                        }
                    }
                }
            }

            return response('ok', 200);

        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp webhook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response('error', 500);
        }
    }

    /**
     * Handle incoming message from WhatsApp
     */
    private function handleIncomingMessage(array $messageData, array $contactData)
    {
        try {
            DB::transaction(function () use ($messageData, $contactData) {
                $senderWaId = $messageData['from'] ?? null;
                $messageId = $messageData['id'] ?? null;
                $timestamp = $messageData['timestamp'] ?? null;

                if (!$senderWaId || !$messageId) {
                    Log::warning('Invalid WhatsApp message data', $messageData);
                    return;
                }

                // Find or create customer
                $customer = WhatsAppCustomer::firstOrCreate(
                    ['wa_id' => $senderWaId],
                    [
                        'phone' => $senderWaId,
                        'stage' => 'new'
                    ]
                );

                // Update customer info if available
                if (isset($contactData['contacts'][0])) {
                    $contact = $contactData['contacts'][0];
                    $customer->update([
                        'display_name' => $contact['profile']['name'] ?? $customer->display_name,
                        'last_contact_at' => $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : now()
                    ]);
                } else {
                    $customer->update(['last_contact_at' => now()]);
                }

                // Find or create conversation
                $conversation = Conversation::firstOrCreate(
                    [
                        'type' => 'direct',
                        'created_by' => 0 // System user for WhatsApp
                    ],
                    [
                        'name' => $customer->display_name ?? $customer->phone
                    ]
                );

                // Add customer as participant (create virtual user if needed)
                if (!$conversation->participants->contains($customer->id)) {
                    $conversation->participants()->attach($customer->id ?: 0);
                }

                // Link WhatsApp conversation
                $whatsappConv = WhatsAppConversation::firstOrCreate(
                    [
                        'conversation_id' => $conversation->id,
                        'whatsapp_customer_id' => $customer->id
                    ],
                    [
                        'is_open' => true,
                        'opened_at' => now()
                    ]
                );

                // Open service window
                $whatsappConv->openServiceWindow();

                // Extract message content
                $content = $this->extractMessageContent($messageData);

                // Create message in app
                $message = $conversation->messages()->create([
                    'user_id' => $customer->id,
                    'content' => $content,
                    'type' => $messageData['type'] ?? 'text',
                    'platform' => 'whatsapp',
                    'direction' => 'in',
                    'metadata' => json_encode([
                        'whatsapp_message_id' => $messageId,
                        'message_type' => $messageData['type'] ?? 'text',
                        'timestamp' => $timestamp
                    ])
                ]);

                // Store platform message ID
                WhatsAppMessageStatus::create([
                    'message_id' => $message->id,
                    'platform_message_id' => $messageId,
                    'status' => 'delivered'
                ]);

                // Update conversation timestamp
                $conversation->touch();
                $whatsappConv->update(['last_message_at' => now()]);

                // Mark message as read
                $this->whatsappService->markAsRead($messageId);

                // Broadcast event to team
                broadcast(new MessageSent($message, $conversation))->toOthers();

                Log::info('WhatsApp message created', [
                    'message_id' => $message->id,
                    'customer_id' => $customer->id,
                    'platform_id' => $messageId
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Error handling WhatsApp incoming message: ' . $e->getMessage(), [
                'message_data' => $messageData,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle status updates (delivered, read, failed)
     */
    private function handleStatusUpdate(array $statusData)
    {
        try {
            $messageId = $statusData['id'] ?? null;
            $status = $statusData['status'] ?? null;
            $timestamp = $statusData['timestamp'] ?? null;

            if (!$messageId || !$status) {
                return;
            }

            // Find message by platform ID
            $whatsappStatus = WhatsAppMessageStatus::where('platform_message_id', $messageId)->first();

            if ($whatsappStatus) {
                $whatsappStatus->updateFromWebhook($status, $timestamp);
                
                Log::info('WhatsApp message status updated', [
                    'platform_id' => $messageId,
                    'status' => $status
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error handling WhatsApp status update: ' . $e->getMessage());
        }
    }

    /**
     * Extract content from different message types
     */
    private function extractMessageContent(array $messageData): string
    {
        $type = $messageData['type'] ?? 'text';

        return match($type) {
            'text' => $messageData['text']['body'] ?? 'Message',
            'image' => '[Image] ' . ($messageData['image']['caption'] ?? 'Image shared'),
            'document' => '[Document] ' . ($messageData['document']['filename'] ?? 'File shared'),
            'audio' => '[Audio] Audio message',
            'video' => '[Video] Video message',
            'sticker' => '[Sticker] Sticker shared',
            'location' => '[Location] ' . ($messageData['location']['address'] ?? 'Location shared'),
            'contacts' => '[Contact] Contact shared',
            'reaction' => '[Reaction] ' . ($messageData['reaction']['emoji'] ?? 'Reaction'),
            default => 'Message'
        };
    }
}
