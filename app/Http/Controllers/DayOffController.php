<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DayOffService;
use App\Http\Requests\StoreDayOffRequest;
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

    public function store(StoreDayOffRequest $request)
    {
         $validated = $request->validated();
        $result = $this->service->createRequest($validated);

        if (isset($result['error'])) {
            return back()->withErrors(['date' => $result['error']]);
        }

        return redirect()->route('dayoff.request')->with('success', __('dayoff.success_submit'));
    }

    public function staffPendingRequests()
    {
        $requests = $this->service->getPendingRequests();
        return view('dayoff.staff_pending', compact('requests'));
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
