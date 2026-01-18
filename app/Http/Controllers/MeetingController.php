<?php

namespace App\Http\Controllers;

use App\Models\MeetingAttendee;
use App\Models\MeetingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;




class MeetingController extends Controller
{

    private function ensureMeetingHistory(string $meetingId): ?MeetingHistory
    {
        $userId = auth()->id();

        if (!$userId) {
            return null;
        }

        return MeetingHistory::firstOrCreate(
            [
                'user_id' => $userId,
                'meeting_id' => $meetingId,
            ],
            [
                'start_time' => now(),
            ]
        );
    }

    private function getMeetingHistoryForUser()
    {
        $userId = auth()->id();

        if (!$userId) {
            return collect();
        }

        $meetingHistory = MeetingHistory::with('attendees')
            ->where('user_id', $userId)
            ->orderByDesc('start_time')
            ->get();

        $meetingHistory->each(function ($meeting) {
            $meeting->start_time = $meeting->start_time ?? $meeting->created_at;
            $meeting->attendees = $meeting->attendees ?? collect();
            $meeting->attendees_count = $meeting->attendees->count();
        });

        return $meetingHistory;
    }

    public function createMeeting(Request $request) {
        
        $METERED_DOMAIN = env('METERED_DOMAIN');
        $METERED_SECRET_KEY = env('METERED_SECRET_KEY');
    

        // Contain the logic to create a new meeting
        $response = Http::post("https://{$METERED_DOMAIN}/api/v1/room?secretKey={$METERED_SECRET_KEY}", [
            'autoJoin' => true
        ]);

        $roomName = $response->json("roomName");

        $this->ensureMeetingHistory($roomName);
        
        return redirect("/meeting/{$roomName}"); // We will update this soon.
    }

    public function validateMeeting(Request $request) {
        $METERED_DOMAIN = env('METERED_DOMAIN');
        $METERED_SECRET_KEY = env('METERED_SECRET_KEY');

        $meetingId = $request->input('meetingId');

        // Contains logic to validate existing meeting
        $response = Http::get("https://{$METERED_DOMAIN}/api/v1/room/{$meetingId}?secretKey={$METERED_SECRET_KEY}");

        $roomName = $response->json("roomName");


        if ($response->status() === 200)  {
            $this->ensureMeetingHistory($roomName);
            return redirect("/meeting/{$roomName}"); // We will update this soon
        } else {
            return redirect("/?error=Invalid Meeting ID");
        }
    }

    public function showLobby(Request $request, $meetingId)
    {
        return view('video-chat.meeting', [
            'MEETING_ID' => $meetingId,
            'METERED_DOMAIN' => env('METERED_DOMAIN')
        ]);
    }

    /**
     * Show the actual Meeting Room UI
     */
    public function showMeetingRoom(Request $request, $meetingId)
    {
        if (auth()->check()) {
            $history = $this->ensureMeetingHistory($meetingId);
            if ($history) {
                $user = auth()->user();
                $name = $user->name ?? $user->email ?? 'User';

                $attendee = MeetingAttendee::where('meeting_id', $meetingId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$attendee) {
                    $attendee = MeetingAttendee::create([
                        'meeting_id' => $meetingId,
                        'user_id' => $user->id,
                        'name' => $name,
                        'avatar_url' => $user->avatar_url ?? null,
                        'joined_at' => now(),
                    ]);
                }

                $shouldSave = false;
                if ($attendee->name !== $name) {
                    $attendee->name = $name;
                    $shouldSave = true;
                }
                if (!empty($user->avatar_url) && $attendee->avatar_url !== $user->avatar_url) {
                    $attendee->avatar_url = $user->avatar_url;
                    $shouldSave = true;
                }
                if ($shouldSave) {
                    $attendee->save();
                }
            }
        }

        // This page might use a different layout, or no layout at all
        return view('video-chat.meeting_view', [
            'MEETING_ID' => $meetingId,
            'METERED_DOMAIN' => env('METERED_DOMAIN')
        ]);
    }

    public function index()
    {
        $meetingHistory = $this->getMeetingHistoryForUser();

        return view('video-chat.index', compact('meetingHistory'));
    }

    public function history()
    {
        $meetingHistory = $this->getMeetingHistoryForUser();

        return view('meetings.history', compact('meetingHistory'));
    }

    public function details($meetingHistoryId)
    {
        $userId = auth()->id();

        $meeting = MeetingHistory::with('attendees')
            ->where('id', $meetingHistoryId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $meeting->start_time = $meeting->start_time ?? $meeting->created_at;
        $meeting->attendees = $meeting->attendees ?? collect();
        $meeting->attendees_count = $meeting->attendees->count();

        return view('meetings.details', compact('meeting'));
    }

    public function recordAttendance(Request $request)
    {
        $data = $request->validate([
            'meeting_id' => 'required|string',
        ]);

        $history = $this->ensureMeetingHistory($data['meeting_id']);
        if (!$history) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        $name = $user->name ?? $user->email ?? 'User';

        $attendee = MeetingAttendee::where('meeting_id', $data['meeting_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$attendee) {
            $attendee = MeetingAttendee::where('meeting_id', $data['meeting_id'])
                ->whereNull('user_id')
                ->where('name', $name)
                ->first();
        }

        if (!$attendee) {
            $attendee = MeetingAttendee::create([
                'meeting_id' => $data['meeting_id'],
                'user_id' => $user->id,
                'name' => $name,
                'avatar_url' => $user->avatar_url ?? null,
                'joined_at' => now(),
            ]);
        }

        $shouldSave = false;
        if ($attendee->user_id !== $user->id) {
            $attendee->user_id = $user->id;
            $shouldSave = true;
        }
        if ($attendee->name !== $name) {
            $attendee->name = $name;
            $shouldSave = true;
        }

        if (!empty($user->avatar_url) && $attendee->avatar_url !== $user->avatar_url) {
            $attendee->avatar_url = $user->avatar_url;
            $shouldSave = true;
        }

        if ($shouldSave) {
            $attendee->save();
        }

        return response()->json(['success' => true]);
    }

    public function recordLeave(Request $request)
    {
        $data = $request->validate([
            'meeting_id' => 'required|string',
        ]);

        $history = $this->ensureMeetingHistory($data['meeting_id']);
        if (!$history) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$history->end_time) {
            $history->end_time = now();
            $history->save();
        }

        return response()->json(['success' => true]);
    }
}