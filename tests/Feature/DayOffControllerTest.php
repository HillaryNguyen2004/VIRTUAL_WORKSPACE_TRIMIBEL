<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DayOffControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_authenticated_user_can_view_day_off_request_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dayoff.request'));

        $response->assertStatus(200);
    }

    public function test_submit_day_off_request_with_missing_dates_returns_errors(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dayoff.request.store'), [
            'leave_type' => 'OFF_FULL',
        ]);

        // Should redirect back with validation errors for start_date/end_date
        $response->assertSessionHasErrors();
    }

    public function test_submit_valid_full_day_off_request_succeeds(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dayoff.request.store'), [
            'leave_type' => 'OFF_FULL',
            'start_date' => '2027-12-25',
            'end_date'   => '2027-12-25',
            'reason'     => 'Holiday',
        ]);

        // Redirect or JSON success — must not be a 4xx/5xx
        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful(),
            "Expected 2xx/3xx, got {$response->getStatusCode()}"
        );
    }
}
