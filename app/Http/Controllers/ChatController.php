<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $request->validate([
            'type' => 'required|in:direct,group',
            'name' => 'required_if:type,group|string|max:255',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id'
        ]);

        // For direct messages, check if conversation already exists between these users
        if ($request->type === 'direct' && count($request->participants) === 1) {
            $otherUserId = $request->participants[0];
            
            // Find existing direct conversation between current user and selected user
            $existingConversation = Conversation::where('type', 'direct')
                ->whereHas('participants', function($query) {
                    $query->where('user_id', auth()->id());
                })
                ->whereHas('participants', function($query) use ($otherUserId) {
                    $query->where('user_id', $otherUserId);
                })
                ->whereDoesntHave('participants', function($query) use ($otherUserId) {
                    $query->whereNotIn('user_id', [auth()->id(), $otherUserId]);
                })
                ->first();

            if ($existingConversation) {
                return redirect()->route('chat.conversation', $existingConversation);
            }
        }

        // Create new conversation
        $conversation = Conversation::create([
            'name' => $request->name,
            'type' => $request->type,
            'created_by' => auth()->id()
        ]);

        // Add current user and selected participants
        $participants = array_unique(array_merge($request->participants, [auth()->id()]));
        $conversation->participants()->attach($participants);

        return redirect()->route('chat.conversation', $conversation);
    }
}
