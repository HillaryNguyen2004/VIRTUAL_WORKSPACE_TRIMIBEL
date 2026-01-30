<?php

namespace App\Services;

use App\Models\Holiday;
use App\Repositories\HolidayRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HolidayService
{
    protected HolidayRepository $holidayRepository;

    public function __construct(HolidayRepository $holidayRepository)
    {
        $this->holidayRepository = $holidayRepository;
    }

    public function createHoliday(array $data): Holiday
    {
        return Holiday::create([
            'title'      => $data['title'],
            'start_date' => $data['start_date'],  // datetime-local already in correct format
            'end_date'   => $data['end_date'] ?? null,
        ]);
    }

    public function updateHoliday(Holiday $holiday, array $data): bool
    {
        return $holiday->update([
            'title'      => $data['title'],
            'start_date' => $data['start_date'],  // datetime-local already in correct format
            'end_date'   => $data['end_date'] ?? null,
        ]);
    }

    public function extractFilters(Request $request): array
    {
        return [
            'search' => $request->input('search'),
            'year'   => $request->input('year'),
            'sort'   => $request->input('sort', 'desc'),
        ];
    }

    public function getFilteredHolidays(array $filters)
    {
        return $this->holidayRepository->getFilteredPaginated($filters);
    }
}