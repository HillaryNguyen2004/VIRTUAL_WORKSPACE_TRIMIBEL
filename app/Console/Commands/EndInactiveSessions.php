<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EndInactiveSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'end:inactive:sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End inactive user sessions based on heartbeat timeout';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sessions = \DB::table('user_sessions')
            ->whereNull('logout_at')
            ->where('last_activity_at', '<', now()->subMinutes(1))
            ->get();

        foreach ($sessions as $session) {
            $duration = now()->diffInSeconds($session->login_at);

            \DB::table('user_sessions')
                ->where('id', $session->id)
                ->update([
                    'logout_at' => $session->last_activity_at,
                    'duration_seconds' => $duration,
                ]);

            \App\Models\ActivityEvent::create([
                'user_id' => $session->user_id,
                'event_type' => 'session_end',
                'module' => 'system',
                'metadata' => [
                    'reason' => 'heartbeat_timeout',
                    'duration_seconds' => $duration,
                ],
                'occurred_at' => now(),
            ]);
        }

        $this->info('Ended ' . count($sessions) . ' inactive sessions.');

        return Command::SUCCESS;
    }
}
