<?php
// app/Http/Controllers/DayOffController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DayOffRequest;
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
}
