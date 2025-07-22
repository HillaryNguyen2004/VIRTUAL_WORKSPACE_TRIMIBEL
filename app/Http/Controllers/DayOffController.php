<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DayOffService;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after:today'],
            'leave_type' => ['required', 'in:OFF_FULL,OFF_HALF'],
            'reason' => ['nullable', 'string'],
        ]);

        $result = $this->service->createRequest($validated);

        if (isset($result['error'])) {
            return back()->withErrors(['date' => $result['error']]);
        }

        return redirect()->route('dayoff.request')->with('success', 'Day off request submitted!');
    }

    public function staffPendingRequests()
    {
        $requests = $this->service->getPendingRequests();
        return view('dayoff.staff_pending', compact('requests'));
    }

    public function approve($id)
    {
        $this->service->approveRequest($id);
        return back()->with('success', 'Day-off request approved.');
    }

    public function reject($id)
    {
        $this->service->rejectRequest($id);
        return back()->with('success', 'Day-off request rejected.');
    }
}
