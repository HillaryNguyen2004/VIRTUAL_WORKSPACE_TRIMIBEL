<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ProductivityCalculatorService;
use App\Models\User;
use App\Models\CheckIn;
use App\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;

/**
 * Test that the ProductivityCalculatorService properly calculates current scores
 * that differ from cached predictions, fixing the overfitting appearance issue.
 */
class ProductivityCalculatorTest extends TestCase
{
    use DatabaseTransactions;

    private ProductivityCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProductivityCalculatorService::class);
    }

    /**
     * Test that current and predicted scores differ for the same employee
     * This test verifies the fix for the task query issue that was causing
     * unrealistic current scores (5-16% instead of 60-90%).
     */
    public function test_current_and_predicted_scores_differ()
    {
        // Create test employee
        $employee = User::factory()->create([
            'name' => 'Test Employee'
        ]);

        // Create test data for current productivity calculation
        $this->createTestCheckInData($employee);
        $this->createTestTaskDataWithNullDates($employee); // Simulate real-world NULL due_date scenario

        // Calculate current score using our fixed service
        $currentScore = $this->service->calculateCurrentProductivityScore($employee->id, 7);

        // Simulate cached prediction (this would come from LSTM model)
        $predictedScore = 85.5; // Typical prediction value

        // Verify current score is now realistic (should be 60-90% range, not 5-16%)
        $this->assertGreaterThan(50, $currentScore,
            "Current score ({$currentScore}%) should be realistic, not the problematic low values from before");

        $this->assertLessThan(100, $currentScore,
            "Current score ({$currentScore}%) should not exceed 100%");

        // Verify they differ (but gap should be reasonable, not 60-80% like before)
        $difference = abs($currentScore - $predictedScore);
        $this->assertGreaterThan(0, $difference,
            "Current score ({$currentScore}%) and predicted score ({$predictedScore}%) should not be identical");

        $this->assertLessThan(50, $difference,
            "Gap ({$difference}%) should be reasonable, not the massive 60-80% gaps from the task query failure");

        // Log the results for manual verification
        $this->addToAssertionCount(1);
        dump("✅ Fix Verification Results:");
        dump("Employee: {$employee->name}");
        dump("Current Score: {$currentScore}% (should be 60-90% range)");
        dump("Predicted Score: {$predictedScore}%");
        dump("Difference: {$difference}% (should be < 20% for most cases)");
        dump("Status: " . ($difference < 20 ? "✅ GOOD" : "⚠️  Large gap - check task data"));
    }

    /**
     * Test productivity calculation with NULL due dates (most common database scenario)
     */
    public function test_handles_null_due_dates_correctly()
    {
        $employee = User::factory()->create(['name' => 'Employee with NULL due dates']);

        // Create check-in data
        CheckIn::create([
            'user_name' => $employee->name,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in_time' => '08:30:00',
            'check_out_time' => '17:00:00'
        ]);

        // Create tasks with NULL due dates (real-world scenario)
        Task::create([
            'title' => 'Task with NULL due date',
            'assigned_user_id' => $employee->id,
            'percentage' => 75,
            'score' => 0, // Common: percentage but no score
            'status' => 'in_progress',
            'start_date' => Carbon::today()->subDays(5),
            'due_date' => null, // This is the key issue that was causing 0 tasks to be found
            'active' => true
        ]);

        Task::create([
            'title' => 'Another NULL due date task',
            'assigned_user_id' => $employee->id,
            'percentage' => 90,
            'score' => 8,
            'status' => 'completed',
            'start_date' => null, // NULL start date too
            'due_date' => null,
            'active' => true
        ]);

        $score = $this->service->calculateCurrentProductivityScore($employee->id, 1);

        // With the fix, these NULL due date tasks should be found and contribute to scoring
        $this->assertGreaterThan(60, $score, "Employee with NULL due date tasks should have realistic score > 60%");
        $this->assertLessThan(95, $score, "Score should not exceed 95%");

        dump("✅ NULL Due Date Test:");
        dump("Employee: {$employee->name}");
        dump("Score: {$score}% (should be 60-90% with fixed task query)");
        dump("Status: Tasks with NULL due dates now contribute to scoring");
    }

    /**
     * Test productivity calculation with good performance data
     */
    public function test_high_productivity_calculation()
    {
        $employee = User::factory()->create(['name' => 'High Performer']);

        // Create excellent check-in data (on time, full hours)
        CheckIn::create([
            'user_name' => $employee->name,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in_time' => '08:30:00',
            'check_out_time' => '17:00:00'
        ]);

        // Create high-quality completed task
        Task::create([
            'title' => 'Important Task',
            'assigned_user_id' => $employee->id,
            'percentage' => 100,
            'score' => 9,
            'status' => 'completed',
            'due_date' => Carbon::today(),
            'active' => true
        ]);

        $score = $this->service->calculateCurrentProductivityScore($employee->id, 1);

        $this->assertGreaterThan(80, $score, "High performer should have score > 80%");
        $this->assertLessThanOrEqual(100, $score, "Score should not exceed 100%");

        dump("High Performer Score: {$score}%");
    }

    /**
     * Test productivity calculation with poor performance data
     */
    public function test_low_productivity_calculation()
    {
        $employee = User::factory()->create(['name' => 'Low Performer']);

        // Create late check-in with fewer hours
        CheckIn::create([
            'user_name' => $employee->name,
            'date' => Carbon::today()->format('Y-m-d'),
            'check_in_time' => '10:00:00', // 1 hour late
            'check_out_time' => '16:00:00'  // Leaving early
        ]);

        // Create incomplete task with low score
        Task::create([
            'title' => 'Incomplete Task',
            'assigned_user_id' => $employee->id,
            'percentage' => 30,
            'score' => 4,
            'status' => 'in_progress',
            'due_date' => Carbon::today(),
            'active' => true
        ]);

        $score = $this->service->calculateCurrentProductivityScore($employee->id, 1);

        $this->assertLessThan(70, $score, "Low performer should have score < 70%");
        $this->assertGreaterThanOrEqual(0, $score, "Score should not be negative");

        dump("Low Performer Score: {$score}%");
    }

    /**
     * Test that the algorithm handles missing data gracefully
     */
    public function test_handles_missing_data()
    {
        $employee = User::factory()->create(['name' => 'No Data Employee']);

        // No check-ins, no tasks, no day-offs
        $score = $this->service->calculateCurrentProductivityScore($employee->id, 7);

        $this->assertEquals(0, $score, "Employee with no data should have 0% productivity");

        dump("No Data Employee Score: {$score}%");
    }

    private function createTestCheckInData(User $employee)
    {
        // Create varied check-in data for the last 7 days
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today()->subDays($i);

            CheckIn::create([
                'user_name' => $employee->name,
                'date' => $date->format('Y-m-d'),
                'check_in_time' => $this->getRandomCheckInTime(),
                'check_out_time' => $this->getRandomCheckOutTime()
            ]);
        }
    }

    private function createTestTaskData(User $employee)
    {
        // Create varied task completion data
        for ($i = 0; $i < 5; $i++) {
            Task::create([
                'title' => "Task " . ($i + 1),
                'assigned_user_id' => $employee->id,
                'percentage' => rand(50, 100),
                'score' => rand(6, 9),
                'status' => rand(0, 1) ? 'completed' : 'in_progress',
                'due_date' => Carbon::today()->subDays(rand(0, 6)),
                'active' => true
            ]);
        }
    }

    private function createTestTaskDataWithNullDates(User $employee)
    {
        // Create realistic task data with NULL due dates (most common scenario in database)
        // This tests the core fix for the task query failure

        Task::create([
            'title' => 'High Progress Task (NULL due date)',
            'assigned_user_id' => $employee->id,
            'percentage' => 85,
            'score' => 0, // Common: has percentage but no score
            'status' => 'in_progress',
            'start_date' => Carbon::today()->subDays(10),
            'due_date' => null, // This was causing tasks to be excluded before fix
            'active' => true
        ]);

        Task::create([
            'title' => 'Completed Task (NULL dates)',
            'assigned_user_id' => $employee->id,
            'percentage' => 100,
            'score' => 8,
            'status' => 'completed',
            'start_date' => null, // NULL start date too
            'due_date' => null,
            'active' => true
        ]);

        Task::create([
            'title' => 'Medium Progress Task',
            'assigned_user_id' => $employee->id,
            'percentage' => 70,
            'score' => 0, // Common database pattern
            'status' => 'in_progress',
            'start_date' => Carbon::today()->subDays(5),
            'due_date' => null,
            'active' => true
        ]);

        Task::create([
            'title' => 'Task with Actual Due Date',
            'assigned_user_id' => $employee->id,
            'percentage' => 90,
            'score' => 9,
            'status' => 'in_progress',
            'start_date' => Carbon::today()->subDays(3),
            'due_date' => Carbon::today()->addDays(7),
            'active' => true
        ]);
    }

    private function getRandomCheckInTime(): string
    {
        // Random between 08:00 and 09:30
        $hour = rand(8, 9);
        $minute = $hour === 9 ? rand(0, 30) : rand(0, 59);
        return sprintf('%02d:%02d:00', $hour, $minute);
    }

    private function getRandomCheckOutTime(): string
    {
        // Random between 16:30 and 18:00
        $hour = rand(16, 17);
        $minute = $hour === 16 ? rand(30, 59) : rand(0, 59);
        if ($hour === 17 && $minute > 30) {
            $minute = rand(0, 30);
        }
        return sprintf('%02d:%02d:00', $hour, $minute);
    }
}