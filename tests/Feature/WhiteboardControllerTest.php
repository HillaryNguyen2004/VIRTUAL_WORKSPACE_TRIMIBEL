<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WhiteboardControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_whiteboard_index_requires_authentication(): void
    {
        $response = $this->get(route('wbo.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_whiteboard_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('wbo.index'));

        $response->assertStatus(200);
    }

    public function test_create_whiteboard_requires_authentication(): void
    {
        $response = $this->post(route('wbo.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_create_whiteboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('wbo.create'));

        // Expect redirect to the new board URL (2xx or 3xx)
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirect(),
            "Expected 2xx or 3xx, got {$response->getStatusCode()}"
        );
    }
}
