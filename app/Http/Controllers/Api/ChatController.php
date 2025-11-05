<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Events\UserStatusChanged;
use App\Services\ChatFileService;
use Carbon\Carbon;

class ChatController extends Controller
{
    protected $chatFileService;

    public function __construct(ChatFileService $chatFileService)
    {
        $this->chatFileService = $chatFileService;
    }
    /**
     * Get all conversations for the authenticated user
     */
    public function getConversations(Request $request)
    {
        try {
            $user = Auth::user();
            
            $conversations = $user->conversations()
                ->with([
                    'lastMessage.user',
                    'participants' => function($query) use ($user) {
                        $query->where('user_id', '!=', $user->id);
                    }
                ])
                ->withCount([
                    'messages as unread_count' => function($query) use ($user) {
                        $query->whereDoesntHave('readBy', function($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                    }
                ])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function($conversation) use ($user) {
                    // Set display name
                    if ($conversation->type === 'direct') {
                        // For direct chats, get the other user (not current user)
                        $allParticipants = $conversation->participants()->get();
                        $otherUser = $allParticipants->where('id', '!=', $user->id)->first();
                        $conversation->display_name = $otherUser ? $otherUser->name : 'Unknown User';
                    } else {
                        $conversation->display_name = $conversation->name ?? 'Group Chat';
                    }
                    
                    return $conversation;
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'conversations' => $conversations,
                    'total' => $conversations->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting conversations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversations'
            ], 500);
        }
    }

    /**
     * Create a new conversation
     */
    public function createConversation(Request $request)
    {
        $request->validate([
            'type' => 'required|in:direct,group',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'name' => 'nullable|string|max:255'
        ]);

        try {
            $user = Auth::user();
            $participantIds = array_unique(array_merge([$user->id], $request->participant_ids));

            // For direct conversations, check if one already exists
            if ($request->type === 'direct' && count($participantIds) === 2) {
                // Find existing direct conversation between exactly these two users
                $existing = Conversation::where('type', 'direct')
                    ->whereHas('participants', function($query) use ($participantIds) {
                        $query->where('user_id', $participantIds[0]);
                    })
                    ->whereHas('participants', function($query) use ($participantIds) {
                        $query->where('user_id', $participantIds[1]);
                    })
                    ->has('participants', '=', 2) // Ensure exactly 2 participants
                    ->first();

                if ($existing) {
                    // Set display name properly
                    $otherUser = $existing->participants->where('id', '!=', $user->id)->first();
                    $existing->display_name = $otherUser ? $otherUser->name : 'Unknown User';
                    
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'conversation' => $existing->load('participants', 'lastMessage.user'),
                            'existing' => true,
                        ]
                    ]);
                }
            }

            $conversation = DB::transaction(function() use ($request, $user, $participantIds) {
                $conversation = Conversation::create([
                    'type' => $request->type,
                    'name' => $request->name,
                    'created_by' => $user->id
                ]);

                // Add participants
                foreach ($participantIds as $participantId) {
                    $conversation->participants()->attach($participantId, [
                        'joined_at' => now()
                    ]);
                }

                return $conversation;
            });

            $conversation->load('participants', 'lastMessage.user');

            // Set display name for the response
            if ($conversation->type === 'direct') {
                $otherUser = $conversation->participants->where('id', '!=', $user->id)->first();
                $conversation->display_name = $otherUser ? $otherUser->name : 'Unknown User';
            } else {
                $conversation->display_name = $conversation->name ?? 'Group Chat';
            }

            // Broadcast to all participants
            foreach ($participantIds as $participantId) {
                if ($participantId !== $user->id) {
                    broadcast(new \App\Events\ConversationCreated($conversation, $participantId));
                }
            }

            return response()->json([
                'success' => true,
                'data' => ['conversation' => $conversation]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating conversation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create conversation'
            ], 500);
        }
    }

    /**
     * Get a specific conversation with messages
     */
    public function getConversation(Request $request, Conversation $conversation)
    {
        try {
            $user = Auth::user();

            // Check if user is participant
            if (!$conversation->participants->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $conversation->load([
                'participants',
                'messages' => function($query) {
                    $query->with('user')->orderBy('created_at', 'desc')->limit(50);
                }
            ]);

            // Set display name properly
            if ($conversation->type === 'direct') {
                // For direct chats, get the other user (not current user)
                $otherUser = $conversation->participants->where('id', '!=', $user->id)->first();
                $conversation->display_name = $otherUser ? $otherUser->name : 'Unknown User';
            } else {
                $conversation->display_name = $conversation->name ?? 'Group Chat';
            }

            // Mark as read
            $this->markConversationAsRead($conversation, $user);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => $conversation,
                    'messages' => $conversation->messages->reverse()->values()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting conversation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversation'
            ], 500);
        }
    }

    /**
     * Get messages for a conversation
     */
    public function getMessages(Request $request, Conversation $conversation)
    {
        try {
            $user = Auth::user();

            if (!$conversation->participants->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $messages = $conversation->messages()
                ->with('user')
                ->when($request->before, function($query, $before) {
                    $query->where('created_at', '<', $before);
                })
                ->orderBy('created_at', 'desc')
                ->limit($request->limit ?? 50)
                ->get()
                ->reverse()
                ->values();

            return response()->json([
                'success' => true,
                'data' => ['messages' => $messages]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting messages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages'
            ], 500);
        }
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'type' => 'sometimes|in:text,image,file,video_call'
        ]);

        try {
            $user = Auth::user();

            if (!$conversation->participants->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'content' => $request->input('content'),
                'type' => $request->type ?? 'text'
            ]);

            $message->load('user');

            // Update conversation timestamp
            $conversation->touch();

            // Broadcast the message
            broadcast(new MessageSent($message, $conversation))->toOthers();

            return response()->json([
                'success' => true,
                'data' => ['message' => $message]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    /**
     * Send a file message
     */
    public function sendFile(Request $request, Conversation $conversation)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'content' => 'nullable|string|max:500' // Optional message with file
        ]);

        try {
            $user = Auth::user();

            if (!$conversation->participants->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Upload file
            $fileData = $this->chatFileService->uploadFile($request->file('file'), $conversation->id);

            // Create message with file
            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'content' => $request->input('content', 'File: ' . $fileData['file_name']),
                'type' => 'file',
                'file_name' => $fileData['file_name'],
                'file_path' => $fileData['file_path'],
                'file_size' => $fileData['file_size'],
                'file_type' => $fileData['file_type']
            ]);

            $message->load('user');

            // Update conversation timestamp
            $conversation->touch();

            // Broadcast the message
            broadcast(new MessageSent($message, $conversation))->toOthers();

            return response()->json([
                'success' => true,
                'data' => ['message' => $message]
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error sending file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send file'
            ], 500);
        }
    }

    /**
     * Send an image message
     */
    public function sendImage(Request $request, Conversation $conversation)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            'content' => 'nullable|string|max:500' // Optional caption
        ]);

        try {
            $user = Auth::user();

            if (!$conversation->participants->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Upload image
            $imageData = $this->chatFileService->uploadImage($request->file('image'), $conversation->id);

            // Create message with image
            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'content' => $request->input('content', 'Image: ' . $imageData['file_name']),
                'type' => 'image',
                'file_name' => $imageData['file_name'],
                'file_path' => $imageData['file_path'],
                'file_size' => $imageData['file_size'],
                'file_type' => $imageData['file_type']
            ]);

            $message->load('user');

            // Update conversation timestamp
            $conversation->touch();

            // Broadcast the message
            broadcast(new MessageSent($message, $conversation))->toOthers();

            return response()->json([
                'success' => true,
                'data' => ['message' => $message]
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error sending image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send image'
            ], 500);
        }
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(Request $request, Conversation $conversation)
    {
        try {
            $user = Auth::user();

            if (!$conversation->participants->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $this->markConversationAsRead($conversation, $user);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error marking as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as read'
            ], 500);
        }
    }

    /**
     * Set typing status
     */
    public function setTyping(Request $request, Conversation $conversation)
    {
        $request->validate([
            'typing' => 'required|boolean'
        ]);

        try {
            $user = Auth::user();

            if (!$conversation->participants->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Broadcast typing status
            broadcast(new UserTyping($user, $conversation, $request->typing))->toOthers();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error setting typing status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to set typing status'
            ], 500);
        }
    }

    /**
     * Search users
     */
    public function searchUsers(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'query' => 'required|string|min:1|max:100'
            ]);

            $searchQuery = $validated['query'];
            
            // Log the search query for debugging
            Log::info('Searching users with query: ' . $searchQuery);
            
            // Search users by name
            $users = User::where('name', 'like', '%' . $searchQuery . '%')
                ->where('id', '!=', Auth::id())
                ->select(['id', 'name', 'email'])
                ->limit(10)
                ->get();
                
            Log::info('Found users count: ' . $users->count());

            return response()->json([
                'success' => true,
                'data' => ['users' => $users]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error searching users: ' . $e->getMessage(), [
                'query' => $request->input('query'),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to search users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get online users
     */
    public function getOnlineUsers(Request $request)
    {
        try {
            // Get users who have been active in the last 5 minutes
            $onlineUsers = Cache::get('online_users', []);

            $users = User::whereIn('id', array_keys($onlineUsers))
                ->where('id', '!=', Auth::id())
                ->select(['id', 'name'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'count' => $users->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting online users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get online users'
            ], 500);
        }
    }

    /**
     * Create a video call
     */
    public function createVideoCall(Request $request, Conversation $conversation)
    {
        try {
            $user = Auth::user();

            if (!$conversation->participants->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $METERED_DOMAIN = env('METERED_DOMAIN');
            $METERED_SECRET_KEY = env('METERED_SECRET_KEY');

            // Create meeting room
            $response = Http::post("https://{$METERED_DOMAIN}/api/v1/room?secretKey={$METERED_SECRET_KEY}", [
                'autoJoin' => false
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to create video meeting room');
            }

            $roomName = $response->json("roomName");

            // Send video call message
            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'content' => "Video call started",
                'type' => 'video_call',
                'metadata' => json_encode([
                    'meeting_id' => $roomName,
                    'meeting_url' => "/meeting/{$roomName}",
                    'started_by' => $user->name,
                    'started_at' => now()->toISOString()
                ])
            ]);

            $message->load('user');

            // Update conversation timestamp
            $conversation->touch();

            // Broadcast the video call message
            broadcast(new MessageSent($message, $conversation))->toOthers();

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $message,
                    'meeting_id' => $roomName,
                    'meeting_url' => "/meeting/{$roomName}"
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating video call: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create video call'
            ], 500);
        }
    }

    /**
     * Join a video call
     */
    public function joinVideoCall(Request $request, Conversation $conversation)
    {
        $request->validate([
            'meeting_id' => 'required|string'
        ]);

        try {
            $user = Auth::user();

            if (!$conversation->participants->contains($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $METERED_DOMAIN = env('METERED_DOMAIN');
            $METERED_SECRET_KEY = env('METERED_SECRET_KEY');
            $meetingId = $request->meeting_id;

            // Validate meeting exists
            $response = Http::get("https://{$METERED_DOMAIN}/api/v1/room/{$meetingId}?secretKey={$METERED_SECRET_KEY}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid meeting ID'
                ], 404);
            }

            // Send join notification message
            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'content' => "{$user->name} joined the video call",
                'type' => 'system',
                'metadata' => json_encode([
                    'meeting_id' => $meetingId,
                    'action' => 'user_joined',
                    'user_name' => $user->name
                ])
            ]);

            $message->load('user');

            // Broadcast the join message
            broadcast(new MessageSent($message, $conversation))->toOthers();

            return response()->json([
                'success' => true,
                'data' => [
                    'meeting_url' => "/meeting/{$meetingId}"
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error joining video call: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to join video call'
            ], 500);
        }
    }

    /**
     * Helper method to mark conversation as read
     */
    private function markConversationAsRead(Conversation $conversation, User $user)
    {
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now()
        ]);

        // Mark all messages as read
        $unreadMessages = $conversation->messages()
            ->whereDoesntHave('readBy', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($unreadMessages as $message) {
            $message->readBy()->attach($user->id, ['read_at' => now()]);
        }
    }
}
