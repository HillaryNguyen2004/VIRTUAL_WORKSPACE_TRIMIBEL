<?php

namespace Tests\Unit;

use App\Repositories\NotificationRepository;
use App\Services\NotificationService;
use Mockery;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    public function test_mark_as_read_returns_true_when_notification_exists(): void
    {
        $notification = new \stdClass();

        $repo = Mockery::mock(NotificationRepository::class);
        $repo->shouldReceive('findById')->with(42)->andReturn($notification);
        $repo->shouldReceive('markAsRead')->with($notification)->once();

        $service = new NotificationService($repo);

        $this->assertTrue($service->markAsRead(42));
    }

    public function test_mark_as_read_returns_false_when_notification_not_found(): void
    {
        $repo = Mockery::mock(NotificationRepository::class);
        $repo->shouldReceive('findById')->with(0)->andReturnNull();

        $service = new NotificationService($repo);

        $this->assertFalse($service->markAsRead(0));
    }

    public function test_mark_all_as_read_delegates_to_repository(): void
    {
        $repo = Mockery::mock(NotificationRepository::class);
        $repo->shouldReceive('markAllAsRead')->once();

        $service = new NotificationService($repo);
        $service->markAllAsRead();

        // Assertion is implicit: Mockery will fail if markAllAsRead was not called once
        $this->assertTrue(true);
    }
}
