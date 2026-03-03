<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\WhatsAppCustomer;
use App\Models\WhatsAppConversation;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\CannedReply;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppFollowUp;
use App\Services\WhatsAppService;
use App\Events\MessageSent;
use Carbon\Carbon;

class WhatsAppController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Get team inbox (all conversations)
     */
    public function inbox(Request $request)
    {
        try {
            $query = WhatsAppConversation::with([
                'customer',
                'conversation.lastMessage.user',
                'conversation.participants'
            ])
            ->where('is_open', true)
            ->orderBy('last_message_at', 'desc');

            // Filters
            if ($request->filled('stage')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('stage', $request->stage);
                });
            }

            if ($request->filled('assigned_to')) {
                $query->whereHas('customer', function ($q) use ($request) {
                    $q->where('assigned_to_user_id', $request->assigned_to);
                });
            }

            if ($request->has('unassigned') && $request->unassigned) {
                $query->whereHas('customer', function ($q) {
                    $q->whereNull('assigned_to_user_id');
                });
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('phone', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            }

            $conversations = $query
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching inbox: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load inbox'
            ], 500);
        }
    }

    /**
     * Get customer details
     */
    public function getCustomer(WhatsAppCustomer $customer)
    {
        try {
            $customer->load([
                'conversations.conversation.messages',
                'followers' => function ($q) {
                    $q->orderBy('scheduled_at', 'asc');
                },
                'assignedTo'
            ]);

            return response()->json([
                'success' => true,
                'data' => ['customer' => $customer]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting customer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load customer'
            ], 500);
        }
    }

    /**
     * Get conversation messages
     */
    public function getMessages(WhatsAppCustomer $customer)
    {
        try {
            $whatsappConv = $customer->conversations()->first();

            if (!$whatsappConv) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }

            $messages = $whatsappConv->conversation
                ->messages()
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messages,
                    'service_window_open' => $whatsappConv->isServiceWindowOpen()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages'
            ], 500);
        }
    }

    /**
     * Send reply to customer
     */
    public function sendReply(Request $request, WhatsAppCustomer $customer)
    {
        $request->validate([
            'content' => 'required|string|max:4096',
            'type' => 'sometimes|in:text,image,file'
        ]);

        try {
            $user = Auth::user();

            return DB::transaction(function () use ($request, $customer, $user) {
                $whatsappConv = $customer->conversations()->first();

                if (!$whatsappConv) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversation not found'
                    ], 404);
                }

                $conversation = $whatsappConv->conversation;

                // Check if service window is open
                $canSendFreeForm = $whatsappConv->canReplyFreeForm();

                if (!$canSendFreeForm) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Service window closed. Use template message instead.',
                        'service_window_open' => false
                    ], 422);
                }

                // Create message in app
                $message = $conversation->messages()->create([
                    'user_id' => $user->id,
                    'sent_by_user_id' => $user->id,
                    'content' => $request->input('content'),
                    'type' => $request->type ?? 'text',
                    'platform' => 'whatsapp',
                    'direction' => 'out'
                ]);

                // Send via WhatsApp
                $messageId = $this->whatsappService->sendMessage(
                    $customer->phone,
                    $request->input('content'),
                    $message
                );

                $message->load('user');

                // Broadcast
                broadcast(new MessageSent($message, $conversation))->toOthers();

                return response()->json([
                    'success' => true,
                    'data' => ['message' => $message]
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Error sending reply: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reply'
            ], 500);
        }
    }

    /**
     * Send template message (for outside service window)
     */
    public function sendTemplate(Request $request, WhatsAppCustomer $customer)
    {
        $request->validate([
            'template_id' => 'required|exists:whatsapp_message_templates,id',
            'variables' => 'sometimes|array'
        ]);

        try {
            $user = Auth::user();
            $template = WhatsAppMessageTemplate::findOrFail($request->template_id);

            return DB::transaction(function () use ($request, $customer, $user, $template) {
                $whatsappConv = $customer->conversations()->first();

                if (!$whatsappConv) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversation not found'
                    ], 404);
                }

                if ($template->status !== 'approved') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Template not approved'
                    ], 422);
                }

                $conversation = $whatsappConv->conversation;

                // Render template with variables
                $content = $template->renderWithVariables($request->variables ?? []);

                // Create message in app
                $message = $conversation->messages()->create([
                    'user_id' => $user->id,
                    'sent_by_user_id' => $user->id,
                    'content' => $content,
                    'type' => 'text',
                    'platform' => 'whatsapp',
                    'direction' => 'out',
                    'metadata' => json_encode([
                        'template_id' => $template->id,
                        'template_name' => $template->template_name
                    ])
                ]);

                // Send via WhatsApp
                $this->whatsappService->sendTemplate(
                    $customer->phone,
                    $template->template_name,
                    $template->language,
                    $request->variables ?? [],
                    $message
                );

                $message->load('user');
                broadcast(new MessageSent($message, $conversation))->toOthers();

                return response()->json([
                    'success' => true,
                    'data' => ['message' => $message]
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Error sending template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send template'
            ], 500);
        }
    }

    /**
     * Use canned reply
     */
    public function useCannedReply(Request $request, WhatsAppCustomer $customer)
    {
        $request->validate([
            'shortcut' => 'required|exists:canned_replies,shortcut'
        ]);

        try {
            $user = Auth::user();
            $cannedReply = CannedReply::where('shortcut', $request->shortcut)->firstOrFail();

            return DB::transaction(function () use ($customer, $user, $cannedReply) {
                $whatsappConv = $customer->conversations()->first();

                if (!$whatsappConv) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversation not found'
                    ], 404);
                }

                if (!$whatsappConv->canReplyFreeForm()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Service window closed.'
                    ], 422);
                }

                $conversation = $whatsappConv->conversation;

                // Create message
                $message = $conversation->messages()->create([
                    'user_id' => $user->id,
                    'sent_by_user_id' => $user->id,
                    'content' => $cannedReply->body,
                    'type' => 'text',
                    'platform' => 'whatsapp',
                    'direction' => 'out'
                ]);

                // Send via WhatsApp
                $this->whatsappService->sendMessage($customer->phone, $cannedReply->body, $message);

                $message->load('user');
                broadcast(new MessageSent($message, $conversation))->toOthers();

                return response()->json([
                    'success' => true,
                    'data' => ['message' => $message]
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Error using canned reply: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reply'
            ], 500);
        }
    }

    /**
     * Assign customer to user
     */
    public function assign(Request $request, WhatsAppCustomer $customer)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $customer->update([
                'assigned_to_user_id' => $request->user_id
            ]);

            return response()->json([
                'success' => true,
                'data' => ['customer' => $customer]
            ]);

        } catch (\Exception $e) {
            Log::error('Error assigning customer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign customer'
            ], 500);
        }
    }

    /**
     * Update customer stage and notes
     */
    public function update(Request $request, WhatsAppCustomer $customer)
    {
        $request->validate([
            'stage' => 'sometimes|in:new,thinking,quoted,made_up_mind,won,come_back,lost',
            'notes' => 'sometimes|string|nullable',
            'tags' => 'sometimes|array',
            'next_follow_up_at' => 'sometimes|date|nullable'
        ]);

        try {
            $customer->update($request->only([
                'stage',
                'notes',
                'tags',
                'next_follow_up_at'
            ]));

            return response()->json([
                'success' => true,
                'data' => ['customer' => $customer]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating customer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer'
            ], 500);
        }
    }

    /**
     * Schedule a follow-up
     */
    public function scheduleFollowUp(Request $request, WhatsAppCustomer $customer)
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'reason' => 'required|string',
            'notes' => 'sometimes|string|nullable',
            'assign_to_user_id' => 'required|exists:users,id'
        ]);

        try {
            $followUp = $customer->followUps()->create([
                'scheduled_at' => $request->scheduled_at,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'assigned_to_user_id' => $request->assign_to_user_id,
                'status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'data' => ['follow_up' => $followUp]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error scheduling follow-up: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule follow-up'
            ], 500);
        }
    }

    /**
     * Get follow-ups for dashboard
     */
    public function getFollowUps(Request $request)
    {
        try {
            $query = WhatsAppFollowUp::with(['customer', 'assignedTo']);

            // Get follow-ups for current user
            if ($request->has('my_follow_ups') && $request->my_follow_ups) {
                $query->where('assigned_to_user_id', Auth::id());
            }

            // Get overdue only
            if ($request->has('overdue') && $request->overdue) {
                $query->overdue();
            }

            // Get today's only
            if ($request->has('today') && $request->today) {
                $query->dueToday();
            }

            // Get pending only
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $followUps = $query->orderBy('scheduled_at', 'asc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $followUps
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching follow-ups: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load follow-ups'
            ], 500);
        }
    }

    /**
     * Complete a follow-up
     */
    public function completeFollowUp(WhatsAppFollowUp $followUp)
    {
        try {
            $followUp->markCompleted(Auth::id());

            return response()->json([
                'success' => true,
                'data' => ['follow_up' => $followUp]
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing follow-up: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete follow-up'
            ], 500);
        }
    }

    /**
     * Get canned replies
     */
    public function getCannedReplies(Request $request)
    {
        try {
            $replies = CannedReply::active()
                ->when($request->filled('category'), function ($q) use ($request) {
                    $q->byCategory($request->category);
                })
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->search($request->search);
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => ['canned_replies' => $replies]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching canned replies: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load canned replies'
            ], 500);
        }
    }

    /**
     * Get approved templates
     */
    public function getTemplates(Request $request)
    {
        try {
            $templates = WhatsAppMessageTemplate::approved()
                ->when($request->filled('category'), function ($q) use ($request) {
                    $q->byCategory($request->category);
                })
                ->when($request->filled('language'), function ($q) use ($request) {
                    $q->byLanguage($request->language);
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => ['templates' => $templates]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching templates: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load templates'
            ], 500);
        }
    }
}
