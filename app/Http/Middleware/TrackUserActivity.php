<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Events\UserStatusChanged;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            $cacheKey = "user_online_{$userId}";
            $onlineUsersKey = "online_users";
            
            // Check if user was previously offline
            $wasOnline = Cache::has($cacheKey);
            
            // Mark user as online for 5 minutes
            Cache::put($cacheKey, now(), now()->addMinutes(5));
            
            // Update global online users list
            $onlineUsers = Cache::get($onlineUsersKey, []);
            $onlineUsers[$userId] = now()->toISOString();
            
            // Clean up offline users (those not active for more than 5 minutes)
            $cutoff = now()->subMinutes(5);
            $onlineUsers = array_filter($onlineUsers, function($lastSeen) use ($cutoff) {
                return \Carbon\Carbon::parse($lastSeen)->isAfter($cutoff);
            });
            
            Cache::put($onlineUsersKey, $onlineUsers, now()->addMinutes(10));
            
            // Broadcast status change if user just came online
            if (!$wasOnline) {
                broadcast(new UserStatusChanged($user, 'online'));
            }
        }

        return $next($request);
    }
}
