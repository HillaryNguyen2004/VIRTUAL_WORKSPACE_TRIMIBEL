<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class ChatController extends Controller
{
    public function index()
    {
        // Only get conversations where the current user is a participant
        $conversations = auth()->user()->conversations()
            ->with(['lastMessage.user', 'participants'])
            ->orderBy('updated_at', 'desc')
            ->get();
            
        // Get all other users except current user for creating new conversations
        $users = User::where('id', '!=', auth()->id())->get();
        
        return view('chat.index', compact('conversations', 'users'));
    }

    public function show(Conversation $conversation)
    {
        // Check if current user is a participant in this conversation
        if (!$conversation->participants->contains(auth()->id())) {
            abort(403, 'You do not have access to this conversation.');
        }

        // Get all messages for this conversation
        $messages = $conversation->messages()->with('user')->orderBy('created_at', 'asc')->get();
        
        // Mark conversation as read for current user
        $conversation->participants()->updateExistingPivot(auth()->id(), [
            'last_read_at' => now()
        ]);
        
        return view('chat.conversation', compact('conversation', 'messages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|max:1000'
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // Verify user is participant in this conversation
        if (!$conversation->participants->contains(auth()->id())) {
            abort(403, 'You cannot send messages to this conversation.');
        }

        // Create the message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'content' => $request->input('content')
        ]);

        // Update conversation timestamp
        $conversation->touch();

        return response()->json([
            'success' => true,
            'message' => $message->load('user')
        ]);
    }

    public function createConversation(Request $request)
    {
        $validationRules = [
            'type' => 'required|in:direct,group',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id'
        ];

        // Only validate name if type is group
        if ($request->type === 'group') {
            $validationRules['name'] = 'required|string|max:255';
        }

        $request->validate($validationRules);

        // For direct messages, ensure exactly one participant is selected
        if ($request->type === 'direct') {
            if (count($request->participants) !== 1) {
                return back()->with('error', 'Please select exactly one person for direct message.');
            }

            $otherUserId = $request->participants[0];
            
            // Check if user is trying to create conversation with themselves
            if ($otherUserId == auth()->id()) {
                return back()->with('error', 'You cannot create a conversation with yourself.');
            }
            
            // Find existing direct conversation between current user and selected user
            $existingConversation = Conversation::where('type', 'direct')
                ->whereHas('participants', function($query) {
                    $query->where('user_id', auth()->id());
                }, '=', 1)
                ->whereHas('participants', function($query) use ($otherUserId) {
                    $query->where('user_id', $otherUserId);
                }, '=', 1)
                ->has('participants', '=', 2) // Ensure only 2 participants
                ->first();

            if ($existingConversation) {
                return redirect()->route('chat.conversation', $existingConversation)
                    ->with('info', 'Conversation already exists.');
            }
        }

        try {
            // Create new conversation
            $conversation = Conversation::create([
                'name' => $request->type === 'group' ? $request->name : null,
                'type' => $request->type,
                'created_by' => auth()->id()
            ]);

            // Add participants
            $participants = $request->participants;
            
            // Always include current user
            if (!in_array(auth()->id(), $participants)) {
                $participants[] = auth()->id();
            }

            // Add participants to the conversation
            foreach ($participants as $userId) {
                $conversation->participants()->attach($userId, [
                    'joined_at' => now(),
                    'last_read_at' => now()
                ]);
            }

            return redirect()->route('chat.conversation', $conversation)
                ->with('success', 'Conversation created successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error creating conversation: ' . $e->getMessage());
            return back()->with('error', 'Failed to create conversation. Please try again.');
        }
    }
}
