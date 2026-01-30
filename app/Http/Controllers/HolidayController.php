<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Services\HolidayService;
use App\Repositories\HolidayRepository;
use Illuminate\Http\Request;
use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;

class HolidayController extends Controller
{
    protected HolidayService $holidayService;
    protected HolidayRepository $holidayRepository;

    public function __construct(HolidayService $holidayService, HolidayRepository $holidayRepository)
    {
        $this->holidayService = $holidayService;
        $this->holidayRepository = $holidayRepository;
    }

    public function index(Request $request)
    {
        $filters = $this->holidayService->extractFilters($request);
        $holidays = $this->holidayService->getFilteredHolidays($filters);
        
        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'holidays' => $holidays
            ]);
        }
        
        return view('admin.holidays.index', compact('holidays', 'filters'));
    }

    public function create()
    {
        return view('admin.holidays.create');
    }

    public function store(StoreHolidayRequest $request)
    {
        $holiday = $this->holidayService->createHoliday($request->validated());

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Holiday created successfully!',
                'holiday' => $holiday
            ], 201);
        }

        return redirect()->route('holidays.index')
            ->with('success', __('messages.holiday_created'));
    }

    public function edit(Holiday $holiday)
    {
        return view('admin.holidays.edit', compact('holiday'));
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday)
    {
        $this->holidayService->updateHoliday($holiday, $request->validated());

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Holiday updated successfully!',
                'holiday' => $holiday->fresh()
            ]);
        }

        return redirect()->route('holidays.index')
            ->with('success', __('messages.holiday_updated'));
    }

    public function destroy(Holiday $holiday)
    {
        $this->holidayRepository->delete($holiday);
        
        // Return JSON for AJAX requests
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Holiday deleted successfully!'
            ]);
        }
        
        return redirect()->route('holidays.index')
            ->with('success', __('messages.holiday_deleted'));
    }

    public function show(Holiday $holiday)
    {
        return view('admin.holidays.show', compact('holiday'));
    }
}