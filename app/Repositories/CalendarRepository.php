<?php

namespace App\Repositories;

use App\Models\CalendarEvent;
use App\Models\Task;
use Illuminate\Support\Collection;

class CalendarRepository
{
    /**
     * Get calendar event by ID and user ID
     */
    public function getEventById(int $id, int $userId): ?CalendarEvent
    {
        return CalendarEvent::where('user_id', $userId)
            ->where('id', $id)
            ->first();
    }

    /**
     * Get all events for a user
     */
    public function getEventsByUser(int $userId): Collection
    {
        return CalendarEvent::where('user_id', $userId)
            ->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Get task by ID
     */
    public function getTaskById(int $id): ?Task
    {
        return Task::where('id', $id)->first();
    }

    /**
     * Create a calendar event
     */
    public function createEvent(int $userId, array $data): CalendarEvent
    {
        return CalendarEvent::create(array_merge($data, ['user_id' => $userId]));
    }

    /**
     * Update calendar event
     */
    public function updateEvent(CalendarEvent $event, array $data): bool
    {
        return $event->update($data);
    }

    /**
     * Delete calendar event
     */
    public function deleteEvent(CalendarEvent $event): bool
    {
        return $event->delete();
    }

    /**
     * Update task due date
     */
    public function updateTaskDueDate(Task $task, string $dueDate): bool
    {
        return $task->update(['due_date' => $dueDate]);
    }
}
