<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat channels
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is participant in this conversation
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Public channel for user status
Broadcast::channel('user-status', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
