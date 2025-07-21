<?php
// app/Http/Controllers/DayOffController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DayOffRequest;
use App\Models\User;
use Carbon\Carbon;

class DayOffController extends Controller
{
    public function create()
    {
        return view('dayoff.request');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date', 'after:today'],
            'leave_type' => ['required', 'in:OFF_FULL,OFF_HALF'],
            'reason' => ['nullable', 'string'],
        ]);

        $userId = auth()->id();

        $existing = DayOffRequest::where('user_id', $userId)
            ->where('date', $request->date)
            ->first();

        if ($existing) {
            return back()->withErrors(['date' => 'You already made a day-off request for this date.']);
        }

        DayOffRequest::create([
            'user_id' => $userId,
            'date' => $request->date,
            'leave_type' => $request->leave_type,
            'reason' => $request->reason,
            'status' => 'PENDING',
        ]);

        return redirect()->route('dayoff.request')->with('success', 'Day off request submitted!');
    }
    public function staffPendingRequests()
    {
        $requests = DayOffRequest::where('status', 'PENDING')->with('user')->get();
        return view('dayoff.staff_pending', compact('requests'));
    }

    public function approve($id)
    {
        $request = DayOffRequest::findOrFail($id);
        $request->update([
            'status' => 'APPROVED',
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Day-off request approved.');
    }

    public function reject($id)
    {
        $request = DayOffRequest::findOrFail($id);
        $request->update([
            'status' => 'REJECTED',
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Day-off request rejected.');
    }
}
