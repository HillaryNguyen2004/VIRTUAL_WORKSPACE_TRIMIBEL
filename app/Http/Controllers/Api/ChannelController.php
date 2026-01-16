<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChannelRule;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;

class ChannelController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $channels = Channel::with('rules')
            ->withCount('members')
            ->get()
            ->map(function($ch) use ($userId) {
                $ch->is_member = $ch->members()->where('user_id', $userId)->exists();
                // hide members list in index; frontend can fetch details from show
                return $ch;
            });

        return response()->json($channels);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_private' => 'boolean',
            'allow_messages' => 'nullable',
        ]);

        $data['created_by'] = Auth::id();

        // normalize allow_messages to boolean (default true)
        $rawAllow = $request->input('allow_messages', null);
        if (is_null($rawAllow)) {
            $data['allow_messages'] = true;
        } else {
            // accept '0','1',0,1,false,true etc.
            $data['allow_messages'] = filter_var($rawAllow, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (is_null($data['allow_messages'])) $data['allow_messages'] = true;
        }

        // Only admins can create announcement-only channels (allow_messages === false)
        if ($data['allow_messages'] === false) {
            if (!Auth::user() || !method_exists(Auth::user(), 'hasRole') || !Auth::user()->hasRole('admin')) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $channel = Channel::create($data);

        // Ensure creator is a member of the channel so they can post and manage it
        try {
            if ($channel->created_by) {
                $channel->members()->syncWithoutDetaching([$channel->created_by]);
            }
        } catch (\Exception $e) {
            // non-fatal
        }

        // create a backing conversation for channel messages
        try {
            $conversation = Conversation::create([
                // conversations.type enum accepts 'direct' or 'group' — use 'group' for channels
                'type' => 'group',
                'name' => $channel->name,
                'created_by' => $channel->created_by,
            ]);
            $channel->conversation_id = $conversation->id;
            $channel->save();

            // attach existing members as participants
            foreach ($channel->members()->pluck('user_id') as $uid) {
                $conversation->participants()->attach($uid, ['joined_at' => now()]);
            }
        } catch (\Exception $e) {
            // non-fatal if conversation not created
        }

        return response()->json($channel, 201);
    }

    public function show(Channel $channel)
    {
        $channel->load(['rules', 'members']);
        $channel->is_member = $channel->members->contains('id', Auth::id());
        return response()->json($channel);
    }

    public function join(Channel $channel)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        if ($channel->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Already a member', 'channel_id' => $channel->id]);
        }

        $channel->members()->attach($user->id);

        // If channel has a backing conversation, add the user as a participant there too
        try {
            if ($channel->conversation_id) {
                $conversation = Conversation::find($channel->conversation_id);
                if ($conversation) {
                    $conversation->participants()->syncWithoutDetaching([$user->id => ['joined_at' => now()]]);
                }
            }
        } catch (\Exception $e) {
            // non-fatal
        }

        return response()->json(['message' => 'Joined channel', 'channel_id' => $channel->id]);
    }

    public function leave(Channel $channel)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $channel->members()->detach($user->id);

        // Also detach from backing conversation if present
        try {
            if ($channel->conversation_id) {
                $conversation = Conversation::find($channel->conversation_id);
                if ($conversation) {
                    $conversation->participants()->detach($user->id);
                }
            }
        } catch (\Exception $e) {
            // non-fatal
        }

        return response()->json(['message' => 'Left channel', 'channel_id' => $channel->id]);
    }

    public function rules(Channel $channel)
    {
        return response()->json($channel->rules()->get());
    }

    public function addRule(Request $request, Channel $channel)
    {
        // only admin can add rules/announcements
        $user = Auth::user();
        if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $data['created_by'] = $user->id;
        $data['channel_id'] = $channel->id;

        $rule = ChannelRule::create($data);

        return response()->json($rule, 201);
    }

    public function postMessage(Request $request, Channel $channel)
    {
        $request->validate(['content' => 'required|string|max:2000']);

        $user = Auth::user();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);

        // permission: if channel disallows messages (falsy), only admins may post
        if (!$channel->allow_messages) {
            if (!method_exists($user, 'hasRole') || !$user->hasRole('admin')) {
                return response()->json(['success' => false, 'message' => 'Người dùng không thể nhắn vào channel này'], 403);
            }
        }

        $isCreator = $channel->created_by == $user->id;
        $isMember = $channel->members()->where('user_id', $user->id)->exists();
        if (!$isCreator && !$isMember) {
            // admins are allowed regardless of membership when channel is admin-only
            if (!(method_exists($user, 'hasRole') && $user->hasRole('admin'))) {
                return response()->json(['success' => false, 'message' => 'Not a member'], 403);
            }
        }

        try {
            // ensure conversation exists
                if (!$channel->conversation_id) {
                $conversation = Conversation::create(['type' => 'group', 'name' => $channel->name, 'created_by' => $channel->created_by]);
                $channel->conversation_id = $conversation->id;
                $channel->save();

                $memberIds = $channel->members()->pluck('user_id')->toArray();
                if (!empty($memberIds)) {
                    $syncData = [];
                    foreach ($memberIds as $mid) $syncData[$mid] = ['joined_at' => now()];
                    $conversation->participants()->syncWithoutDetaching($syncData);
                }
            } else {
                $conversation = Conversation::find($channel->conversation_id);
            }

            if (!$conversation) return response()->json(['success' => false, 'message' => 'Conversation not available'], 500);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'content' => $request->input('content'),
                'type' => 'text',
            ]);
            $message->load('user');
            $conversation->touch();

            // broadcast
            try { broadcast(new MessageSent($message, $conversation))->toOthers(); } catch (\Exception $e) {}

            return response()->json(['success' => true, 'data' => ['message' => $message]], 201);
        } catch (\Exception $e) {
            \Log::error('Channel postMessage error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }
}
