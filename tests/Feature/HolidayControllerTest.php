<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HolidayControllerTest extends TestCase
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

    public function test_admin_can_create_a_holiday(): void
    {
        $response = $this->actingAs($this->adminUser())->post(route('holidays.store'), [
            'title'      => 'Reunification Day',
            'start_date' => '2027-04-30',
            'end_date'   => '2027-04-30',
        ]);

        $response->assertRedirect(route('holidays.index'));
        $this->assertDatabaseHas('holidays', ['title' => 'Reunification Day']);
    }

    public function test_store_holiday_requires_title(): void
    {
        $response = $this->actingAs($this->adminUser())->post(route('holidays.store'), [
            'title'      => '',
            'start_date' => '2027-04-30',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_store_holiday_requires_start_date(): void
    {
        $response = $this->actingAs($this->adminUser())->post(route('holidays.store'), [
            'title'      => 'Some Holiday',
            'start_date' => '',
        ]);

        $response->assertSessionHasErrors('start_date');
    }

    public function test_admin_can_update_a_holiday(): void
    {
        $holiday = Holiday::create([
            'title'      => 'Old Holiday',
            'start_date' => '2027-08-01',
        ]);

        $response = $this->actingAs($this->adminUser())->put(route('holidays.update', $holiday), [
            'title'      => 'Updated Holiday',
            'start_date' => '2027-08-01',
        ]);

        $response->assertRedirect(route('holidays.index'));
        $this->assertDatabaseHas('holidays', ['title' => 'Updated Holiday']);
    }

    public function test_admin_can_delete_a_holiday(): void
    {
        $holiday = Holiday::create([
            'title'      => 'Holiday To Delete',
            'start_date' => '2027-11-20',
        ]);
        $id = $holiday->id;

        $response = $this->actingAs($this->adminUser())->delete(route('holidays.destroy', $holiday));

        $response->assertRedirect(route('holidays.index'));
        $this->assertDatabaseMissing('holidays', ['id' => $id]);
    }
}
