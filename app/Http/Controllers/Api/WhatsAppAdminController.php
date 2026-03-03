<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\CannedReply;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSettings;

class WhatsAppAdminController extends Controller
{
    /**
     * Get WhatsApp settings
     */
    public function getSettings()
    {
        try {
            $settings = WhatsAppSettings::active();

            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp not configured'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'business_name' => $settings->business_name,
                    'phone_number' => $settings->phone_number,
                    'waba_id' => $settings->waba_id,
                    'webhook_url' => $settings->webhook_url,
                    'is_active' => $settings->is_active
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get settings'
            ], 500);
        }
    }

    /**
     * Update WhatsApp settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'business_name' => 'sometimes|string',
            'waba_id' => 'sometimes|string',
            'phone_number_id' => 'sometimes|string',
            'phone_number' => 'sometimes|string',
            'access_token' => 'sometimes|string',
            'verify_token' => 'sometimes|string',
            'webhook_url' => 'sometimes|url|nullable'
        ]);

        try {
            $settings = WhatsAppSettings::active();

            if (!$settings) {
                $settings = WhatsAppSettings::create($request->all());
            } else {
                $settings->update($request->all());
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => ['settings' => $settings]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings'
            ], 500);
        }
    }

    /**
     * List canned replies
     */
    public function listCannedReplies(Request $request)
    {
        try {
            $replies = CannedReply::with('creator')
                ->when($request->filled('category'), function ($q) use ($request) {
                    $q->byCategory($request->category);
                })
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->search($request->search);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $replies
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing canned replies: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to list canned replies'
            ], 500);
        }
    }

    /**
     * Create canned reply
     */
    public function createCannedReply(Request $request)
    {
        $request->validate([
            'shortcut' => 'required|string|unique:canned_replies,shortcut|max:50',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $reply = CannedReply::create([
                'shortcut' => $request->shortcut,
                'title' => $request->title,
                'body' => $request->body,
                'category' => $request->category,
                'created_by_user_id' => Auth::id(),
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Canned reply created',
                'data' => ['reply' => $reply]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating canned reply: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create canned reply'
            ], 500);
        }
    }

    /**
     * Update canned reply
     */
    public function updateCannedReply(Request $request, CannedReply $reply)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'category' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $reply->update($request->only(['title', 'body', 'category', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => ['reply' => $reply]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating canned reply: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update canned reply'
            ], 500);
        }
    }

    /**
     * Delete canned reply
     */
    public function deleteCannedReply(CannedReply $reply)
    {
        try {
            $reply->delete();

            return response()->json([
                'success' => true,
                'message' => 'Canned reply deleted'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting canned reply: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete canned reply'
            ], 500);
        }
    }

    /**
     * List message templates
     */
    public function listTemplates(Request $request)
    {
        try {
            $templates = WhatsAppMessageTemplate::with('creator')
                ->when($request->filled('status'), function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->when($request->filled('category'), function ($q) use ($request) {
                    $q->byCategory($request->category);
                })
                ->when($request->filled('language'), function ($q) use ($request) {
                    $q->byLanguage($request->language);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing templates: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to list templates'
            ], 500);
        }
    }

    /**
     * Create message template
     */
    public function createTemplate(Request $request)
    {
        $request->validate([
            'template_name' => 'required|string|max:512',
            'language' => 'required|string|size:2',
            'category' => 'required|in:MARKETING,AUTHENTICATION,UTILITY,SERVICE_UPDATE',
            'body' => 'required|string',
            'variables' => 'sometimes|array',
            'example' => 'sometimes|string'
        ]);

        try {
            $template = WhatsAppMessageTemplate::create([
                'template_name' => $request->template_name,
                'language' => $request->language,
                'category' => $request->category,
                'body' => $request->body,
                'variables' => $request->variables,
                'example' => $request->example,
                'created_by_user_id' => Auth::id(),
                'status' => 'pending' // Templates must be approved by Meta
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template created (pending Meta approval)',
                'data' => ['template' => $template]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template'
            ], 500);
        }
    }

    /**
     * Update template
     */
    public function updateTemplate(Request $request, WhatsAppMessageTemplate $template)
    {
        $request->validate([
            'body' => 'sometimes|string',
            'example' => 'sometimes|string|nullable',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $template->update($request->only(['body', 'example', 'is_active']));

            return response()->json([
                'success' => true,
                'data' => ['template' => $template]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update template'
            ], 500);
        }
    }

    /**
     * Delete template
     */
    public function deleteTemplate(WhatsAppMessageTemplate $template)
    {
        try {
            if ($template->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete approved templates'
                ], 422);
            }

            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template deleted'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete template'
            ], 500);
        }
    }

    /**
     * Sync template approval status with Meta (if you implement this)
     */
    public function syncTemplateStatus(WhatsAppMessageTemplate $template)
    {
        try {
            // This would call Meta's API to check template approval status
            // For now, it's a placeholder
            
            return response()->json([
                'success' => true,
                'message' => 'Template status synced',
                'data' => ['template' => $template]
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing template status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync status'
            ], 500);
        }
    }
}
