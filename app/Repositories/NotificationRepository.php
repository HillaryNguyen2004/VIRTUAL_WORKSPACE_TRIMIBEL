<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Auth;

class NotificationRepository
{
    public function findById($id)
    {
        return Auth::user()->notifications()->where('id', $id)->first();
    }

    public function markAsRead($notification)
    {
        $notification->markAsRead();
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }
}
