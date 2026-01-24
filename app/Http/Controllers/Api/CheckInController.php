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
use App\Services\CheckInService;
use App\Services\FaceService;
use App\Services\CheckInExportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CheckInController extends Controller
{
    protected $checkInService;
    protected $faceService;

    public function __construct(CheckInService $checkInService, FaceService $faceService)
    {
        $this->checkInService = $checkInService;
        $this->faceService = $faceService;
    }
    
    // New method to handle face verification and check-in/out
    public function faceProcess(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'check_type' => 'required|in:checkin,checkout',
                'image_data' => 'required|string'
            ]);
            
            $user = User::where('username', $request->username)->first();
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Verify face if user has registered face
            if ($user->face_hash) {
                $faceVerified = $this->verifyFaceFromImage($user, $request->image_data);
                
                if (!$faceVerified) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Face verification failed. Please try again or use manual check.',
                        'requires_verification' => true
                    ], 400);
                }
            }
            
            // Process check-in or check-out
            if ($request->check_type === 'checkin') {
                $result = $this->checkInService->processCheckIn($request->username, $user->id);
            } else {
                $result = $this->checkInService->processCheckOut($request->username, $user->id);
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Face check-in error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'An error occurred. Please try again.'
            ], 500);
        }
    }
    
    private function verifyFaceFromImage(User $user, string $imageData): bool
    {
        try {
            // Remove data URL prefix
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            
            // Decode base64
            $imageBinary = base64_decode($imageData);
            
            if ($imageBinary === false) {
                Log::error('Failed to decode image data');
                return false;
            }
            
            // Save temp file
            $tempPath = storage_path('app/temp/face_verify_' . time() . '.jpg');
            file_put_contents($tempPath, $imageBinary);
            
            // Create UploadedFile instance
            $file = new \Illuminate\Http\UploadedFile(
                $tempPath,
                'face.jpg',
                'image/jpeg',
                null,
                true
            );
            
            // Generate hash from the captured image
            $capturedHash = $this->faceService->generateHash($file);
            
            // Compare with stored hash
            $distance = levenshtein($user->face_hash, $capturedHash);
            
            // Clean up temp file
            unlink($tempPath);
            
            Log::info('Face verification', [
                'user_id' => $user->id,
                'distance' => $distance,
                'threshold' => 2000
            ]);
            
            // Return true if distance is within acceptable range
            return $distance <= 2000;
            
        } catch (\Exception $e) {
            Log::error('Face verification error: ' . $e->getMessage());
            return false;
        }
    }

    public function showFacePage(string $type)
    {
        $workingHour = CompanyHour::first();

        return view('face-checkin', [
            'checkType'   => $type,
            'workingHour'=> $workingHour,
        ]);
    }

    
    // ... rest of your existing methods ...
    public function index(Request $request, CheckInExportService $exportService)
    {
        $query = $exportService->getFilteredCheckIns($request);

        // Get per_page from request, default to 5
        $perPage = $request->get('per_page', 10);
        
        // Validate per_page value
        $allowedPerPage = [10, 25, 50];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
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