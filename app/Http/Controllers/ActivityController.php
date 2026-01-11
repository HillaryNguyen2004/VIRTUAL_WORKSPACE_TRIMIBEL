<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityEvent;
use Carbon\Carbon;

class ActivityController extends Controller
{
    public function heartbeat(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $sessionId = session()->getId();

        DB::table('user_sessions')
            ->where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->update([
                'last_activity_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['status' => 'ok']);
    }

    public function trackTabClose(Request $request)
    {
        // This endpoint accepts POST requests without authentication
        // since it's called during page unload when session might be destroyed
        
        $userId = $request->input('user_id');
        $sessionId = $request->input('session_id');
        
        if (!$userId || !$sessionId) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }

        // Find the active session
        $session = DB::table('user_sessions')
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->whereNull('logout_at')
            ->first();

        if ($session) {
            // Calculate duration from login_at to now
            $duration = Carbon::now()->diffInSeconds($session->login_at);
            
            DB::table('user_sessions')
                ->where('id', $session->id)
                ->update([
                    'logout_at' => Carbon::now(),
                    'duration_seconds' => $duration,
                    'updated_at' => Carbon::now(),
                ]);
            
            // Log activity event
            ActivityEvent::create([
                'user_id' => $userId,
                'role' => DB::table('users')->where('id', $userId)->value('role'),
                'event_type' => 'logout',
                'module' => 'auth',
                'entity_id' => null,
                'metadata' => json_encode(['logout_type' => 'tab_close']),
                'occurred_at' => Carbon::now(),
            ]);
        }

        return response()->json(['status' => 'logged_out']);
    }

    public function tabClose(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $sessionId = $request->input('session_id', session()->getId());

        // Update user_sessions with logout_at for tab close
        $session = DB::table('user_sessions')
            ->where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->whereNull('logout_at')
            ->first();

        if ($session) {
            $duration = now()->diffInSeconds($session->login_at);
            DB::table('user_sessions')
                ->where('id', $session->id)
                ->update([
                    'logout_at' => now(),
                    'duration_seconds' => $duration,
                    'updated_at' => now(),
                ]);

            // Log activity event
            ActivityEvent::create([
                'user_id' => $user->id,
                'role' => $user->role,
                'event_type' => 'logout',
                'module' => 'auth',
                'entity_id' => null,
                'metadata' => json_encode(['logout_type' => 'tab_close']),
                'occurred_at' => now(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}