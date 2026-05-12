<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_profile_page_requires_authentication(): void
    {
        $response = $this->get(route('profile'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertStatus(200);
    }

    public function test_settings_page_requires_authentication(): void
    {
        $response = $this->get(route('settings'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_settings_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings'));

        $response->assertStatus(200);
    }
}
