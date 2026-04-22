<?php

namespace App\Http\Controllers;

use App\Models\MeetingAttendee;
use App\Models\MeetingHistory;
use App\Events\MeetingChatMessage;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;



// NOT REFACTORED YET - WE WILL CLEAN THIS UP IN THE FUTURE
class MeetingController extends Controller
{

    private function meteredBaseUrl(): string
    {
        $domain = env('METERED_DOMAIN');
        return "https://{$domain}/api/v1";
    }

    private function meteredSecretKey(): ?string
    {
        return env('METERED_SECRET_KEY');
    }

    private function meteredRequest(string $method, string $path, array $query = [])
    {
        $secretKey = $this->meteredSecretKey();
        if (!$secretKey) {
            return null;
        }

        $query = array_merge($query, ['secretKey' => $secretKey]);
        $url = rtrim($this->meteredBaseUrl(), '/') . '/' . ltrim($path, '/');

        if (strtolower($method) === 'get') {
            return Http::get($url, $query);
        }

        if (strtolower($method) === 'post') {
            return Http::post($url . '?' . http_build_query($query), []);
        }

        return null;
    }

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
        
        $METERED_DOMAIN = config('services.metered.domain');
        $METERED_SECRET_KEY = config('services.metered.secret_key');
    

        // Contain the logic to create a new meeting
        $response = Http::post("https://{$METERED_DOMAIN}/api/v1/room?secretKey={$METERED_SECRET_KEY}", [
            'autoJoin' => true
        ]);

        $roomName = $response->json("roomName");

        $this->ensureMeetingHistory($roomName);
        
        return redirect("/meeting/{$roomName}"); // We will update this soon.
    }

    public function validateMeeting(Request $request) {
        $METERED_DOMAIN = config('services.metered.domain');
        $METERED_SECRET_KEY = config('services.metered.secret_key');

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

    // API: Generate a Metered Room for scheduled meetings
    public function generateRoomApi(Request $request)
    {
        $METERED_DOMAIN = config('services.metered.domain');
        $METERED_SECRET_KEY = config('services.metered.secret_key');

        $response = Http::post("https://{$METERED_DOMAIN}/api/v1/room?secretKey={$METERED_SECRET_KEY}", [
            'autoJoin' => true
        ]);

        if ($response->successful()) {
            $roomName = $response->json("roomName");
            
            // Log it in history right away so the user "owns" it
            $this->ensureMeetingHistory($roomName);

            return response()->json([
                'success' => true,
                'roomName' => $roomName
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to create room via Metered API'], 500);
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
            'METERED_DOMAIN' => env('METERED_DOMAIN'),
            'meetingAttendees' => MeetingAttendee::where('meeting_id', $meetingId)
                ->orderByDesc('joined_at')
                ->get(),
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

    public function debugRoomsApi()
    {
        $response = $this->meteredRequest('get', 'rooms');

        if (!$response) {
            return response()->json(['success' => false, 'message' => 'Missing METERED_SECRET_KEY'], 400);
        }

        return response()->json([
            'success' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json(),
        ], $response->status());
    }

    public function syncFromMetered()
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $response = $this->meteredRequest('get', 'rooms');
        if (!$response) {
            return response()->json(['success' => false, 'message' => 'Missing METERED_SECRET_KEY'], 400);
        }

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rooms',
                'status' => $response->status(),
                'data' => $response->json(),
            ], $response->status());
        }

        $rooms = $response->json('rooms') ?? $response->json('data') ?? $response->json();
        if (!is_array($rooms)) {
            $rooms = [];
        }

        $synced = [];

        foreach ($rooms as $room) {
            $roomName = $room['roomName'] ?? $room['room_name'] ?? $room['name'] ?? null;
            if (!$roomName) {
                continue;
            }

            $createdAt = $room['created'] ?? $room['createdAt'] ?? $room['created_at'] ?? null;
            $startTime = $createdAt ? Carbon::parse($createdAt) : now();

            $history = MeetingHistory::firstOrCreate(
                [
                    'user_id' => $userId,
                    'meeting_id' => $roomName,
                ],
                [
                    'start_time' => $startTime,
                ]
            );

            $sessionsResponse = $this->meteredRequest('get', "room/{$roomName}/sessions");
            if ($sessionsResponse && $sessionsResponse->successful()) {
                $sessions = $sessionsResponse->json('sessions') ?? $sessionsResponse->json('data') ?? $sessionsResponse->json();
                if (is_array($sessions) && count($sessions) > 0) {
                    $latestSession = collect($sessions)->sortByDesc(function ($session) {
                        return $session['start_time'] ?? $session['startedAt'] ?? $session['created'] ?? null;
                    })->first();

                    $sessionStart = $latestSession['start_time'] ?? $latestSession['startedAt'] ?? $latestSession['created'] ?? null;
                    $sessionEnd = $latestSession['end_time'] ?? $latestSession['endedAt'] ?? $latestSession['ended'] ?? null;

                    if ($sessionStart) {
                        $history->start_time = Carbon::parse($sessionStart);
                    }

                    if ($sessionEnd) {
                        $history->end_time = Carbon::parse($sessionEnd);
                    }

                    $history->save();
                }
            }

            $synced[] = [
                'room_name' => $roomName,
                'start_time' => $history->start_time,
                'is_local' => false,
            ];
        }

        return response()->json([
            'success' => true,
            'count' => count($synced),
            'meetings' => $synced,
        ]);
    }

    public function meteredRoomDetails($roomName)
    {
        $roomResponse = $this->meteredRequest('get', "room/{$roomName}");
        $sessionsResponse = $this->meteredRequest('get', "room/{$roomName}/sessions");

        return response()->json([
            'room' => $roomResponse ? $roomResponse->json() : null,
            'sessions' => $sessionsResponse ? $sessionsResponse->json() : null,
        ]);
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

    public function sendChatMessage(Request $request, $meetingId)
    {
        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $history = $this->ensureMeetingHistory($meetingId);
        if (!$history) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        $payload = [
            'meeting_id' => $meetingId,
            'message' => $data['message'],
            'name' => $user->name ?? $user->email ?? 'User',
            'user_id' => $user->id,
            'avatar_url' => $user->avatar_url ?? null,
            'sent_at' => now()->toISOString(),
        ];

        broadcast(new MeetingChatMessage($meetingId, $payload))->toOthers();

        return response()->json(['success' => true, 'data' => $payload]);
    }

    // API to Find Slots
    public function findSmartSlots(Request $request, CalendarService $calendarService)
    {
        $request->validate([
            'attendees' => 'required|array',
            'duration' => 'required|integer',
        ]);

        // Ensure the current user is included in the check
        $userIds = array_merge([auth()->id()], $request->attendees);

        $slots = $calendarService->findAvailableSlots($userIds, $request->duration, 7);

        return response()->json(['status' => 'success', 'slots' => $slots]);
    }

    // API to Book the Meeting for multiple people
    public function bookSmartMeeting(Request $request)
    {
        $request->validate([
            'title'      => 'required|string',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
            'attendees'  => 'required|array',
            'meeting_id' => 'required|string'
        ]);

        // Cast everything to int to avoid type mismatches
        $attendeeIds = array_map('intval', $request->attendees);
        $userIds = array_unique(array_merge([(int) auth()->id()], $attendeeIds));

        try {
            foreach ($userIds as $userId) {
                \App\Models\CalendarEvent::create([
                    'user_id'        => $userId,
                    'title'          => $request->title,
                    'start_date'     => $request->start_date,
                    'end_date'       => $request->end_date,
                    'category'       => 'meeting',
                    'meeting_id'     => $request->meeting_id,
                    'recurrence_type'=> 'none'
                ]);
            }

            $this->ensureMeetingHistory($request->meeting_id);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('bookSmartMeeting failed: ' . $e->getMessage());

            // Always return JSON, never let Laravel return an HTML error page
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}