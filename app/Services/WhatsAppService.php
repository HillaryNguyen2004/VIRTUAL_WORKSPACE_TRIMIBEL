<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppSettings;
use App\Models\Message;
use App\Models\WhatsAppMessageStatus;
use Carbon\Carbon;

class WhatsAppService
{
    protected $settings;

    public function __construct()
    {
        $this->settings = WhatsAppSettings::active();
    }

    /**
     * Send a free-form text message
     */
    public function sendMessage(string $phoneNumber, string $content, Message $message = null): ?string
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'text',
                'text' => [
                    'body' => $content
                ]
            ];

            $response = Http::withHeaders($this->settings->getApiHeaders())
                ->post($this->settings->getApiUrl('messages'), $payload);

            if (!$response->successful()) {
                throw new \Exception('WhatsApp API error: ' . $response->body());
            }

            $messageId = $response->json('messages.0.id');

            // Store platform message ID if message object provided
            if ($message) {
                WhatsAppMessageStatus::create([
                    'message_id' => $message->id,
                    'platform_message_id' => $messageId,
                    'status' => 'sent'
                ]);
            }

            Log::info('WhatsApp message sent', [
                'phone' => $phoneNumber,
                'platform_message_id' => $messageId
            ]);

            return $messageId;

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp message: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send a template message (for outside service window)
     */
    public function sendTemplate(
        string $phoneNumber,
        string $templateName,
        string $language = 'en',
        array $variables = [],
        Message $message = null
    ): ?string {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $language
                    ]
                ]
            ];

            // Add variables if present
            if (!empty($variables)) {
                $payload['template']['components'][0]['type'] = 'body';
                $payload['template']['components'][0]['parameters'] = array_map(function ($val) {
                    return ['type' => 'text', 'text' => $val];
                }, $variables);
            }

            $response = Http::withHeaders($this->settings->getApiHeaders())
                ->post($this->settings->getApiUrl('messages'), $payload);

            if (!$response->successful()) {
                throw new \Exception('WhatsApp API error: ' . $response->body());
            }

            $messageId = $response->json('messages.0.id');

            if ($message) {
                WhatsAppMessageStatus::create([
                    'message_id' => $message->id,
                    'platform_message_id' => $messageId,
                    'status' => 'sent'
                ]);
            }

            Log::info('WhatsApp template sent', [
                'phone' => $phoneNumber,
                'template' => $templateName,
                'platform_message_id' => $messageId
            ]);

            return $messageId;

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp template: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send an image message
     */
    public function sendImage(string $phoneNumber, string $mediaUrl, ?string $caption = null, Message $message = null): ?string
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'image',
                'image' => [
                    'link' => $mediaUrl
                ]
            ];

            if ($caption) {
                $payload['image']['caption'] = $caption;
            }

            $response = Http::withHeaders($this->settings->getApiHeaders())
                ->post($this->settings->getApiUrl('messages'), $payload);

            if (!$response->successful()) {
                throw new \Exception('WhatsApp API error: ' . $response->body());
            }

            $messageId = $response->json('messages.0.id');

            if ($message) {
                WhatsAppMessageStatus::create([
                    'message_id' => $message->id,
                    'platform_message_id' => $messageId,
                    'status' => 'sent'
                ]);
            }

            return $messageId;

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp image: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send a file message
     */
    public function sendFile(string $phoneNumber, string $mediaUrl, string $fileName, Message $message = null): ?string
    {
        try {
            $mimeType = $this->getMimeType($fileName);

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'document',
                'document' => [
                    'link' => $mediaUrl,
                    'filename' => $fileName
                ]
            ];

            if ($mimeType) {
                $payload['document']['mime_type'] = $mimeType;
            }

            $response = Http::withHeaders($this->settings->getApiHeaders())
                ->post($this->settings->getApiUrl('messages'), $payload);

            if (!$response->successful()) {
                throw new \Exception('WhatsApp API error: ' . $response->body());
            }

            $messageId = $response->json('messages.0.id');

            if ($message) {
                WhatsAppMessageStatus::create([
                    'message_id' => $message->id,
                    'platform_message_id' => $messageId,
                    'status' => 'sent'
                ]);
            }

            return $messageId;

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp file: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead(string $messageId): bool
    {
        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId
            ];

            $response = Http::withHeaders($this->settings->getApiHeaders())
                ->post($this->settings->getApiUrl('messages'), $payload);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Error marking WhatsApp message as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get media URL from WhatsApp
     */
    public function getMediaUrl(string $mediaId): ?string
    {
        try {
            $response = Http::withHeaders($this->settings->getApiHeaders())
                ->get($this->settings->getApiUrl($mediaId));

            if (!$response->successful()) {
                return null;
            }

            return $response->json('url');

        } catch (\Exception $e) {
            Log::error('Error getting WhatsApp media URL: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download media from WhatsApp
     */
    public function downloadMedia(string $mediaUrl, string $savePath): bool
    {
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $this->settings->access_token])
                ->get($mediaUrl);

            if (!$response->successful()) {
                return false;
            }

            file_put_contents($savePath, $response->body());
            return true;

        } catch (\Exception $e) {
            Log::error('Error downloading WhatsApp media: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get MIME type from file extension
     */
    private function getMimeType(string $fileName): ?string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
        ];

        return $mimeTypes[$ext] ?? null;
    }

    /**
     * Verify webhook token
     */
    public function verifyWebhookToken(string $token): bool
    {
        return hash_equals($token, $this->settings->verify_token);
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhookSignature(string $signature, string $payload): bool
    {
        $hash = hash_hmac('sha256', $payload, $this->settings->verify_token);
        return hash_equals('sha256=' . $hash, $signature);
    }
}
