<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\CalendarService;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class CalendarController extends Controller
{
    protected CalendarService $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function index()
    {
        $user = auth()->user();

        return view('calendar.index');
    }

    /**
     * 2. Fetch Events for FullCalendar (AJAX)
     */
    public function getEvents(Request $request)
    {
        $user = auth()->user();
        
        // Use the service to get merged events
        $events = $this->calendarService->getCombinedEvents($user);

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'category' => 'nullable|string',
            'meeting_id' => 'nullable|string',
            'recurrence_type' => 'nullable|string',
            'recurrence_interval' => 'nullable|integer',
            'recurrence_end_date' => 'nullable|date',
        ]);

        $recEndDate = $request->recurrence_end_date;
        if (empty($recEndDate)) {
            $recEndDate = null;
        }

        $event = \App\Models\CalendarEvent::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'category' => $request->category ?? 'tasks',
            'meeting_id' => $request->meeting_id,
            'recurrence_type' => $request->recurrence_type ?? 'none',
            'recurrence_interval' => $request->recurrence_interval ?? 1,
            'recurrence_end_date' => $recEndDate,
        ]);

        return response()->json([
            'status' => 'success',
            'event' => $event
        ]);
    }

    /**
     * 3. Store a New Event (Create)
     */
    public function updateDate(Request $request)
    {
        // Debug Log: Check storage/logs/laravel.log if this fails
        Log::info('Calendar Drag Update:', $request->all());

        $request->validate([
            'id' => 'required|string',
            'start' => 'required', 
            'end' => 'nullable', 
        ]);

        $parts = explode('_', $request->id);
        if(count($parts) < 2) return response()->json(['status' => 'error', 'message' => 'Invalid ID'], 400);

        $type = $parts[0]; // 'custom' or 'local'
        $id = $parts[1];

        try {
            if ($type === 'custom') {
                $event = CalendarEvent::where('user_id', auth()->id())->where('id', $id)->firstOrFail();
                
                $event->update([
                    'start_date' => \Carbon\Carbon::parse($request->start)->format('Y-m-d H:i:s'),
                    'end_date' => $request->end ? \Carbon\Carbon::parse($request->end)->format('Y-m-d H:i:s') : null,
                ]);

                return response()->json(['status' => 'success']);
            } 
            
            // Optional: Handle Tasks
            elseif ($type === 'local') {
                $task = Task::where('id', $id)->first();
                if ($task) {
                    $task->update([
                        'due_date' => \Carbon\Carbon::parse($request->start)->format('Y-m-d H:i:s')
                    ]);
                    return response()->json(['status' => 'success']);
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
        
        return response()->json(['status' => 'error', 'message' => 'Event not found'], 404);
    }

    /**
     * Update details (Title, Category, Time) from the Modal
     */
    public function updateDetails(Request $request)
    {
        $request->validate([
            'id' => 'required|string', // "custom_5"
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'meeting_id' => 'nullable|string',
            'recurrence_type' => 'nullable|string',
            'recurrence_interval' => 'nullable|integer',
            'recurrence_end_date' => 'nullable|date',
        ]);

        $parts = explode('_', $request->id);
        $id = $parts[1];

        $event = \App\Models\CalendarEvent::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $recEndDate = $request->recurrence_end_date;
        if (empty($recEndDate) || $recEndDate === 'null') {
            $recEndDate = null;
        }

        $event->update([
            'title' => $request->title,
            'category' => $request->category,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'meeting_id' => $request->meeting_id,
            'recurrence_type' => $request->recurrence_type ?? 'none', 
            'recurrence_interval' => $request->recurrence_interval ?? 1, // Default to 1
            'recurrence_end_date' => $recEndDate,
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Delete an event
     */
    public function destroy(Request $request)
    {
        $request->validate(['id' => 'required|string']);

        $parts = explode('_', $request->id);
        $id = $parts[1];

        $event = \App\Models\CalendarEvent::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $event->delete();

        return response()->json(['status' => 'success']);
    }

    /**
     * START GOOGLE LOGIN
     */
    public function connectGoogle()
    {
        $user = auth()->user();

        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/calendar.readonly'])
            ->with([
                'access_type' => 'offline', // Critical for refreshing tokens later
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    /**
     * HANDLE GOOGLE CALLBACK
     */
    public function googleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = auth()->user();
            

            // Strict Security: Ensure the Google email matches the User email
            // if ($googleUser->email !== $user->email) {
            //     return redirect()->route('calendar')
            //         ->with('error', 'The Google Account email must match your registered email.');
            // }

            // Save tokens
            $user->update([
                'google_email' => $googleUser->email,
                'google_access_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken,
                'is_google_connected' => true,
            ]);

            return redirect()->route('calendar')->with('success', 'Google Calendar synced!');

        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect()->route('calendar')->with('error', 'Failed to connect Google Calendar');
        }
    }
}