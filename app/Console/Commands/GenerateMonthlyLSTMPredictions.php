<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateMonthlyLSTMPredictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lstm:generate-predictions
                           {--retrain : Retrain the LSTM model before generating predictions}
                           {--employee-id= : Generate prediction for specific employee ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly LSTM productivity predictions for all employees';

    private $lstmApiUrl = 'http://localhost:5001';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startTime = microtime(true);

        $this->info("Starting LSTM Prediction Generation at " . now()->format('Y-m-d H:i:s'));

        try {
            // Check if LSTM API is available
            if (!$this->checkLSTMAPIHealth()) {
                $this->error("LSTM API is not available at {$this->lstmApiUrl}");
                return Command::FAILURE;
            }

            // Optionally retrain model if requested
            if ($this->option('retrain')) {
                $this->info("Retraining LSTM model...");
                $this->retrainModel();
            }

            // Generate predictions
            $specificEmployeeId = $this->option('employee-id');
            if ($specificEmployeeId) {
                $this->info("Generating prediction for employee ID: {$specificEmployeeId}");
                $result = $this->generatePredictionForEmployee($specificEmployeeId);
            } else {
                $this->info("Generating predictions for all active employees...");
                $result = $this->generatePredictionsForAllEmployees();
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            $this->info("Prediction generation completed in {$executionTime} seconds");
            $this->info("Results: {$result['success']} successful, {$result['errors']} failed");

            // Log completion
            Log::info('LSTM predictions generated', [
                'success_count' => $result['success'],
                'error_count' => $result['errors'],
                'execution_time' => $executionTime,
                'specific_employee' => $specificEmployeeId
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Fatal error during prediction generation: " . $e->getMessage());
            Log::error('LSTM prediction generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    private function checkLSTMAPIHealth(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->lstmApiUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('LSTM API health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function retrainModel(): void
    {
        try {
            $this->line("Fetching latest training data from data warehouse...");

            // Call your ETL process to ensure latest data
            $this->call('etl:sync-productivity-data');

            $this->line("Starting model retraining...");

            // Call LSTM training endpoint (you'll need to add this to your Flask API)
            $response = Http::timeout(300)->post("{$this->lstmApiUrl}/retrain");

            if ($response->successful()) {
                $metrics = $response->json();
                $this->info("Model retrained successfully");
                $this->line("New accuracy: " . ($metrics['accuracy'] ?? 'N/A'));
                $this->line("MSE: " . ($metrics['mse'] ?? 'N/A'));
            } else {
                $this->warn("Model retraining failed: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->warn("Model retraining encountered an error: " . $e->getMessage());
        }
    }

    private function generatePredictionsForAllEmployees(): array
    {
        // Get all active employees (not blocked)
        $employees = DB::table('users')
            ->where('blocked', false)
            ->select('id', 'name', 'username', 'email')
            ->get();

        $successCount = 0;
        $errorCount = 0;
        $progressBar = $this->output->createProgressBar($employees->count());

        $this->line("Processing {$employees->count()} employees:");
        $progressBar->start();

        foreach ($employees as $employee) {
            try {
                $prediction = $this->callLSTMAPI($employee->id);

                if ($prediction) {
                    $this->storePrediction($employee->id, $prediction);
                    $successCount++;
                } else {
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $errorCount++;
                Log::warning("Failed to generate prediction for employee {$employee->id}", [
                    'employee_name' => $employee->name,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return ['success' => $successCount, 'errors' => $errorCount];
    }

    private function generatePredictionForEmployee(int $employeeId): array
    {
        try {
            $employee = DB::table('users')
                ->where('id', $employeeId)
                ->where('blocked', false)
                ->first();

            if (!$employee) {
                $this->error("Employee with ID {$employeeId} not found or inactive");
                return ['success' => 0, 'errors' => 1];
            }

            $prediction = $this->callLSTMAPI($employeeId);

            if ($prediction) {
                $this->storePrediction($employeeId, $prediction);
                $this->info("Prediction generated for {$employee->name}");
                $this->line(" Predicted Score: " . round($prediction['productivity_score'] * 100, 1) . "%");
                return ['success' => 1, 'errors' => 0];
            } else {
                return ['success' => 0, 'errors' => 1];
            }

        } catch (\Exception $e) {
            $this->error("Failed to generate prediction: " . $e->getMessage());
            return ['success' => 0, 'errors' => 1];
        }
    }

    private function callLSTMAPI(int $employeeId): ?array
    {
        try {
            $response = Http::timeout(30)->get("{$this->lstmApiUrl}/predict/{$employeeId}");

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::warning("LSTM API returned error for employee {$employeeId}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error("LSTM API call failed for employee {$employeeId}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function storePrediction(int $employeeId, array $prediction): void
    {
        $predictionDetails = [
            'model_version' => $prediction['model_version'] ?? 'v1.0',
            'features_used' => $prediction['features_used'] ?? [],
            'raw_prediction' => $prediction['productivity_score'] ?? 0,
            'prediction_date' => now()->toDateString()
        ];

        DB::table('lstm_predictions')->updateOrInsert(
            ['employee_id' => $employeeId],
            [
                'predicted_score' => round(($prediction['productivity_score'] ?? 0) * 100, 2),
                'confidence' => round($prediction['confidence'] ?? 0.85, 4),
                'prediction_details' => json_encode($predictionDetails),
                'predicted_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }
}
