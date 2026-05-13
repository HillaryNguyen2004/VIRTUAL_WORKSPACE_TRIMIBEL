<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_register_page_is_accessible(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_register_with_valid_data_creates_user_and_redirects(): void
    {
        Notification::fake();

        $response = $this->post(route('register.post'), [
            'first_name'            => 'Tuan',
            'last_name'             => 'Nguyen',
            'email'                 => 'tuannguyen_test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'tuannguyen_test@example.com']);
    }

    public function test_register_with_mismatched_passwords_returns_validation_errors(): void
    {
        $response = $this->post(route('register.post'), [
            'first_name'            => 'Tuan',
            'last_name'             => 'Nguyen',
            'email'                 => 'another@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'different456',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_register_with_missing_email_returns_validation_error(): void
    {
        $response = $this->post(route('register.post'), [
            'first_name'            => 'Tuan',
            'last_name'             => 'Nguyen',
            'email'                 => '',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_register_with_duplicate_email_returns_validation_error(): void
    {
        Notification::fake();

        // Create the first user
        $this->post(route('register.post'), [
            'first_name'            => 'First',
            'last_name'             => 'User',
            'email'                 => 'duplicate@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Attempt with same email
        $response = $this->post(route('register.post'), [
            'first_name'            => 'Second',
            'last_name'             => 'User',
            'email'                 => 'duplicate@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
