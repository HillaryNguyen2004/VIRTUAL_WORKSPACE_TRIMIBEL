<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Create roles needed by ProjectController::create()
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
    }

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function staffUser(): User
    {
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('staff');
        return $user;
    }

    public function test_project_list_requires_authentication(): void
    {
        $response = $this->get('/projects');

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_project_list(): void
    {
        $response = $this->actingAs($this->adminUser())->get('/projects');

        $response->assertStatus(200);
    }

    public function test_staff_can_view_project_list(): void
    {
        $response = $this->actingAs($this->staffUser())->get('/projects');

        $response->assertStatus(200);
    }

    public function test_project_create_form_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser())->get('/projects/create');

        $response->assertStatus(200);
    }

    public function test_create_project_requires_staff_assignment(): void
    {
        // Attempt to create a project with a non-staff user as staff_id
        $nonStaff = User::factory()->create(); // no role

        $response = $this->actingAs($this->adminUser())->post('/projects/store', [
            'title'      => 'Test Project',
            'staff_id'   => $nonStaff->id,
            'status'     => 'active',
            'start_date' => '2026-07-01',
            'due_date'   => '2026-12-31',
        ]);

        // Should abort with 422 because user is not staff
        $response->assertStatus(422);
    }

    public function test_admin_can_create_project_with_valid_staff(): void
    {
        $staff = $this->staffUser();

        $response = $this->actingAs($this->adminUser())->post('/projects/store', [
            'title'       => 'Valid Project',
            'staff_id'    => $staff->id,
            'status'      => 'active',
            'start_date'  => '2026-07-01',
            'due_date'    => '2026-12-31',
            'description' => 'A test project',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['title' => 'Valid Project']);
    }

    public function test_project_detail_page_is_accessible(): void
    {
        $staff   = $this->staffUser();
        $admin   = $this->adminUser();

        $project = \App\Models\Project::create([
            'title'      => 'Detail Test Project',
            'staff_id'   => $staff->id,
            'status'     => 'active',
            'start_date' => '2026-07-01',
            'due_date'   => '2026-12-31',
            'percentage' => 0,
        ]);

        $response = $this->actingAs($admin)->get("/projects/{$project->id}/details");

        $response->assertStatus(200);
    }
}
