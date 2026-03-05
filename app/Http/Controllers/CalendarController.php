<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\CalendarService;
use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Http\Requests\UpdateCalendarDateRequest;
use App\Http\Requests\DeleteCalendarEventRequest;
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
        return view('calendar.index');
    }

    /**
     * Fetch Events for FullCalendar (AJAX)
     */
    public function getEvents(Request $request)
    {
        $user = auth()->user();
        $events = $this->calendarService->getCombinedEvents($user);

        return response()->json($events);
    }

    /**
     * Create a new calendar event
     */
    public function store(StoreCalendarEventRequest $request)
    {
        $user = auth()->user();

        try {
            $event = $this->calendarService->createEvent($user, $request->validated());

            return response()->json([
                'status' => 'success',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            Log::error('Store Event Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update event date (drag-drop)
     */
    public function updateDate(UpdateCalendarDateRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        try {
            $this->calendarService->updateEventDate($user, $validated['id'], $validated);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Update Date Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update event details (title, category, time, recurrence)
     */
    public function updateDetails(UpdateCalendarEventRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        try {
            $this->calendarService->updateEvent($user, $validated['id'], $validated);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Update Details Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an event
     */
    public function destroy(DeleteCalendarEventRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        try {
            $this->calendarService->deleteEvent($user, $validated['id']);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Delete Event Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start Google Calendar connection
     */
    public function connectGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/calendar.readonly'])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = auth()->user();

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