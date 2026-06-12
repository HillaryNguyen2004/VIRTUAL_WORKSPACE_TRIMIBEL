<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LoginService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole('admin');

        $this->mock(LoginService::class)
            ->shouldReceive('login')->once()->andReturn($user);

        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/admin/dashboard');
    }

    public function test_subadmin_is_redirected_to_subadmin_dashboard(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'subadmin', 'guard_name' => 'web']);
        $user->assignRole('subadmin');

        $this->mock(LoginService::class)
            ->shouldReceive('login')->once()->andReturn($user);

        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('subadmin.dashboard'));
    }

    public function test_staff_is_redirected_to_staff_dashboard(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'staff', 'guard_name' => 'web']);
        $user->assignRole('staff');

        $this->mock(LoginService::class)
            ->shouldReceive('login')->once()->andReturn($user);

        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/staff/dashboard');
    }

    public function test_substaff_is_redirected_to_substaff_dashboard(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'substaff', 'guard_name' => 'web']);
        $user->assignRole('substaff');

        $this->mock(LoginService::class)
            ->shouldReceive('login')->once()->andReturn($user);

        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('substaff.dashboard'));
    }

    public function test_regular_user_is_redirected_to_user_dashboard(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'user', 'guard_name' => 'web']);
        $user->assignRole('user');

        $this->mock(LoginService::class)
            ->shouldReceive('login')->once()->andReturn($user);

        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('user.dashboard'));
    }

    public function test_failed_login_redirects_back_with_errors(): void
    {
        $this->mock(LoginService::class)
            ->shouldReceive('login')->once()
            ->andThrow(ValidationException::withMessages(['email' => 'Invalid credentials.']));

        $this->post(route('login.post'), ['email' => 'bad@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
    }

    public function test_logout_redirects_to_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));
    }
}
