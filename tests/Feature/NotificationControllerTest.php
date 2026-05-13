<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        $user->notifications()->create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'type'            => 'App\Notifications\TestNotification',
            'data'            => json_encode(['message' => 'Test']),
            'read_at'         => null,
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('notifications.readAll'));

        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful(),
            "Expected 2xx/3xx, got {$response->getStatusCode()}"
        );

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_mark_specific_notification_as_read_with_valid_id(): void
    {
        $user = User::factory()->create();

        $notifId = (string) \Illuminate\Support\Str::uuid();
        $user->notifications()->create([
            'id'              => $notifId,
            'type'            => 'App\Notifications\TestNotification',
            'data'            => json_encode(['message' => 'Hello']),
            'read_at'         => null,
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('notifications.read', ['id' => $notifId]));

        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful(),
            "Expected 2xx/3xx, got {$response->getStatusCode()}"
        );
    }

    public function test_mark_all_notifications_as_read_with_no_notifications(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('notifications.readAll'));

        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful(),
            "Expected 2xx/3xx, got {$response->getStatusCode()}"
        );
    }
}
