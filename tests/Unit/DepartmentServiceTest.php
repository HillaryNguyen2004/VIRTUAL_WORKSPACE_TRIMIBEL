<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\User;
use App\Repositories\DepartmentRepository;
use App\Services\DepartmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepartmentServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function makeService(): DepartmentService
    {
        return app(DepartmentService::class);
    }

    public function test_create_department_persists_to_database(): void
    {
        $service    = $this->makeService();
        $department = $service->createDepartment(['name' => 'Engineering']);

        $this->assertInstanceOf(Department::class, $department);
        $this->assertEquals('Engineering', $department->name);
        $this->assertDatabaseHas('departments', ['name' => 'Engineering']);
    }

    public function test_update_department_changes_name(): void
    {
        $department = Department::create(['name' => 'HR Dept']);
        $service    = $this->makeService();

        $result = $service->updateDepartment($department, ['name' => 'Human Resources']);

        $this->assertTrue($result);
        $this->assertDatabaseHas('departments', ['name' => 'Human Resources']);
    }

    public function test_delete_department_removes_from_database(): void
    {
        $department = Department::create(['name' => 'Temp Department']);
        $id         = $department->id;
        $service    = $this->makeService();

        $result = $service->deleteDepartment($department);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('departments', ['id' => $id]);
    }

    public function test_remove_staff_throws_when_user_not_in_department(): void
    {
        $dept1 = Department::create(['name' => 'Dept A']);
        $dept2 = Department::create(['name' => 'Dept B']);
        $user  = User::factory()->create(['department_id' => $dept2->id]);

        $service = $this->makeService();

        $this->expectException(\InvalidArgumentException::class);

        $service->removeStaffFromDepartment($dept1, $user);
    }

    public function test_assign_staff_to_valid_department(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $user       = User::factory()->create(['department_id' => null]);
        $service    = $this->makeService();

        $result = $service->assignStaffToDepartment($user->id, $department->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'department_id' => $department->id]);
    }
}
