<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CheckIn;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\CompanyHour;
use Illuminate\Support\Facades\DB;
use App\Services\CheckInExportService;
// use Maatwebsite\Excel\Facades\Excel;
// use App\Exports\CheckInExport;



class CheckInController extends Controller
{
    // public function index(Request $request)
    // {
    //     $query = DB::table('check_ins')
    //         ->orderByDesc('date')
    //         ->orderByDesc('check_in_time');

    //     if ($request->filled('username')) {
    //         $query->where('user_name', 'like', '%' . $request->username . '%');
    //     }

    //     if ($request->filled('date')) {
    //         $query->where('date', $request->date);
    //     }

    //     if ($request->filled('status')) {
    //         if ($request->status === 'late') {
    //             $query->where('is_late', true);
    //         } elseif ($request->status === 'on_time') {
    //             $query->where('is_late', false);
    //         }
    //     }

    //     $checkIns = $query->paginate(3);

    //     return view('users.checkin_index', compact('checkIns'));
    // }

    public function index(Request $request, CheckInExportService $exportService)
    {
        $query = $exportService->getFilteredCheckIns($request);
        $checkIns = $query->paginate(3);

        return view('users.checkin_index', compact('checkIns'));
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
        ]);

        $user = User::where('name', $request->username)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid user'], 401);
        }

        // Optional: generate token if doesn't exist
        // if (!$user->api_token) {
        //     $user->api_token = Str::random(60);
        //     $user->save();
        // }
        $token = $user->createToken('check-in-token')->plainTextToken;

        // Use Vietnam timezone
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $today = $now->toDateString();
        

        // Check if already checked in today
        $alreadyCheckedIn = \DB::table('check_ins')
            ->where('user_name', $user->name)
            ->where('date', $today)
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json([
                'message' => 'You have already checked in today.',
            ], 400);
        }
        $workingHour = CompanyHour::first(); // Assuming one row
        $isLate = false;

        if ($workingHour) {
            $configuredStart = Carbon::createFromFormat('H:i:s', $workingHour->start_at, 'Asia/Ho_Chi_Minh')
                ->setDate($now->year, $now->month, $now->day);
            $isLate = $now->greaterThan($configuredStart);
        }


        // Save check-in
        \DB::table('check_ins')->insert([
            'user_name' => $user->name,
            'date' => $today,
            'check_in_time' => $now->toTimeString(),
            'created_at' => $now,
            'updated_at' => $now,
            'is_late' => $isLate,
        ]);

        return response()->json([
            'message' => 'Checked in successfully',
            'token' => $token,
        ]);
    }

    public function checkOut(Request $request)
    {
        $user = $request->user(); // Authenticated user via Sanctum

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $today = $now->toDateString();

        $checkIn = DB::table('check_ins')
            ->where('user_name', $user->name)
            ->where('date', $today)
            ->first();

        if (!$checkIn) {
            return response()->json([
                'message' => 'You have not checked in today.',
            ], 400);
        }

        if ($checkIn->check_out_time !== null) {
            return response()->json([
                'message' => 'You have already checked out today.',
            ], 400);
        }

        DB::table('check_ins')
            ->where('id', $checkIn->id)
            ->update([
                'check_out_time' => $now->toTimeString(),
                'updated_at' => $now,
            ]);

        return response()->json([
            'message' => 'Checked out successfully.',
        ]);
    }

    
    // public function export(Request $request, CheckInExportService $exportService)
    // {
    //     $filteredCheckIns = $exportService->getFilteredCheckIns($request)->get();
    //     return Excel::download(new CheckInExport($filteredCheckIns), 'checkin_logs.xlsx');
    // }

    // public function export(Request $request, CheckInExportService $exportService)
    // {
    //     $checkIns = $exportService->getFilteredCheckIns($request);
    //     $excelFile = $exportService->generateExcel($checkIns);

    //     return response()->download($excelFile['file'], $excelFile['name'])->deleteFileAfterSend(true);
    // }
    public function export(Request $request, CheckInExportService $exportService)
    {
        $checkIns = $exportService->getFilteredCheckIns($request)->get(); // ✅ Now we get all results
        $excelFile = $exportService->generateExcel($checkIns);

        return response()->download($excelFile['file'], $excelFile['name'])->deleteFileAfterSend(true);
    }


}


