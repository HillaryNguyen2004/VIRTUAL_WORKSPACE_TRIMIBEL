<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;




class MeetingController extends Controller
{

    public function createMeeting(Request $request) {
        
        $METERED_DOMAIN = env('METERED_DOMAIN');
        $METERED_SECRET_KEY = env('METERED_SECRET_KEY');
    

        // Contain the logic to create a new meeting
        $response = Http::post("https://{$METERED_DOMAIN}/api/v1/room?secretKey={$METERED_SECRET_KEY}", [
            'autoJoin' => true
        ]);

        $roomName = $response->json("roomName");
        
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
        // This page might use a different layout, or no layout at all
        return view('video-chat.meeting_view', [
            'MEETING_ID' => $meetingId,
            'METERED_DOMAIN' => env('METERED_DOMAIN')
        ]);
    }

    public function index()
    {
        // detailed dummy data to match your specific Blade view
        $meetingHistory = collect([
            (object) [
                'id' => 1,
                'start_time' => now()->subHours(2), // Matches $meeting->start_time
                'notes' => 'Discussed Q4 marketing strategy and budget allocation.', // Matches $meeting->notes
                'attendees_count' => 5, // Matches $meeting->attendees_count
                'attendees' => collect([ // Matches $meeting->attendees
                    (object) ['name' => 'Alice Johnson', 'avatar_url' => null],
                    (object) ['name' => 'Bob Smith', 'avatar_url' => null],
                    (object) ['name' => 'Charlie Davis', 'avatar_url' => null],
                    (object) ['name' => 'Dana Lee', 'avatar_url' => null],
                    (object) ['name' => 'Evan Wright', 'avatar_url' => null],
                ]),
            ],
            (object) [
                'id' => 2,
                'start_time' => now()->subDays(1)->subHours(4),
                'notes' => null, // Test empty notes
                'attendees_count' => 2,
                'attendees' => collect([
                    (object) ['name' => 'You', 'avatar_url' => null],
                    (object) ['name' => 'Sarah Connor', 'avatar_url' => null],
                ]),
            ],
            (object) [
                'id' => 3,
                'start_time' => now()->subDays(3),
                'notes' => 'Client requested changes to the homepage layout.',
                'attendees_count' => 8,
                'attendees' => collect([
                    (object) ['name' => 'Mike Ross', 'avatar_url' => null],
                    (object) ['name' => 'Rachel Zane', 'avatar_url' => null],
                    (object) ['name' => 'Harvey Specter', 'avatar_url' => null],
                    (object) ['name' => 'Louis Litt', 'avatar_url' => null],
                ]),
            ],
            (object) [
                'id' => 4,
                'start_time' => now()->subWeek(),
                'notes' => 'Weekly team sync.',
                'attendees_count' => 12,
                'attendees' => collect([
                    (object) ['name' => 'Team Lead', 'avatar_url' => null],
                    (object) ['name' => 'Developer', 'avatar_url' => null],
                    (object) ['name' => 'Designer', 'avatar_url' => null],
                ]),
            ],
        ]);

        return view('video-chat.index', compact('meetingHistory'));
    }

    public function history()
    {
        dd('I am here!');
        // Create dummy data using a Collection to mimic a Database result
        $meetingHistory = collect([
            (object) [
                'id' => 1,
                'topic' => 'Project Kickoff',
                'host' => 'Dr. Smith',
                'start_time' => now()->subDays(1)->format('M d, Y H:i'), // "Nov 25, 2025 08:30"
                'status' => 'Completed',
            ],
            (object) [
                'id' => 2,
                'topic' => 'Weekly Team Sync',
                'host' => 'You',
                'start_time' => now()->subDays(3)->format('M d, Y H:i'),
                'status' => 'Cancelled',
            ],
            (object) [
                'id' => 3,
                'topic' => 'Client Review',
                'host' => 'Jane Doe',
                'start_time' => now()->subHours(5)->format('M d, Y H:i'),
                'status' => 'Completed',
            ],
        ]);

        return view('meetings.history', compact('meetingHistory'));
    }
}