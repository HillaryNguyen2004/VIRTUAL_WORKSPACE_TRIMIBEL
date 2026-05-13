<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeService(): UserService
    {
        return new UserService(Mockery::mock(UserRepositoryInterface::class));
    }

    public function test_delete_user_returns_false_when_deleting_self(): void
    {
        $service = $this->makeService();
        $user    = User::factory()->create();
        $this->actingAs($user);

        $result = $service->deleteUser($user);

        $this->assertFalse($result);
    }

    public function test_delete_user_returns_false_when_target_is_admin(): void
    {
        Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user',    'guard_name' => 'web']);

        $actor = User::factory()->create();
        $actor->assignRole('user');
        $this->actingAs($actor);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $service = $this->makeService();
        $result  = $service->deleteUser($admin);

        $this->assertFalse($result);
    }

    public function test_delete_user_returns_true_for_regular_user(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);

        $actor = User::factory()->create();
        $actor->assignRole('admin');
        $this->actingAs($actor);

        $target = User::factory()->create();
        $target->assignRole('user');

        $service = $this->makeService();
        $result  = $service->deleteUser($target);

        $this->assertTrue($result);
        $this->assertNull(User::find($target->id));
    }

    public function test_create_user_generates_unique_username(): void
    {
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $actor = User::factory()->create();
        $this->actingAs($actor);

        $repo = Mockery::mock(UserRepositoryInterface::class);
        $repo->shouldReceive('create')->once()->andReturnUsing(function (array $data) {
            $user           = new User($data);
            $user->id       = 999;
            return $user;
        });

        $service = new UserService($repo);

        $created = $service->createUser([
            'name'  => 'Test User',
            'email' => 'testcreate@example.com',
            'roles' => 'user',
        ]);

        $this->assertNotEmpty($created->username);
    }

    public function test_update_user_syncs_role(): void
    {
        Role::firstOrCreate(['name' => 'user',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $actor = User::factory()->create();
        $actor->assignRole('admin');
        $this->actingAs($actor);

        $target = User::factory()->create();
        $target->assignRole('user');

        $service = $this->makeService();
        $service->updateUser($target, ['name' => 'Updated Name', 'role' => 'staff']);

        $target->refresh();
        $this->assertTrue($target->hasRole('staff'));
        $this->assertFalse($target->hasRole('user'));
    }
}
