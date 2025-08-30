<?php

namespace App\Services;

use App\Repositories\NotificationRepository;

class NotificationService
{
    protected $notificationRepo;

    public function __construct(NotificationRepository $notificationRepo)
    {
        $this->notificationRepo = $notificationRepo;
    }

    public function markAsRead($id)
    {
        $notification = $this->notificationRepo->findById($id);

        if ($notification) {
            $this->notificationRepo->markAsRead($notification);
            return true;
        }

        return false;
    }

    public function markAllAsRead()
    {
        $this->notificationRepo->markAllAsRead();
    }
}
