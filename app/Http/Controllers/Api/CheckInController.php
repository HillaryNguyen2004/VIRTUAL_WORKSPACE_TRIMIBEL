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
use App\Services\CheckInService;
use App\Http\Requests\CheckInRequest;
use App\Http\Requests\CheckOutRequest;

// use Maatwebsite\Excel\Facades\Excel;
// use App\Exports\CheckInExport;



class CheckInController extends Controller
{
    protected $checkInService;

    public function __construct(CheckInService $checkInService)
    {
        $this->checkInService = $checkInService;
    }
   
    // public function index(Request $request, CheckInExportService $exportService)
    // {
    //     // $query = $exportService->getFilteredCheckIns($request);
    //     // $checkIns = $query->paginate(3);

    //     // return view('users.checkin_index', compact('checkIns'));
    //     $query = $exportService->getFilteredCheckIns($request);

    //     // Eager load the related user and their dayOffRequests
    //     $checkIns = $query->with(['user.dayOffRequests'])->paginate(3);

    //     return view('users.checkin_index', compact('checkIns'));
    // }
    public function index(Request $request, CheckInExportService $exportService)
    {
        $query = $exportService->getFilteredCheckIns($request);

        // Get per_page from request, default to 5
        $perPage = $request->get('per_page', 5);
        
        // Validate per_page value
        $allowedPerPage = [5, 10, 15, 25, 50];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 5;
        }

        // Eager load user and their dayOffRequests
        $checkIns = $query->with(['user.dayOffRequests'])->paginate($perPage);

        // Add computed flags to each check-in item
        foreach ($checkIns as $log) {
            $log->is_half_day_off = $log->user?->hasHalfDayOffOn($log->date);
            $log->is_full_day_off = $log->user?->hasFullDayOffOn($log->date);

            // Calculate if employee is late
            $log->is_late = $this->isLate($log->check_in_time, $log->date);

            if ($log->check_in_time && $log->check_out_time) {
                $in = \Carbon\Carbon::parse($log->check_in_time);
                $out = \Carbon\Carbon::parse($log->check_out_time);
                $log->working_hours = $out->diff($in)->format('%H:%I');
            }
        }

        return view('users.checkin_index', compact('checkIns'));
    }


    public function checkIn(CheckInRequest $request)
    {
        
        $result = $this->checkInService->processCheckIn($request->username);

        return $result['status']
            ? response()->json(['message' => $result['message'], 'token' => $result['token']])
            : response()->json(['message' => $result['message']], 400);
    }


    public function checkOut(CheckOutRequest $request)
    {
       
        $result = $this->checkInService->processCheckOut($request->username);

        return $result['status']
            ? response()->json(['message' => $result['message']])
            : response()->json(['message' => $result['message']], 400);
    }

    

    public function export(Request $request, CheckInExportService $exportService)
    {
        $checkIns = $exportService->getFilteredCheckIns($request)->get(); // ✅ Now we get all results
        $excelFile = $exportService->generateExcel($checkIns);

        return response()->download($excelFile['file'], $excelFile['name'])->deleteFileAfterSend(true);
    }

    /**
     * Check if the check-in time is late compared to company hours
     */
    private function isLate($checkInTime, $date)
    {
        if (!$checkInTime) {
            return false;
        }

        // Get the current company hours (assuming there's only one active)
        $companyHour = CompanyHour::first();
        
        if (!$companyHour) {
            return false; // No company hours defined, can't determine if late
        }

        try {
            // Parse the check-in time
            $checkInDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $checkInTime);
            
            // Create company start time for the same date
            $companyStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $companyHour->start_at);
            
            // Add a small grace period (e.g., 5 minutes)
            $gracePeriodMinutes = 5;
            $allowedStartTime = $companyStartTime->copy()->addMinutes($gracePeriodMinutes);
            
            // Check if check-in is after the allowed start time
            return $checkInDateTime->greaterThan($allowedStartTime);
            
        } catch (\Exception $e) {
            // If there's any error parsing times, assume not late
            return false;
        }
    }


}


