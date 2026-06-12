<?php

namespace Tests\Unit;

use App\Models\Holiday;
use App\Repositories\HolidayRepository;
use App\Services\HolidayService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HolidayServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function makeService(): HolidayService
    {
        return app(HolidayService::class);
    }

    public function test_create_holiday_persists_to_database(): void
    {
        $service = $this->makeService();

        $holiday = $service->createHoliday([
            'title'      => 'Independence Day',
            'start_date' => '2027-09-02',
            'end_date'   => '2027-09-02',
        ]);

        $this->assertInstanceOf(Holiday::class, $holiday);
        $this->assertEquals('Independence Day', $holiday->title);
        $this->assertDatabaseHas('holidays', ['title' => 'Independence Day']);
    }

    public function test_create_holiday_without_end_date(): void
    {
        $service = $this->makeService();

        $holiday = $service->createHoliday([
            'title'      => 'Single Day Feast',
            'start_date' => '2027-10-01',
            'end_date'   => null,
        ]);

        $this->assertInstanceOf(Holiday::class, $holiday);
        $this->assertNull($holiday->end_date);
    }

    public function test_update_holiday_changes_title_and_dates(): void
    {
        $holiday = Holiday::create([
            'title'      => 'Old Title',
            'start_date' => '2027-01-01',
            'end_date'   => '2027-01-01',
        ]);

        $service = $this->makeService();
        $result  = $service->updateHoliday($holiday, [
            'title'      => 'New Year Day',
            'start_date' => '2027-01-01',
            'end_date'   => '2027-01-02',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('holidays', ['title' => 'New Year Day']);
    }

    public function test_extract_filters_returns_correct_keys(): void
    {
        $service = $this->makeService();
        $request = new \Illuminate\Http\Request(['search' => 'tet', 'year' => '2027', 'sort' => 'asc']);

        $filters = $service->extractFilters($request);

        $this->assertEquals('tet', $filters['search']);
        $this->assertEquals('2027', $filters['year']);
        $this->assertEquals('asc', $filters['sort']);
    }
}
