<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DayOffService;
use App\Http\Requests\StoreDayOffRequest;
use Carbon\CarbonPeriod;
class DayOffController extends Controller
{
    protected $service;

    public function __construct(DayOffService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('dayoff.request');
    }

    // public function store(StoreDayOffRequest $request)
    // {
    //      $validated = $request->validated();
    //     $result = $this->service->createRequest($validated);

    //     if (isset($result['error'])) {
    //         if ($request->expectsJson()) {
    //             return response()->json(['error' => $result['error']], 422);
    //         }
    //         return back()->withErrors(['general' => $result['error']]);
    //     }

    //     if ($request->expectsJson()) {
    //         return response()->json([
    //             'message' => __('dayoff.success_submit'),
    //             'count' => $result['count'] ?? 1
    //         ]);
    //     }

    //     return redirect()->route('dayoff.request')->with('success', __('dayoff.success_submit'));
    // }

    public function store(StoreDayOffRequest $request)
    {
        $validated = $request->validated();

        $period = CarbonPeriod::create(
            $validated['start_date'],
            $validated['end_date']
        );

        $validated['dates'] = collect($period)
            ->map(fn ($date) => $date->toDateString())
            ->toArray();

        // Generate group ID for the submission
        $validated['request_group_id'] = (string) \Illuminate\Support\Str::uuid();

        $result = $this->service->createRequest($validated);

        if (isset($result['error'])) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $result['error']], 422);
            }
            return back()->withErrors(['general' => $result['error']]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('dayoff.success_submit'),
                'count' => $result['count'] ?? count($validated['dates'])
            ]);
        }

        return redirect()
            ->route('dayoff.request')
            ->with('success', __('dayoff.success_submit'));
    }



    public function staffPendingRequests()
    {
        $requests = $this->service->getPendingRequests();
        
        // Group requests by request_group_id, treating null as individual groups
        $groupedRequests = collect($requests)->groupBy(function ($request) {
            return $request->request_group_id ?? 'single_' . $request->id;
        });
        
        return view('dayoff.staff_pending', compact('groupedRequests'));
    }

    public function approve($id)
    {
        
        $dayOff = $this->service->approveRequest($id); // assume it returns the updated model
    if ($dayOff && $dayOff->user) {
        // Store notification-like message in cache for the user
        cache()->put('user_'.$dayOff->user->id.'_dayoff_notice', __('dayoff.notice_approved', ['date' => $dayOff->date]), now()->addMinutes(10));
    }

    return back()->with('success', 'Day-off request approved.');
    }

    public function reject($id)
    {
        $this->service->rejectRequest($id);
        return back()->with('success', __('dayoff.success_reject'));
    }
}
