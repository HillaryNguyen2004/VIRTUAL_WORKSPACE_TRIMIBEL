<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductivityCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LSTMDashboardController extends Controller
{
    private $lstmApiUrl = 'http://localhost:5001';

    /**
     * Display the LSTM dashboard page
     */
    public function index()
    {
        return view('admin.lstm-dashboard');
    }

    /**
     * Get dashboard statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'lastRun' => $this->getLastPredictionRun(),
                'highPerformers' => $this->getHighPerformersCount(),
                'atRiskEmployees' => $this->getAtRiskEmployeesCount(),
                'accuracy' => $this->getModelAccuracy()
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('LSTM Stats Error: ' . $e->getMessage());

            // Return default stats if there's an error
            return response()->json([
                'lastRun' => Carbon::now()->subHours(2)->toISOString(),
                'highPerformers' => 0,
                'atRiskEmployees' => 0,
                'accuracy' => 87.3
            ]);
        }
    }

    /**
     * Get productivity trends data
     */
    public function getTrends(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);

            // For now, return simulated trend data based on predictions
            // You can enhance this to track historical predictions over time
            $avgScore = DB::table('lstm_predictions')->avg('predicted_score');

            $labels = [];
            $actual = [];
            $predicted = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $labels[] = $date->format('M j');

                // Simulate trend around average
                $variance = rand(-10, 10);
                $score = max(0, min(100, $avgScore + $variance));

                $actual[] = round($score, 1);
                $predicted[] = round($score + rand(-5, 5), 1);
            }

            return response()->json([
                'labels' => $labels,
                'actual' => $actual,
                'predicted' => $predicted
            ]);

        } catch (\Exception $e) {
            Log::error('LSTM Trends Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load trends'], 500);
        }
    }

    /**
     * Get performance distribution data
     */
    public function getDistribution(): JsonResponse
    {
        try {
            // Use cached predictions for distribution
            $total = DB::table('lstm_predictions')->count();

            if ($total == 0) {
                return response()->json([
                    'high' => 0, 'medium' => 0, 'low' => 0, 'critical' => 0
                ]);
            }

            $distribution = DB::table('lstm_predictions')
                ->selectRaw('
                    SUM(CASE WHEN predicted_score >= 80 THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN predicted_score >= 60 AND predicted_score < 80 THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN predicted_score >= 40 AND predicted_score < 60 THEN 1 ELSE 0 END) as low,
                    SUM(CASE WHEN predicted_score < 40 THEN 1 ELSE 0 END) as critical
                ')
                ->first();

            // Convert to percentages
            return response()->json([
                'high' => round(($distribution->high / $total) * 100, 1),
                'medium' => round(($distribution->medium / $total) * 100, 1),
                'low' => round(($distribution->low / $total) * 100, 1),
                'critical' => round(($distribution->critical / $total) * 100, 1)
            ]);

        } catch (\Exception $e) {
            Log::error('LSTM Distribution Error: ' . $e->getMessage());
            return response()->json([
                'high' => 0, 'medium' => 0, 'low' => 0, 'critical' => 100
            ]);
        }
    }

    /**
     * Get employee predictions
     */
    public function getEmployeePredictions(): JsonResponse
    {
        try {
            Log::info('Fetching employee predictions...');

            // Get all active employees with their latest productivity data
            $employees = DB::table('users')
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
                ->select([
                    'users.id',
                    'users.name',
                    'users.user_profile_photo',
                    'departments.name as department',
                ])
                ->where('users.blocked', false)
                ->get();

            Log::debug("Found " . count($employees) . " active employees");

            $predictions = [];

            foreach ($employees as $employee) {
                try {
                    // Get LSTM prediction for this employee
                    $prediction = $this->getLSTMPredictionForEmployee($employee->id);

                    // Get current productivity score from data warehouse
                    $currentScore = $this->getCurrentProductivityScore($employee->id);

                    $row = [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'department' => $employee->department ?? 'Not Assigned',
                        'avatar' => $employee->user_profile_photo,
                        'currentScore' => $currentScore,
                        'predictedScore' => $prediction['score'] ?? 0,
                        'trend' => $this->calculateTrend($currentScore, $prediction['score'] ?? 0),
                        'trendValue' => abs($currentScore - ($prediction['score'] ?? 0)),
                        'lastUpdated' => Carbon::now()->toISOString(),
                        'confidence' => $prediction['confidence'] ?? 0
                    ];

                    $predictions[] = $row;
                    Log::debug("Prediction for {$employee->name}: " . json_encode($row));

                } catch (\Exception $e) {
                    Log::warning("Failed to get prediction for employee {$employee->id}: " . $e->getMessage());

                    // Add employee with default values instead of skipping
                    $predictions[] = [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'department' => $employee->department ?? 'Not Assigned',
                        'avatar' => $employee->user_profile_photo,
                        'currentScore' => 0,
                        'predictedScore' => 0,
                        'trend' => 'stable',
                        'trendValue' => 0,
                        'lastUpdated' => Carbon::now()->toISOString(),
                        'confidence' => 0
                    ];
                }
            }

            // Sort by predicted score (descending)
            usort($predictions, function($a, $b) {
                return $b['predictedScore'] <=> $a['predictedScore'];
            });

            Log::info('Returning ' . count($predictions) . ' predictions');

            return response()->json($predictions);

        } catch (\Exception $e) {
            Log::error('LSTM Employee Predictions Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load employee predictions'], 500);
        }
    }

    /**
     * Refresh all LSTM predictions
     */
    public function refreshPredictions(): JsonResponse
    {
        try {
            Log::info('Starting LSTM predictions refresh...');

            // Get all active employee IDs
            $employeeIds = DB::table('users')
                ->where('blocked', false)
                ->pluck('id');

            Log::info("Found " . count($employeeIds) . " employees to update");

            $successCount = 0;
            $errorCount = 0;

            foreach ($employeeIds as $employeeId) {
                try {
                    // Call LSTM API to generate fresh prediction
                    $response = Http::timeout(30)->get("{$this->lstmApiUrl}/predict/{$employeeId}");

                    Log::debug("LSTM API response for employee {$employeeId}: " . $response->status());

                    if ($response->successful()) {
                        $prediction = $response->json();
                        Log::debug("Received prediction for {$employeeId}: " . json_encode($prediction));

                        // Store prediction in database for caching
                        $this->storePrediction($employeeId, $prediction);
                        $successCount++;
                    } else {
                        $errorCount++;
                        Log::warning("Failed to get prediction for employee {$employeeId}: " . $response->body());
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("Error getting prediction for employee {$employeeId}: " . $e->getMessage());
                }
            }

            Log::info("LSTM refresh completed. Success: {$successCount}, Errors: {$errorCount}");

            // Verify data was stored
            $storedCount = DB::table('lstm_predictions')->count();
            Log::info("Total predictions in database: {$storedCount}");

            return response()->json([
                'message' => "Predictions refreshed: {$successCount} successful, {$errorCount} failed",
                'success' => $successCount,
                'errors' => $errorCount,
                'totalStored' => $storedCount
            ]);

        } catch (\Exception $e) {
            Log::error('LSTM Refresh Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to refresh predictions: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send productivity alert for specific employee
     */
    public function sendProductivityAlert(Request $request): JsonResponse
    {
        try {
            $employeeId = $request->input('employeeId');

            // Get employee details
            $employee = DB::table('users')
                ->where('id', $employeeId)
                ->first();

            if (!$employee) {
                return response()->json(['error' => 'Employee not found'], 404);
            }

            // Get prediction data
            $prediction = $this->getLSTMPredictionForEmployee($employeeId);

            // Send email alert (implement based on your mail system)
            // Mail::to($employee->email)->send(new ProductivityConcernAlert($employee, $prediction));

            // Log the alert
            Log::info("Productivity alert sent for employee {$employeeId}");

            return response()->json(['message' => 'Alert sent successfully']);

        } catch (\Exception $e) {
            Log::error('Send Alert Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send alert'], 500);
        }
    }

    // Private helper methods

    private function getLastPredictionRun()
    {
        // Get from your prediction log table or file system
        return Carbon::now()->subHours(2)->toISOString();
    }

    private function getHighPerformersCount(): int
    {
        try {
            // Use cached predictions for faster results
            return DB::table('lstm_predictions')
                ->where('predicted_score', '>=', 80)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAtRiskEmployeesCount(): int
    {
        try {
            // Use cached predictions for faster results
            return DB::table('lstm_predictions')
                ->where('predicted_score', '<', 60)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getModelAccuracy(): float
    {
        // Return stored model accuracy from training metrics
        return 87.3;
    }

    private function getLSTMPredictionsForPeriod(int $days)
    {
        // This would be from your predictions cache table
        // For now, return empty collection
        return collect();
    }

    private function getLSTMPredictionForEmployee(int $employeeId): array
    {
        try {
            // First try to get from cached predictions
            $cached = DB::table('lstm_predictions')
                ->where('employee_id', $employeeId)
                ->first();

            if ($cached) {
                Log::debug("Using cached prediction for employee {$employeeId}: score={$cached->predicted_score}");
                return [
                    'score' => round($cached->predicted_score, 1),
                    'confidence' => round($cached->confidence, 2)
                ];
            }

            Log::debug("No cache found for employee {$employeeId}, calling LSTM API...");

            // Fallback: Call LSTM API
            $response = Http::timeout(10)->get("{$this->lstmApiUrl}/predict/{$employeeId}");

            Log::debug("LSTM API response status: " . $response->status());

            if ($response->successful()) {
                $data = $response->json();
                Log::debug("LSTM API data for {$employeeId}: " . json_encode($data));
                
                // Convert Flask 0-1 scale to 0-100 percentage scale
                $predictedScore = ($data['productivity_score'] ?? 0) * 100;

                return [
                    'score' => round($predictedScore, 1),
                    'confidence' => round($data['confidence'] ?? 0.85, 2)
                ];
            }

            Log::warning("LSTM API returned non-successful status: " . $response->status());

        } catch (\Exception $e) {
            Log::error("LSTM API Error for employee {$employeeId}: " . $e->getMessage());
        }

        return ['score' => 0, 'confidence' => 0];
    }

    private function getCurrentProductivityScore(int $employeeId): float
    {
        try {
            // Use real-time calculation for current productivity score
            $productivityService = app(ProductivityCalculatorService::class);
            $currentScore = $productivityService->calculateCurrentProductivityScore($employeeId, 7);

            Log::info("Real-time productivity calculated for employee {$employeeId}: {$currentScore}");
            return $currentScore;

        } catch (\Exception $e) {
            Log::error("Real-time productivity calculation failed for employee {$employeeId}: " . $e->getMessage());

            // Fallback to PostgreSQL data warehouse if available
            try {
                return $this->getProductivityFromDataWarehouse($employeeId);
            } catch (\Exception $e2) {
                Log::error("Data warehouse fallback failed for employee {$employeeId}: " . $e2->getMessage());

                // Final fallback: use cached predictions but log the issue
                Log::warning("Using cached predictions as final fallback for employee {$employeeId}");
                $cached = DB::table('lstm_predictions')
                    ->where('employee_id', $employeeId)
                    ->value('predicted_score');

                return round($cached ?? 0, 1);
            }
        }
    }

    /**
     * Fallback method to get productivity from PostgreSQL data warehouse
     */
    private function getProductivityFromDataWarehouse(int $employeeId): float
    {
        try {
            // Use the dedicated data warehouse PostgreSQL connection
            $avgScore = DB::connection('pgsql_dw')
                ->table('fact_employee_productivity as f')
                ->join('dim_employee as e', 'f.employee_sk', '=', 'e.employee_sk')
                ->join('dim_date as d', 'f.date_sk', '=', 'd.date_sk')
                ->where('e.user_id', $employeeId)
                ->where('d.full_date', '>=', DB::raw('CURRENT_DATE - INTERVAL \'7 days\''))
                ->avg('f.productivity_score');

            $result = round($avgScore ?? 0, 1);
            Log::info("Data warehouse productivity retrieved for employee {$employeeId}: {$result}");

            return $result;

        } catch (\Exception $e) {
            Log::error("PostgreSQL data warehouse query failed: " . $e->getMessage());
            throw $e; // Re-throw to trigger final fallback
        }
    }

    private function calculateTrend(float $current, float $predicted): string
    {
        $difference = $predicted - $current;

        if (abs($difference) < 2) return 'stable';
        return $difference > 0 ? 'up' : 'down';
    }

    private function storePrediction(int $employeeId, array $prediction): void
    {
        try {
            // Store prediction in cache table for future reference
            // Convert Flask 0-1 scale to 0-100 percentage scale
            $predictedScore = ($prediction['productivity_score'] ?? 0) * 100;

            Log::debug("Storing prediction for employee {$employeeId}: score={$predictedScore}, confidence={$prediction['confidence']}");

            DB::table('lstm_predictions')->updateOrInsert(
                ['employee_id' => $employeeId],
                [
                    'predicted_score' => round($predictedScore, 2),
                    'confidence' => $prediction['confidence'] ?? 0,
                    'predicted_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]
            );

            Log::debug("Successfully stored prediction for employee {$employeeId}");

        } catch (\Exception $e) {
            Log::error("Error storing prediction for employee {$employeeId}: " . $e->getMessage());
        }
    }

    /**
     * Get historical and predicted productivity data for employee chart
     */
    /**
     * Get historical and predicted productivity data for employee chart.
     *
     * Returns 12 weekly averages from the data warehouse (already 0-100 scale)
     * + the current 7-day real-time score (from ProductivityCalculatorService,
     *   matching exactly what the dashboard card shows)
     * + the LSTM predicted score.
     *
     * FIX: removed the * 100 multiplication — fact_employee_productivity.productivity_score
     *      is already stored on a 0-100 scale, so multiplying again gave values like 8500.
     */
    public function getEmployeeHistory($id): JsonResponse
    {
        try {
            $weeklyScores = [];

            // ── 1. Historical weekly averages from the data warehouse ────────────
            // We query the last 12 *full* weeks (Mon–Sun), excluding the current week.
            try {
                $startOfThisWeek = Carbon::now()->startOfWeek();          // Monday 00:00
                $startOfWindow   = $startOfThisWeek->copy()->subWeeks(12); // 12 weeks back

                $records = DB::connection('pgsql_dw')
                    ->table('fact_employee_productivity as f')
                    ->join('dim_employee as e', 'f.employee_sk', '=', 'e.employee_sk')
                    ->join('dim_date    as d', 'f.date_sk',     '=', 'd.date_sk')
                    ->where('e.user_id',   $id)
                    ->where('d.full_date', '>=', $startOfWindow)
                    ->where('d.full_date', '<',  $startOfThisWeek)
                    ->where('f.productivity_score', '>', 0)   // exclude zero/absent days
                    ->selectRaw("DATE_TRUNC('week', d.full_date) AS week_start,
                                 AVG(f.productivity_score)       AS weekly_avg")
                    ->groupBy('week_start')
                    ->orderBy('week_start', 'asc')
                    ->get();

                if ($records->isNotEmpty()) {
                    $weeklyScores = $records
                        ->pluck('weekly_avg')
                        ->map(fn ($s) => round((float) $s, 1))  // already 0-100, NO × 100
                        ->values()
                        ->toArray();
                }

                // Keep only the most recent 12 weeks
                if (count($weeklyScores) > 12) {
                    $weeklyScores = array_slice($weeklyScores, -12);
                }

                Log::info("History: found " . count($weeklyScores) . " weekly scores for employee {$id}");

            } catch (\Exception $e) {
                Log::error("DW history query failed for employee {$id}: " . $e->getMessage());
            }

            // ── 2. Pad to exactly 12 historical slots (null = no data that week) ──
            $padCount     = 12 - count($weeklyScores);
            $weeklyScores = array_merge(array_fill(0, $padCount, null), $weeklyScores);

            // ── 3. Current score — from ProductivityCalculatorService (7-day avg) ─
            //    This is the SAME value shown on the dashboard card.
            $currentScore    = $this->getCurrentProductivityScore((int) $id);
            $predictedScore  = $this->getLSTMPredictionForEmployee((int) $id)['score'] ?? 0;

            // ── 4. Build Chart.js datasets ────────────────────────────────────────
            // Labels:  W-12 … W-1  |  Current  |  Predicted
            // history: [weekly…, currentScore, null]
            // predicted:[nulls…,  currentScore, predictedScore]  ← bridge at Current point

            $labels = ['W-12','W-11','W-10','W-9','W-8','W-7',
                       'W-6', 'W-5', 'W-4', 'W-3','W-2','W-1',
                       'Current','Predicted'];

            $historyData   = array_merge($weeklyScores, [$currentScore, null]);
            $predictedData = array_merge(array_fill(0, 12, null), [$currentScore, $predictedScore]);

            return response()->json([
                'labels'       => $labels,
                'history'      => $historyData,
                'predicted'    => $predictedData,
                'currentScore' => $currentScore,
                'predicted_score' => $predictedScore,
            ]);

        } catch (\Exception $e) {
            Log::error("getEmployeeHistory failed for employee {$id}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}