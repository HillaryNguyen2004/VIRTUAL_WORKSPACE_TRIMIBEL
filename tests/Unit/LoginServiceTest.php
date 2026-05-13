<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\LoginService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoginServiceTest extends TestCase
{
    use DatabaseTransactions;

    private LoginService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoginService();
    }

    public function test_throws_when_user_not_found(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->login(['email' => 'nobody@example.com', 'password' => 'any']);
    }

    public function test_user_not_found_error_is_on_email_field(): void
    {
        try {
            $this->service->login(['email' => 'nobody@example.com', 'password' => 'any']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_throws_when_user_is_already_blocked(): void
    {
        $user = User::factory()->create(['blocked' => true]);

        try {
            $this->service->login(['email' => $user->email, 'password' => 'any']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_successful_login_returns_user(): void
    {
        $user = User::factory()->create();

        Auth::shouldReceive('attempt')
            ->once()
            ->with(['email' => $user->email, 'password' => 'correct'], false)
            ->andReturn(true);

        $result = $this->service->login(['email' => $user->email, 'password' => 'correct']);

        $this->assertSame($user->id, $result->id);
    }

    public function test_successful_login_resets_login_attempts(): void
    {
        $user = User::factory()->create(['login_attempts' => 3]);

        Auth::shouldReceive('attempt')->once()->andReturn(true);

        $this->service->login(['email' => $user->email, 'password' => 'correct']);

        $this->assertEquals(0, $user->fresh()->login_attempts);
    }

    public function test_wrong_password_throws_error_on_password_field(): void
    {
        $user = User::factory()->create();

        Auth::shouldReceive('attempt')->once()->andReturn(false);

        try {
            $this->service->login(['email' => $user->email, 'password' => 'wrong']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('password', $e->errors());
        }
    }

    public function test_wrong_password_increments_login_attempts(): void
    {
        $user = User::factory()->create(['login_attempts' => 2]);

        Auth::shouldReceive('attempt')->once()->andReturn(false);

        try {
            $this->service->login(['email' => $user->email, 'password' => 'wrong']);
        } catch (ValidationException) {
        }

        $this->assertEquals(3, $user->fresh()->login_attempts);
    }

    public function test_fifth_failed_attempt_blocks_the_user(): void
    {
        $user = User::factory()->create(['login_attempts' => 4, 'blocked' => false]);

        Auth::shouldReceive('attempt')->once()->andReturn(false);

        try {
            $this->service->login(['email' => $user->email, 'password' => 'wrong']);
        } catch (ValidationException) {
        }

        $this->assertTrue((bool) $user->fresh()->blocked);
    }

    public function test_fifth_failed_attempt_returns_error_on_email_field(): void
    {
        $user = User::factory()->create(['login_attempts' => 4, 'blocked' => false]);

        Auth::shouldReceive('attempt')->once()->andReturn(false);

        try {
            $this->service->login(['email' => $user->email, 'password' => 'wrong']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_remember_me_flag_is_forwarded_to_auth_attempt(): void
    {
        $user = User::factory()->create();

        Auth::shouldReceive('attempt')
            ->once()
            ->with(['email' => $user->email, 'password' => 'pass'], true)
            ->andReturn(true);

        $this->service->login(['email' => $user->email, 'password' => 'pass'], true);
    }
}
