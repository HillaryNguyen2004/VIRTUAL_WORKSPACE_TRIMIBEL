<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\CompanyHoursRepository;
use App\Repositories\DayOffRequestRepository;
use App\Services\DayOffService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class DayOffServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function makeService(
        ?DayOffRequestRepository $repo = null,
        ?CompanyHoursRepository $hoursRepo = null
    ): DayOffService {
        return new DayOffService(
            $repo  ?? Mockery::mock(DayOffRequestRepository::class),
            $hoursRepo ?? Mockery::mock(CompanyHoursRepository::class)
        );
    }

    public function test_create_request_with_empty_dates_array_returns_error(): void
    {
        $service = $this->makeService();
        $this->actingAs(User::factory()->create());

        $result = $service->createRequest(['dates' => [], 'leave_type' => 'OFF_FULL']);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('at least one date', $result['error']);
    }

    public function test_create_request_without_dates_key_returns_error(): void
    {
        $service = $this->makeService();
        $this->actingAs(User::factory()->create());

        $result = $service->createRequest(['leave_type' => 'OFF_FULL']);

        $this->assertArrayHasKey('error', $result);
    }

    public function test_create_request_returns_error_when_date_already_booked(): void
    {
        $repo      = Mockery::mock(DayOffRequestRepository::class);
        $hoursRepo = Mockery::mock(CompanyHoursRepository::class);
        $repo->shouldReceive('findByUserAndDate')->once()->andReturn(new \stdClass());
        $hoursRepo->shouldReceive('getCompanyHours')->andReturnNull();

        $service = new DayOffService($repo, $hoursRepo);
        $this->actingAs(User::factory()->create());

        $result = $service->createRequest([
            'dates'      => ['2026-12-25'],
            'leave_type' => 'OFF_FULL',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('2026-12-25', $result['error']);
    }

    public function test_create_full_day_off_request_returns_success(): void
    {
        $repo      = Mockery::mock(DayOffRequestRepository::class);
        $hoursRepo = Mockery::mock(CompanyHoursRepository::class);
        $repo->shouldReceive('findByUserAndDate')->once()->andReturnNull();
        $repo->shouldReceive('create')->once()->andReturn(new \stdClass());
        $hoursRepo->shouldReceive('getCompanyHours')->andReturnNull();

        $service = new DayOffService($repo, $hoursRepo);
        $this->actingAs(User::factory()->create());

        $result = $service->createRequest([
            'dates'      => ['2026-12-25'],
            'leave_type' => 'OFF_FULL',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);
    }

    public function test_create_half_day_request_fetches_company_hours(): void
    {
        $repo        = Mockery::mock(DayOffRequestRepository::class);
        $hoursRepo   = Mockery::mock(CompanyHoursRepository::class);

        $companyHour = (object)[
            'start_at'    => '08:00:00',
            'end_at'      => '17:00:00',
            'lunch_start' => null,
            'lunch_end'   => null,
            'mid_day'     => '12:00:00',
        ];

        $repo->shouldReceive('findByUserAndDate')->once()->andReturnNull();
        $repo->shouldReceive('create')->once()->andReturn(new \stdClass());
        $hoursRepo->shouldReceive('getCompanyHours')->once()->andReturn($companyHour);

        $service = new DayOffService($repo, $hoursRepo);
        $this->actingAs(User::factory()->create());

        $result = $service->createRequest([
            'dates'           => ['2026-12-25'],
            'leave_type'      => 'OFF_HALF',
            'half_day_period' => 'AM',
        ]);

        $this->assertTrue($result['success']);
    }

    public function test_approve_request_throws_when_requester_is_not_in_staff_team(): void
    {
        $repo      = Mockery::mock(DayOffRequestRepository::class);
        $hoursRepo = Mockery::mock(CompanyHoursRepository::class);

        $requester           = new User();
        $requester->team_leader_id = null; // not managed by this staff

        $mockRequest       = new \stdClass();
        $mockRequest->user = $requester;

        $repo->shouldReceive('find')->with(99)->andReturn($mockRequest);

        $service = new DayOffService($repo, $hoursRepo);
        $staff   = User::factory()->create();
        $this->actingAs($staff);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized');

        $service->approveRequest(99);
    }

    public function test_reject_request_throws_when_requester_is_not_in_staff_team(): void
    {
        $repo      = Mockery::mock(DayOffRequestRepository::class);
        $hoursRepo = Mockery::mock(CompanyHoursRepository::class);

        $requester                 = new User();
        $requester->team_leader_id = null;

        $mockRequest       = new \stdClass();
        $mockRequest->user = $requester;

        $repo->shouldReceive('find')->with(55)->andReturn($mockRequest);

        $service = new DayOffService($repo, $hoursRepo);
        $this->actingAs(User::factory()->create());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized');

        $service->rejectRequest(55);
    }
}
