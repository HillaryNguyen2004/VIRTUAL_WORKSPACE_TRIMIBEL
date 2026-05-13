<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function regularUser(): User
    {
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('user');
        return $user;
    }

    public function test_user_list_requires_authentication(): void
    {
        $response = $this->get('/management/users');

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_user_list(): void
    {
        $response = $this->actingAs($this->adminUser())->get('/management/users');

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_user_list(): void
    {
        $response = $this->actingAs($this->regularUser())->get('/management/users');

        // Should either redirect or return 403
        $this->assertTrue(
            $response->isRedirect() || $response->getStatusCode() === 403,
            "Expected redirect or 403, got {$response->getStatusCode()}"
        );
    }

    public function test_admin_can_access_create_user_form(): void
    {
        $response = $this->actingAs($this->adminUser())->get('/admin/users/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_a_user_with_valid_data(): void
    {
        Notification::fake();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $response = $this->actingAs($this->adminUser())->post('/admin/users/store', [
            'name'  => 'New Staff Member',
            'email' => 'newstaff_test@example.com',
            'role'  => 'user',
        ]);

        // Should redirect after successful creation
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['email' => 'newstaff_test@example.com']);
    }

    public function test_admin_can_delete_a_regular_user(): void
    {
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $target = User::factory()->create();
        $target->assignRole('user');

        $response = $this->actingAs($this->adminUser())->delete("/users/{$target->id}");

        $response->assertRedirect();
        $this->assertNull(User::find($target->id));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->delete("/users/{$admin->id}");

        // The service returns false, controller should redirect with error
        $this->assertNotNull(User::find($admin->id));
    }
}
