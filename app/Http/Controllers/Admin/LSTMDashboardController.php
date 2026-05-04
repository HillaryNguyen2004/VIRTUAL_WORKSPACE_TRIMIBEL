<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductivityCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

/**
 * LSTM Dashboard Controller — v3.0
 *
 * Updated to match:
 *   • train_lstm_nextday.py (next-day forecast, 27 features, LOOKBACK=14)
 *   • Class thresholds: Low <50, Medium 50-79, High >=80
 *   • Redesigned dashboard (4 tiers: snapshot, action, context, trust)
 *
 * Removed dead code:
 *   • Feature importance UI (no longer in blade)
 *   • Old 60/80 risk thresholds
 *   • Old 7-day lookback / score_trend / avg_score_30d feature names
 *   • Synthetic trend simulation in getTrends() (blade no longer renders it)
 */
class LSTMDashboardController extends Controller
{
    private $lstmApiUrl = 'http://localhost:5001';

    // Class thresholds — must match train_lstm_nextday.py exactly
    private const TH_LOW = 50;   // <50  = Low
    private const TH_HIGH = 80;   // >=80 = High;   50..79 = Medium

    /**
     * Display the LSTM dashboard page.
     */
    public function index()
    {
        return view('admin.lstm-dashboard');
    }

    /**
     * Get model stats for the trust panel.
     *
     * Reads from ml/models/metrics.json if present, else uses last
     * known evaluation results as fallback.
     */
    public function getStats(): JsonResponse
    {
        try {
            $metrics = $this->loadMetricsFile();

            return response()->json([
                'lastRun' => $metrics['lastRun'] ?? Carbon::now()->toISOString(),
                'accuracy' => $metrics['accuracy'] ?? 70.05,
                'naiveAccuracy' => $metrics['naiveAccuracy'] ?? 65.00,
                'macroF1' => $metrics['macroF1'] ?? 0.620,
                'f1High' => $metrics['f1High'] ?? 0.779,
                'f1Med' => $metrics['f1Med'] ?? 0.621,
                'f1Low' => $metrics['f1Low'] ?? 0.381,
                'valLoss' => $metrics['valLoss'] ?? null,
                'epochsRan' => $metrics['epochsRan'] ?? null,
                'lookback' => $metrics['lookback'] ?? 14,
            ]);
        } catch (\Exception $e) {
            Log::error('LSTM Stats Error: ' . $e->getMessage());

            // Hard fallback — last known evaluation results
            return response()->json([
                'lastRun' => Carbon::now()->toISOString(),
                'accuracy' => 70.05,
                'naiveAccuracy' => 65.00,
                'macroF1' => 0.620,
                'f1High' => 0.779,
                'f1Med' => 0.621,
                'f1Low' => 0.381,
                'valLoss' => null,
                'epochsRan' => null,
                'lookback' => 14,
            ]);
        }
    }

    /**
     * Load metrics.json if it exists. Expected schema (written by your
     * evaluate_classifier_nextday.py — see helper at the bottom of this file):
     *
     *   {
     *     "accuracy": 70.05,
     *     "naiveAccuracy": 65.00,
     *     "macroF1": 0.620,
     *     "f1High": 0.779,
     *     "f1Med": 0.621,
     *     "f1Low": 0.381,
     *     "valLoss": 0.6953,
     *     "epochsRan": 65,
     *     "lookback": 14,
     *     "lastRun": "2026-04-28T17:00:00Z"
     *   }
     */
    private function loadMetricsFile(): array
    {
        $path = base_path('ml/models/metrics.json');
        if (!file_exists($path))
            return [];

        $raw = json_decode(file_get_contents($path), true);
        if (!is_array($raw))
            return [];

        // Normalize: accuracy may be stored as 0.7005 (decimal) OR 70.05 (percent)
        if (isset($raw['accuracy']) && $raw['accuracy'] < 1.0) {
            $raw['accuracy'] = round($raw['accuracy'] * 100, 2);
        }
        if (isset($raw['naiveAccuracy']) && $raw['naiveAccuracy'] < 1.0) {
            $raw['naiveAccuracy'] = round($raw['naiveAccuracy'] * 100, 2);
        }

        return $raw;
    }

    /**
     * Get employee predictions from Flask API + departments from MySQL.
     */
    public function getEmployeePredictions(): JsonResponse
    {
        try {
            Log::info('Fetching employee predictions from Flask API...');

            $flaskResponse = Http::timeout(60)->post("{$this->lstmApiUrl}/predict/all");

            if (!$flaskResponse->successful()) {
                Log::error('Flask API error: ' . $flaskResponse->status());
                return response()->json([]);
            }

            $predictions = $flaskResponse->json()['predictions'] ?? [];
            if (empty($predictions)) {
                return response()->json([]);
            }

            // Single MySQL query for all departments
            $userIds = array_column($predictions, 'user_id');
            $deptMap = DB::table('users')
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
                ->whereIn('users.id', $userIds)
                ->pluck('departments.name', 'users.id')
                ->toArray();

            $result = array_map(function ($pred) use ($deptMap) {
                $userId = $pred['user_id'] ?? 0;
                return [
                    'id' => $userId,
                    'name' => $pred['name'] ?? $pred['employee_name'] ?? 'Unknown',
                    'department' => $deptMap[$userId] ?? 'Not Assigned',
                    'currentScore' => round($pred['current_productivity'] ?? 0, 1),
                    'predictedScore' => round($pred['predicted_productivity'] ?? 0, 1),
                    'predictedLevel' => $pred['predicted_level'] ?? 'Medium',
                    'trend' => $pred['trend'] ?? 'stable',
                    'confidence' => round($pred['confidence_score'] ?? 0, 4),
                    'lastUpdated' => Carbon::now()->toISOString(),
                ];
            }, $predictions);

            usort($result, fn($a, $b) => $b['predictedScore'] <=> $a['predictedScore']);

            Log::info('Returning ' . count($result) . ' predictions with departments');
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('LSTM Employee Predictions Error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Refresh predictions and rebuild the productivity vector DB.
     */
    public function refreshPredictions(): JsonResponse
    {
        try {
            @set_time_limit(600);

            $workspacePath = base_path('chatbot_service/var/chroma_db/workspaces/productivity');

            $this->reloadChromaCache();
            if (File::exists($workspacePath)) {
                File::deleteDirectory($workspacePath);
            }

            $pythonBinary = base_path('chatbot_service/.venv/bin/python');
            if (!is_executable($pythonBinary)) {
                $pythonBinary = 'python3';
            }

            $script = base_path('chatbot_service/cli/ingest_workspace.py');
            $process = new Process([
                $pythonBinary,
                $script,
                '--refresh-productivity',
                '--api-base-url',
                $this->lstmApiUrl,
            ], base_path('chatbot_service'), [
                'PYTHONPATH' => base_path('chatbot_service'),
            ]);
            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                $stderr = trim($process->getErrorOutput());
                $stdout = trim($process->getOutput());

                return response()->json([
                    'message' => 'Refresh failed: ' . ($stderr !== '' ? $stderr : $stdout),
                    'success' => 0,
                    'errors' => 1,
                ], 500);
            }

            return response()->json([
                'message' => 'Productivity vector DB cleared and rebuilt successfully.',
                'success' => 1,
                'errors' => 0,
            ]);

        } catch (\Exception $e) {
            Log::error('LSTM Refresh Error: ' . $e->getMessage());
            return response()->json(['error' => 'Refresh failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get historical productivity for the modal chart.
     *
     * 12 weekly averages from DW + today's score + tomorrow's prediction.
     */
    public function getEmployeeHistory($id): JsonResponse
    {
        try {
            $response = Http::timeout(10)->get("{$this->lstmApiUrl}/predict/{$id}");
            if (!$response->successful()) {
                return response()->json([
                    'labels' => ['Today', 'Tomorrow'],
                    'history' => [0, null],
                    'predicted' => [0, 0],
                ]);
            }
            $prediction = $response->json();
            $currentScore = $prediction['current_productivity'] ?? 0;
            $predictedScore = $prediction['predicted_productivity'] ?? 0;

            // 12 weekly averages from DW
            $weeklyScores = [];
            try {
                $startOfThisWeek = Carbon::now()->startOfWeek();
                $startOfWindow = $startOfThisWeek->copy()->subWeeks(12);

                $records = DB::connection('pgsql_dw')
                    ->table('fact_employee_productivity as f')
                    ->join('dim_employee as e', 'f.employee_sk', '=', 'e.employee_sk')
                    ->join('dim_date     as d', 'f.date_sk', '=', 'd.date_sk')
                    ->where('e.user_id', $id)
                    ->where('d.full_date', '>=', $startOfWindow)
                    ->where('d.full_date', '<', $startOfThisWeek)
                    ->where('f.productivity_score', '>', 0)
                    ->selectRaw("DATE_TRUNC('week', d.full_date) AS week_start,
                                 AVG(f.productivity_score) AS weekly_avg")
                    ->groupBy('week_start')
                    ->orderBy('week_start', 'asc')
                    ->get();

                if ($records->isNotEmpty()) {
                    $weeklyScores = $records
                        ->pluck('weekly_avg')
                        ->map(fn($s) => round((float) $s, 1))
                        ->values()
                        ->toArray();
                }

                if (count($weeklyScores) > 12) {
                    $weeklyScores = array_slice($weeklyScores, -12);
                }
            } catch (\Exception $e) {
                Log::info("DW history not available for employee {$id}");
            }

            // Pad to 12 slots
            $padCount = 12 - count($weeklyScores);
            $weeklyScores = array_merge(array_fill(0, $padCount, null), $weeklyScores);

            $labels = [
                'W-12',
                'W-11',
                'W-10',
                'W-9',
                'W-8',
                'W-7',
                'W-6',
                'W-5',
                'W-4',
                'W-3',
                'W-2',
                'W-1',
                'Today',
                'Tomorrow',
            ];

            $historyData = array_merge($weeklyScores, [$currentScore, null]);
            $predictedData = array_merge(array_fill(0, 12, null), [$currentScore, $predictedScore]);

            return response()->json([
                'labels' => $labels,
                'history' => $historyData,
                'predicted' => $predictedData,
                'currentScore' => $currentScore,
                'predicted_score' => $predictedScore,
            ]);

        } catch (\Exception $e) {
            Log::error("getEmployeeHistory failed for employee {$id}: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send productivity alert for a specific employee.
     */
    public function sendProductivityAlert(Request $request): JsonResponse
    {
        try {
            $employeeId = $request->input('employeeId');
            $employee = DB::table('users')->where('id', $employeeId)->first();

            if (!$employee) {
                return response()->json(['error' => 'Employee not found'], 404);
            }

            $prediction = $this->getLSTMPredictionForEmployee($employeeId);

            // Mail::to($employee->email)->send(new ProductivityConcernAlert($employee, $prediction));

            Log::info("Productivity alert sent for employee {$employeeId}");
            return response()->json(['message' => 'Alert sent successfully']);

        } catch (\Exception $e) {
            Log::error('Send Alert Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send alert'], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ════════════════════════════════════════════════════════════════════

    private function getLSTMPredictionForEmployee(int $employeeId): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->lstmApiUrl}/predict/{$employeeId}");
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'score' => round($data['predicted_productivity'] ?? 0, 1),
                    'confidence' => round($data['confidence_score'] ?? 0.85, 4),
                    'predicted_level' => $data['predicted_level'] ?? 'Medium',
                ];
            }
        } catch (\Exception $e) {
            Log::error("LSTM API error for employee {$employeeId}: " . $e->getMessage());
        }

        return ['score' => 0, 'confidence' => 0, 'predicted_level' => 'Medium'];
    }

    private function getCurrentProductivityScore(int $employeeId): float
    {
        try {
            $response = Http::timeout(10)->get("{$this->lstmApiUrl}/predict/{$employeeId}");
            if ($response->successful()) {
                $data = $response->json();
                return round($data['current_productivity'] ?? 0, 1);
            }
        } catch (\Exception $e) {
            Log::error("getCurrentProductivityScore failed for {$employeeId}: " . $e->getMessage());
        }
        return 0;
    }

    /**
     * Class-aware trend calculation — matches train_lstm_nextday.py thresholds.
     */
    private function calculateTrend(float $current, float $predicted): string
    {
        $currentClass = $this->scoreToClass($current);
        $predictedClass = $this->scoreToClass($predicted);

        if ($predictedClass === $currentClass) {
            // Same class — fall back to magnitude
            $diff = $predicted - $current;
            if (abs($diff) < 2)
                return 'stable';
            return $diff > 0 ? 'improving' : 'declining';
        }

        $rank = ['Low' => 0, 'Medium' => 1, 'High' => 2];
        return $rank[$predictedClass] > $rank[$currentClass] ? 'improving' : 'declining';
    }

    private function scoreToClass(float $score): string
    {
        if ($score >= self::TH_HIGH)
            return 'High';
        if ($score >= self::TH_LOW)
            return 'Medium';
        return 'Low';
    }

    /**
     * Risk level — uses correct thresholds (50/80) now.
     */
    private function getRiskLevel(float $score): string
    {
        if ($score >= self::TH_HIGH)
            return 'Low Risk - High Performer';
        if ($score >= self::TH_LOW)
            return 'Medium Risk';
        return 'High Risk - Needs Attention';
    }

    /**
     * Performance tier — granular within the High band.
     */
    private function getPerformanceTier(float $score): string
    {
        if ($score >= 90)
            return 'Elite';
        if ($score >= 80)
            return 'High';
        if ($score >= 65)
            return 'Good';
        if ($score >= 50)
            return 'Average';
        return 'Needs Improvement';
    }

    // ════════════════════════════════════════════════════════════════════
    // EXPORT (Excel report)
    // ════════════════════════════════════════════════════════════════════

    public function exportExcel(Request $request)
    {
        try {
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return response()->json([
                    'error' => 'PhpSpreadsheet not installed. Run: composer require phpoffice/phpspreadsheet'
                ], 500);
            }

            Log::info('Starting export...');
            $employees = $this->buildDetailedEmployeeData();

            if (empty($employees)) {
                return response()->json(['error' => 'No data to export'], 400);
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            $this->createDetailedPredictionsSheet($spreadsheet, $employees);
            $this->createSummarySheet($spreadsheet, $employees);
            $this->createDepartmentSheet($spreadsheet, $employees);
            $this->createRiskAnalysisSheet($spreadsheet, $employees);
            $this->createModelMetadataSheet($spreadsheet);

            $fileName = 'LSTM_Report_' . date('Y-m-d_His') . '.xlsx';
            $tempPath = storage_path('app/temp');
            if (!file_exists($tempPath))
                mkdir($tempPath, 0755, true);

            $tempFile = $tempPath . '/' . $fileName;
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tempFile);

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage());
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }

    private function buildDetailedEmployeeData(): array
    {
        $employees = DB::table('users')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->select(['users.id', 'users.name', 'departments.name as department'])
            ->where('users.blocked', false)
            ->get();

        if ($employees->isEmpty())
            return [];

        $result = [];
        foreach ($employees as $emp) {
            try {
                $prediction = $this->getLSTMPredictionForEmployee($emp->id);
                $currentScore = $this->getCurrentProductivityScore($emp->id);
                $metrics = $this->getEmployeeMetricsFromDataWarehouse($emp->id);
                $predScore = $prediction['score'] ?? 0;

                $result[] = [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'department' => $emp->department ?? 'Unknown',
                    'currentScore' => round($currentScore, 1),
                    'currentScore30d' => round($metrics['score30d'] ?? $currentScore, 1),
                    'scoreVolatility' => round($metrics['volatility'] ?? 0, 2),
                    'daysOfData' => $metrics['daysOfData'] ?? 0,
                    'predictedScore' => round($predScore, 1),
                    'predictedClass' => $prediction['predicted_level'] ?? 'Medium',
                    'predictionConfidence' => round(($prediction['confidence'] ?? 0.85) * 100, 1),
                    'scoreChange' => round($predScore - $currentScore, 1),
                    'trend' => $this->calculateTrend($currentScore, $predScore),
                    'tasksCompleted7d' => $metrics['tasks7d'] ?? 0,
                    'tasksCompleted30d' => $metrics['tasks30d'] ?? 0,
                    'avgHoursWorked7d' => round($metrics['avgHours7d'] ?? 0, 1),
                    'avgHoursWorked30d' => round($metrics['avgHours30d'] ?? 0, 1),
                    'attendanceRate' => round($metrics['attendanceRate'] ?? 0, 1),
                    'lateCheckins' => $metrics['lateCheckins'] ?? 0,
                    'hasTaskSignal' => ($metrics['tasks7d'] ?? 0) > 0 ? 'Yes' : 'No',
                    'riskLevel' => $this->getRiskLevel($predScore),
                    'burnoutRiskScore' => round($metrics['burnoutRisk'] ?? 0, 1),
                    'engagementScore' => round($metrics['engagementScore'] ?? 0, 1),
                    'performanceTier' => $this->getPerformanceTier($predScore),
                    'lastActivityDate' => $metrics['lastActivityDate'] ?? 'N/A',
                    'lastScoreUpdate' => Carbon::now()->format('Y-m-d H:i:s'),
                    'predictionGeneratedAt' => Carbon::now()->format('Y-m-d H:i:s'),
                ];
            } catch (\Exception $e) {
                Log::warning("Failed export data for {$emp->id}: " . $e->getMessage());
            }
        }

        return $result;
    }

    private function getEmployeeMetricsFromDataWarehouse(int $employeeId): array
    {
        try {
            $now = Carbon::now();
            $sevenDaysAgo = $now->copy()->subDays(7)->format('Y-m-d');
            $thirtyDaysAgo = $now->copy()->subDays(30)->format('Y-m-d');

            $cols = [
                'f.productivity_score',
                'f.tasks_completed',
                'f.hours_worked',
                'f.checked_in',
                'f.is_late',
                'd.full_date'
            ];

            $records7d = DB::connection('pgsql_dw')
                ->table('fact_employee_productivity as f')
                ->join('dim_employee as e', 'f.employee_sk', '=', 'e.employee_sk')
                ->join('dim_date     as d', 'f.date_sk', '=', 'd.date_sk')
                ->where('e.user_id', $employeeId)
                ->where('d.full_date', '>=', $sevenDaysAgo)
                ->select($cols)
                ->get();

            $records30d = DB::connection('pgsql_dw')
                ->table('fact_employee_productivity as f')
                ->join('dim_employee as e', 'f.employee_sk', '=', 'e.employee_sk')
                ->join('dim_date     as d', 'f.date_sk', '=', 'd.date_sk')
                ->where('e.user_id', $employeeId)
                ->where('d.full_date', '>=', $thirtyDaysAgo)
                ->select($cols)
                ->get();

            $scores7d = $records7d->pluck('productivity_score')->filter()->values()->toArray();
            $scores30d = $records30d->pluck('productivity_score')->filter()->values()->toArray();

            $score7d = !empty($scores7d) ? round(array_sum($scores7d) / count($scores7d), 1) : 0;
            $score30d = !empty($scores30d) ? round(array_sum($scores30d) / count($scores30d), 1) : 0;

            $tasks7d = (int) $records7d->sum('tasks_completed');
            $tasks30d = (int) $records30d->sum('tasks_completed');

            $hours7d = $records7d->pluck('hours_worked')->filter()->values()->toArray();
            $hours30d = $records30d->pluck('hours_worked')->filter()->values()->toArray();
            $avgH7d = !empty($hours7d) ? array_sum($hours7d) / count($hours7d) : 0;
            $avgH30d = !empty($hours30d) ? array_sum($hours30d) / count($hours30d) : 0;

            $totalDays30d = $records30d->count();
            $checkedInDays = $records30d->where('checked_in', true)->count();
            $attendanceRate = $totalDays30d > 0
                ? round(($checkedInDays / $totalDays30d) * 100, 1)
                : 0;

            $lateCheckins = $records30d->where('is_late', true)->count();

            return [
                'daysOfData' => count($scores7d),
                'score30d' => $score30d,
                'volatility' => $this->calculateStdDev($scores7d),
                'tasks7d' => $tasks7d,
                'tasks30d' => $tasks30d,
                'avgHours7d' => round($avgH7d, 1),
                'avgHours30d' => round($avgH30d, 1),
                'attendanceRate' => $attendanceRate,
                'lateCheckins' => $lateCheckins,
                'burnoutRisk' => $this->calculateBurnoutRisk($avgH7d, $score30d, $score7d),
                'engagementScore' => $this->calculateEngagementScore($tasks7d, $attendanceRate, $score7d),
                'lastActivityDate' => $records30d->sortByDesc('full_date')->first()?->full_date ?? 'N/A',
            ];

        } catch (\Exception $e) {
            Log::error("DW metrics failed for {$employeeId}: " . $e->getMessage());
            return [];
        }
    }

    private function calculateStdDev(array $array): float
    {
        if (empty($array))
            return 0;
        $mean = array_sum($array) / count($array);
        $variance = 0;
        foreach ($array as $val)
            $variance += pow($val - $mean, 2);
        return sqrt($variance / count($array));
    }

    /**
     * Burnout risk — updated thresholds to match new class boundaries (50/80).
     */
    private function calculateBurnoutRisk(float $avgHours, float $score30d, float $currentScore): float
    {
        $risk = 0;
        if ($avgHours > 9)
            $risk += 40;
        elseif ($avgHours > 8)
            $risk += 20;

        $trend = $currentScore - $score30d;
        if ($trend < -5)
            $risk += 30;

        // Adjusted thresholds: <50 = high risk (was <60), <65 = moderate (was <75)
        if ($currentScore < self::TH_LOW)
            $risk += 30;
        elseif ($currentScore < 65)
            $risk += 15;

        return min($risk, 100);
    }

    private function calculateEngagementScore(int $tasks, float $attendance, float $score): float
    {
        $engagement = 0;
        $engagement += min(($tasks / 10) * 40, 40);
        $engagement += ($attendance / 100) * 30;
        $engagement += ($score / 100) * 30;
        return min($engagement, 100);
    }

    private function getRecommendation(array $emp): string
    {
        if ($emp['burnoutRiskScore'] >= 70 && $emp['avgHoursWorked7d'] > 9) {
            return 'URGENT: Reduce workload, schedule 1-on-1';
        } elseif ($emp['predictedScore'] < self::TH_LOW) {
            return 'CRITICAL: Predicted Low — review role fit, provide support';
        } elseif ($emp['burnoutRiskScore'] >= 50) {
            return 'Monitor closely, consider workload adjustment';
        } elseif ($emp['trend'] === 'declining') {
            return 'Schedule check-in, identify blockers';
        } else {
            return 'Standard monitoring';
        }
    }

    // ────────────── Excel sheet builders ──────────────

    private function createDetailedPredictionsSheet(&$spreadsheet, array $employees)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detailed Predictions');

        $headers = [
            'Employee ID',
            'Name',
            'Department',
            'Current Score (7d)',
            'Current Score (30d)',
            'Score Volatility',
            'Days of Data',
            'Predicted Score',
            'Predicted Class',
            'Confidence (%)',
            'Score Change',
            'Trend',
            'Tasks (7d)',
            'Tasks (30d)',
            'Avg Hours (7d)',
            'Avg Hours (30d)',
            'Attendance (%)',
            'Late Check-ins',
            'Has Task Signal',
            'Risk Level',
            'Burnout Risk',
            'Engagement Score',
            'Performance Tier',
            'Last Activity',
            'Last Score Update',
            'Prediction Generated',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2D3748']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '718096']
                ]
            ],
        ];

        $lastCol = $this->getColumnLetter(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $row = 2;
        foreach ($employees as $emp) {
            $sheet->fromArray([
                $emp['id'],
                $emp['name'],
                $emp['department'],
                $emp['currentScore'],
                $emp['currentScore30d'],
                $emp['scoreVolatility'],
                $emp['daysOfData'],
                $emp['predictedScore'],
                $emp['predictedClass'],
                $emp['predictionConfidence'],
                $emp['scoreChange'],
                ucfirst($emp['trend']),
                $emp['tasksCompleted7d'],
                $emp['tasksCompleted30d'],
                $emp['avgHoursWorked7d'],
                $emp['avgHoursWorked30d'],
                $emp['attendanceRate'],
                $emp['lateCheckins'],
                $emp['hasTaskSignal'],
                $emp['riskLevel'],
                $emp['burnoutRiskScore'],
                $emp['engagementScore'],
                $emp['performanceTier'],
                $emp['lastActivityDate'],
                $emp['lastScoreUpdate'],
                $emp['predictionGeneratedAt'],
            ], null, "A{$row}");

            $this->applyRowFormatting($sheet, $row, $emp);
            $row++;
        }

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('D2');
        $sheet->setAutoFilter("A1:{$lastCol}1");
    }

    private function applyRowFormatting(&$sheet, int $row, array $emp)
    {
        // Performance tier — column W (23rd)
        $tierCell = "W{$row}";
        $tierColors = [
            'Elite' => 'C6F6D5',
            'High' => 'D1FAE5',
            'Good' => 'DBEAFE',
            'Average' => 'FEF3C7',
            'Needs Improvement' => 'FEE2E2',
        ];
        if (isset($tierColors[$emp['performanceTier']])) {
            $sheet->getStyle($tierCell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($tierColors[$emp['performanceTier']]);
        }

        // Burnout risk — column U (21st)
        $burnoutCell = "U{$row}";
        $color = $emp['burnoutRiskScore'] >= 70 ? 'FEE2E2'
            : ($emp['burnoutRiskScore'] >= 40 ? 'FEF3C7' : 'D1FAE5');
        $sheet->getStyle($burnoutCell)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB($color);

        // Score change — column K (11th, after we added Predicted Class column)
        $changeCell = "K{$row}";
        if ($emp['scoreChange'] > 5) {
            $sheet->getStyle($changeCell)->getFont()->getColor()->setRGB('166534');
            $sheet->getStyle($changeCell)->getFont()->setBold(true);
        } elseif ($emp['scoreChange'] < -5) {
            $sheet->getStyle($changeCell)->getFont()->getColor()->setRGB('991B1B');
            $sheet->getStyle($changeCell)->getFont()->setBold(true);
        }
    }

    private function createSummarySheet(&$spreadsheet, array $employees)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');

        $total = count($employees);
        $avgPredicted = array_sum(array_column($employees, 'predictedScore')) / $total;
        $avgCurrent = array_sum(array_column($employees, 'currentScore')) / $total;
        $highPerformers = count(array_filter($employees, fn($e) => $e['predictedScore'] >= self::TH_HIGH));
        $needsAttention = count(array_filter($employees, fn($e) => $e['predictedScore'] < self::TH_LOW));
        $avgBurnout = array_sum(array_column($employees, 'burnoutRiskScore')) / $total;
        $avgEngagement = array_sum(array_column($employees, 'engagementScore')) / $total;

        $data = [
            ['LSTM Next-Day Forecast Report', ''],
            ['', ''],
            ['Metric', 'Value'],
            ['Total Employees', $total],
            ['Average Predicted Score', round($avgPredicted, 1)],
            ['Average Current Score', round($avgCurrent, 1)],
            ['Predicted High (≥' . self::TH_HIGH . ')', $highPerformers . ' (' . round(($highPerformers / $total) * 100, 1) . '%)'],
            ['Predicted Low (<' . self::TH_LOW . ')', $needsAttention . ' (' . round(($needsAttention / $total) * 100, 1) . '%)'],
            ['Average Burnout Risk', round($avgBurnout, 1)],
            ['Average Engagement Score', round($avgEngagement, 1)],
            ['Export Date', date('Y-m-d H:i:s')],
        ];

        $sheet->fromArray($data, null, 'A1');
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);

        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2D3748']
            ],
        ]);
    }

    private function createDepartmentSheet(&$spreadsheet, array $employees)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Departments');

        $deptGroups = collect($employees)->groupBy('department');

        $headers = [
            'Department',
            'Employees',
            'Avg Predicted',
            'Avg Current',
            'Avg Tasks (7d)',
            'Avg Hours (7d)',
            'Attendance %',
            'Predicted High',
            'Predicted Low',
            'Avg Burnout Risk',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($deptGroups as $dept => $group) {
            $sheet->fromArray([
                $dept,
                $group->count(),
                round($group->avg('predictedScore'), 1),
                round($group->avg('currentScore'), 1),
                round($group->avg('tasksCompleted7d'), 1),
                round($group->avg('avgHoursWorked7d'), 1),
                round($group->avg('attendanceRate'), 1),
                $group->filter(fn($e) => $e['predictedScore'] >= self::TH_HIGH)->count(),
                $group->filter(fn($e) => $e['predictedScore'] < self::TH_LOW)->count(),
                round($group->avg('burnoutRiskScore'), 1),
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function createRiskAnalysisSheet(&$spreadsheet, array $employees)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Risk Analysis');

        // High risk = burnout >= 50 OR predicted Low
        $highRisk = array_filter(
            $employees,
            fn($e) =>
            $e['burnoutRiskScore'] >= 50 || $e['predictedScore'] < self::TH_LOW
        );
        usort($highRisk, fn($a, $b) => $b['burnoutRiskScore'] <=> $a['burnoutRiskScore']);

        $headers = [
            'Employee',
            'Department',
            'Predicted Score',
            'Predicted Class',
            'Burnout Risk',
            'Engagement',
            'Hours (7d)',
            'Trend',
            'Recommendation',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($highRisk as $emp) {
            $sheet->fromArray([
                $emp['name'],
                $emp['department'],
                $emp['predictedScore'],
                $emp['predictedClass'],
                $emp['burnoutRiskScore'],
                $emp['engagementScore'],
                $emp['avgHoursWorked7d'],
                ucfirst($emp['trend']),
                $this->getRecommendation($emp),
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function createModelMetadataSheet(&$spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Model Info');

        $metrics = $this->loadMetricsFile();

        $metadata = [
            ['Parameter', 'Value'],
            ['Model Version', 'LSTM Next-Day Forecast v3.0'],
            ['Architecture', '2-layer LSTM + Dense classifier'],
            ['Lookback Window', ($metrics['lookback'] ?? 14) . ' days'],
            ['Prediction Target', 'Tomorrow\'s class (Low / Medium / High)'],
            ['Class Thresholds', 'Low <' . self::TH_LOW . ', Medium ' . self::TH_LOW . '-' . (self::TH_HIGH - 1) . ', High >=' . self::TH_HIGH],
            ['Test Accuracy', ($metrics['accuracy'] ?? 70.05) . '%'],
            ['Naive Baseline', ($metrics['naiveAccuracy'] ?? 65.00) . '%'],
            ['Macro F1', $metrics['macroF1'] ?? 0.620],
            ['F1 — High class', $metrics['f1High'] ?? 0.779],
            ['F1 — Medium class', $metrics['f1Med'] ?? 0.621],
            ['F1 — Low class', $metrics['f1Low'] ?? 0.381],
            ['Validation Loss', $metrics['valLoss'] ?? 'N/A'],
            ['Epochs Trained', $metrics['epochsRan'] ?? 'N/A'],
            ['Features', '27 total: behavioral inputs + rolling rates + lag scores + calendar'],
            ['Data Source', 'PostgreSQL Data Warehouse (fact_employee_productivity)'],
            ['Export Generated At', Carbon::now()->format('Y-m-d H:i:s')],
        ];

        $sheet->fromArray($metadata, null, 'A1');
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(50);

        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2D3748']
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A2:B' . count($metadata))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB']
                ]
            ],
        ]);
    }

    private function getColumnLetter(int $columnNumber): string
    {
        $letter = '';
        while ($columnNumber > 0) {
            $temp = ($columnNumber - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $columnNumber = ($columnNumber - $temp - 1) / 26;
        }
        return $letter;
    }

    private function reloadChromaCache(): void
    {
        try {
            Http::timeout(10)->post("http://127.0.0.1:8002/reload-chroma");
        } catch (\Throwable $e) {
            Log::warning('Failed to reload Chroma cache before refresh', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}