<?php

namespace App\Listeners;

use App\Models\ActivityEvent;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class LogUserLogout
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Logout  $event
     * @return void
     */
    public function handle(Logout $event)
    {
        $sessionId = session()->getId();
        $logoutType = 'manual'; // Default for button clicks

        // Check if this is coming from a tab close (would have logout_type in request)
        if (request()->has('logout_type')) {
            $logoutType = request()->input('logout_type');
        }

        // Update user_sessions with logout_at
        $session = DB::table('user_sessions')
            ->where('user_id', $event->user->id)
            ->where('session_id', $sessionId)
            ->whereNull('logout_at')
            ->first();

        if ($session) {
            // Calculate duration from login_at to now
            $duration = now()->diffInSeconds($session->login_at);
            
            DB::table('user_sessions')
                ->where('id', $session->id)
                ->update([
                    'logout_at' => now(),
                    'duration_seconds' => $duration,
                    'updated_at' => now(),
                ]);
        }

        ActivityEvent::create([
            'user_id' => $event->user->id,
            'role' => $event->user->role ?? null,
            'event_type' => 'logout',
            'module' => 'auth',
            'entity_id' => null,
            'metadata' => json_encode(['logout_type' => $logoutType]),
            'occurred_at' => now(),
        ]);
    }
}