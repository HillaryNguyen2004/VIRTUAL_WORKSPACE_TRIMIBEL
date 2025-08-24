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

        // Eager load user and their dayOffRequests
        $checkIns = $query->with(['user.dayOffRequests'])->paginate(3);

        // Add computed flags to each check-in item
        $checkIns->getCollection()->transform(function ($log) {
            $log->is_half_day_off = $log->user?->hasHalfDayOffOn($log->date);
            $log->is_full_day_off = $log->user?->hasFullDayOffOn($log->date);

            if ($log->check_in_time && $log->check_out_time) {
                $in = \Carbon\Carbon::parse($log->check_in_time);
                $out = \Carbon\Carbon::parse($log->check_out_time);
                $log->working_hours = $out->diff($in)->format('%H:%I');
            }

            return $log;
        });

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


}


