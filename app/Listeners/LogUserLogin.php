<?php

namespace App\Listeners;

use App\Models\ActivityEvent;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class LogUserLogin
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
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        $sessionId = session()->getId();

        DB::table('user_sessions')->insert([
            'user_id' => $event->user->id,
            'session_id' => $sessionId,
            'login_at' => now(),
            'last_activity_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ActivityEvent::create([
            'user_id' => $event->user->id,
            'event_type' => 'login',
            'module' => 'auth',
            'metadata' => [
                'session_id' => $sessionId,
            ],
            'occurred_at' => now(),
        ]);
    }
}
