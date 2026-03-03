<?php

namespace App\Services;

use App\Models\User;
use App\Models\Task;
use App\Models\CalendarEvent;
use App\Repositories\CalendarRepository;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleServiceCalendar;
use Illuminate\Support\Facades\Log;

use GuzzleHttp\Client as GuzzleClient;

class CalendarService
{
    protected CalendarRepository $repository;

    public function __construct(CalendarRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getCombinedEvents(User $user)
    {
        $systemEvents = $this->getSystemTasksFormatted($user);
        $customEvents = $this->getCustomEventsFormatted($user);
        $googleEvents = $user->is_google_connected ? $this->getGoogleEvents($user) : [];

        return array_merge($systemEvents, $customEvents, $googleEvents);
    }

    /**
     * Source A: Tasks (Read-only on calendar)
     */
    protected function getSystemTasksFormatted(User $user)
    {
        $tasks = Task::where('assigned_user_id', $user->id)->get(); 

        return $tasks->map(function ($task) {
            return [
                'id' => 'local_' . $task->id,
                'title' => 'Task: ' . $task->title,
                'start' => $task->due_date,
                'allDay' => true, // Force tasks to be All Day events
                'category' => 'tasks',
                // USE TAILWIND CLASSES DIRECTLY:
                // Secondary Blue (#4896FE) with Darker Border (#2680F6)
                'classNames' => [
                    'bg-secondary', 
                    'border-l-[3px]', 
                    'border-secondary-hover', 
                    'text-white',
                    'shadow-sm'
                ],
                'extendedProps' => [
                    'type' => 'task',
                    'event_id' => $task->id
                ]
            ];
        })->toArray();
    }

    /**
     * Source B: Custom Events (Editable)
     */
    protected function getCustomEventsFormatted(User $user)
    {
        // 1. Get Normal Events (Non-recurring)
        $normalEvents = CalendarEvent::where('user_id', $user->id)
            ->where('recurrence_type', 'none')
            ->get();

        // 2. Get Recurring Events
        $recurringEvents = CalendarEvent::where('user_id', $user->id)
            ->where('recurrence_type', '!=', 'none')
            ->get();

        $formattedEvents = [];

        // Process Normal Events
        foreach ($normalEvents as $event) {
            $formattedEvents[] = $this->formatEvent($event);
        }

        // Process Recurring Events (Expand them)
        // We need the view start/end from the Request usually. 
        // Assuming we fetch slightly more than needed (e.g., +/- 1 year or current view).
        // For now, let's assume we generate for the next 2 years max to be safe if no range provided.
        
        $viewStart = Carbon::now()->subMonths(1); 
        $viewEnd = Carbon::now()->addMonths(6); 
        // Ideally, pass $start/$end from controller to this function for perfect efficiency.

        foreach ($recurringEvents as $rule) {
            $formattedEvents = array_merge($formattedEvents, $this->expandRecurrence($rule, $viewStart, $viewEnd));
        }

        return $formattedEvents;
    }

    private function formatEvent($event, $overrideStart = null, $overrideEnd = null)
    {
        $start = $overrideStart ? $overrideStart : $event->start_date;
        $end = $overrideEnd ? $overrideEnd : $event->end_date;

        return [
            'id' => 'custom_' . $event->id, // Keep same ID for all instances so editing one edits the rule
            'title' => $event->title,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'category' => $event->category,
            'allDay' => ($event->start_date->format('H:i') === '00:00' && $event->end_date && $event->end_date->format('H:i') === '23:59'),
            'classNames' => $this->getTailwindClassesForCategory($event->category),
            'extendedProps' => [
                'type' => 'custom',
                'meeting_id' => $event->meeting_id,
                'recurrence_type' => $event->recurrence_type,
                'recurrence_interval' => $event->recurrence_interval,
                'recurrence_end_date' => $event->recurrence_end_date ? $event->recurrence_end_date->format('Y-m-d') : null,
            ]
        ];
    }

    // Logic to generate instances
    private function expandRecurrence($rule, $viewStart, $viewEnd)
    {
        $instances = [];
        $current = $rule->start_date->copy();
        
        // Stop if we go past the rule's own end date (if set)
        $hardStop = $rule->recurrence_end_date ? $rule->recurrence_end_date->endOfDay() : $viewEnd;
        
        // Safety break
        $loops = 0;

        while ($current->lte($hardStop) && $loops < 365) { // Max 365 instances per fetch for safety
            
            // Only add if it falls within our current viewing window
            if ($current->gte($viewStart)) {
                // Calculate end time based on original duration
                $duration = $rule->end_date->diffInMinutes($rule->start_date);
                $instanceEnd = $current->copy()->addMinutes($duration);

                $instances[] = $this->formatEvent($rule, $current, $instanceEnd);
            }

            // Advance Date
            switch ($rule->recurrence_type) {
                case 'daily':   $current->addDays($rule->recurrence_interval); break;
                case 'weekly':  $current->addWeeks($rule->recurrence_interval); break;
                case 'monthly': $current->addMonths($rule->recurrence_interval); break;
                case 'yearly':  $current->addYears($rule->recurrence_interval); break;
                default:        $loops = 9999; break; // Break loop
            }
            $loops++;
        }

        return $instances;
    }

    private function getTailwindClassesForCategory($category)
    {
        $base = ['border-l-[3px]', 'text-white'];

        switch ($category) {
            case 'other':
                return array_merge($base, ['bg-secondary-light', 'border-secondary']);
            
            case 'meeting':
                return array_merge($base, ['bg-accent', 'border-accent']);

            case 'tasks':
            default:
                return array_merge($base, ['bg-primary-light', 'border-primary']);
        }
    }

    /**
     * Fetch Google Calendar Events
     */
    protected function getGoogleEvents(User $user)
    {
        if (!$user->google_access_token) return [];

        try {
            $client = new GoogleClient();

            // Fixes the "hang" on localhost by using a custom Guzzle client
            $httpClient = new GuzzleClient(['verify' => false]);
            $client->setHttpClient($httpClient);

            $client->setAccessToken($user->google_access_token);
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));

            // 1. REFRESH TOKEN LOGIC (Prevents disconnection after 1 hour)
            if ($client->isAccessTokenExpired()) {
                if ($user->google_refresh_token) {
                    $newAccessToken = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                    
                    if (!isset($newAccessToken['error'])) {
                        $user->update(['google_access_token' => $newAccessToken['access_token']]);
                        $client->setAccessToken($newAccessToken['access_token']);
                    } else {
                        throw new \Exception("Refresh token failed");
                    }
                } else {
                    $user->update(['is_google_connected' => false]); // Force reconnect
                    return [];
                }
            }

            // 2. Fetch Events
            $service = new GoogleServiceCalendar($client);
            $optParams = [
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => Carbon::now()->subMonths(1)->toRfc3339String(),
                'timeMax' => Carbon::now()->addMonths(3)->toRfc3339String(),
            ];

            $results = $service->events->listEvents('primary', $optParams);

            // 3. Format for FullCalendar
            return array_map(function ($event) {
                $start = $event->start->dateTime ?? $event->start->date;
                $end = $event->end->dateTime ?? $event->end->date;
                $isAllDay = empty($event->start->dateTime);

                return [
                    'id' => 'google_' . $event->id,
                    'title' => '📅 ' . $event->getSummary(),
                    'start' => $start,
                    'end' => $end,
                    'allDay' => $isAllDay,
                    'url' => $event->htmlLink,
                    'category' => 'google', // For JS filtering
                    'classNames' => [
                        'bg-success-light',       // <--- CHANGE THIS (was bg-white)
                        'shadow-sm',
                        'font-medium'
                    ],
                    'editable' => false // Prevent editing Google events locally
                ];
            }, $results->getItems());

        } catch (\Exception $e) {
            Log::error('Google Fetch Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Business Logic: Create a new calendar event
     */
    public function createEvent(User $user, array $data): CalendarEvent
    {
        try {
            $recEndDate = !empty($data['recurrence_end_date']) ? $data['recurrence_end_date'] : null;

            return $this->repository->createEvent([
                'user_id' => $user->id,
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'category' => $data['category'] ?? 'tasks',
                'meeting_id' => $data['meeting_id'] ?? null,
                'recurrence_type' => $data['recurrence_type'] ?? 'none',
                'recurrence_interval' => $data['recurrence_interval'] ?? 1,
                'recurrence_end_date' => $recEndDate,
            ]);
        } catch (\Exception $e) {
            Log::error('Create Event Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Business Logic: Update calendar event details
     */
    public function updateEvent(User $user, $eventId, array $data): bool
    {
        try {
            // Parse event ID (format: "custom_5" or "local_5")
            ['type' => $type, 'id' => $id] = $this->parseEventId($eventId);

            if ($type === 'custom') {
                $recEndDate = !empty($data['recurrence_end_date']) && $data['recurrence_end_date'] !== 'null' 
                    ? $data['recurrence_end_date'] 
                    : null;

                return $this->repository->updateEvent($id, $user->id, [
                    'title' => $data['title'],
                    'category' => $data['category'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'] ?? null,
                    'meeting_id' => $data['meeting_id'] ?? null,
                    'recurrence_type' => $data['recurrence_type'] ?? 'none',
                    'recurrence_interval' => $data['recurrence_interval'] ?? 1,
                    'recurrence_end_date' => $recEndDate,
                ]);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Update Event Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Business Logic: Update event date (drag-drop)
     */
    public function updateEventDate(User $user, $eventId, array $data): bool
    {
        try {
            ['type' => $type, 'id' => $id] = $this->parseEventId($eventId);

            if ($type === 'custom') {
                return $this->repository->updateEvent($id, $user->id, [
                    'start_date' => Carbon::parse($data['start'])->format('Y-m-d H:i:s'),
                    'end_date' => $data['end'] ? Carbon::parse($data['end'])->format('Y-m-d H:i:s') : null,
                ]);
            } elseif ($type === 'local') {
                // Handle Task date update
                $task = Task::where('id', $id)->firstOrFail();
                $task->update([
                    'due_date' => Carbon::parse($data['start'])->format('Y-m-d H:i:s')
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Update Event Date Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Business Logic: Delete event
     */
    public function deleteEvent(User $user, $eventId): bool
    {
        try {
            ['type' => $type, 'id' => $id] = $this->parseEventId($eventId);

            if ($type === 'custom') {
                return $this->repository->deleteEvent($id, $user->id);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Delete Event Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper: Parse event ID format "custom_5" or "local_5"
     */
    private function parseEventId(string $eventId): array
    {
        $parts = explode('_', $eventId);
        
        if (count($parts) < 2) {
            throw new \InvalidArgumentException('Invalid event ID format');
        }

        return [
            'type' => $parts[0], // 'custom' or 'local'
            'id' => $parts[1],   // The actual ID
        ];
    }
}