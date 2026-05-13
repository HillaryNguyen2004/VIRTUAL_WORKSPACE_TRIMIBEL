<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Repositories\TaskRepositoryInterface;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_create_task_throws_validation_exception_when_due_date_is_before_start_date(): void
    {
        $repo = Mockery::mock(TaskRepositoryInterface::class);
        $repo->shouldNotReceive('create');

        $service = new TaskService($repo);

        $this->expectException(ValidationException::class);

        $service->createTask([
            'title'      => 'Overdue Task',
            'project_id' => null,
            'start_date' => '2026-06-15',
            'due_date'   => '2026-06-10', // before start
        ]);
    }

    public function test_update_task_throws_validation_exception_when_due_date_is_before_start_date(): void
    {
        $repo = Mockery::mock(TaskRepositoryInterface::class);

        $task             = new Task();
        $task->id         = 1;
        $task->start_date = '2026-05-01';
        $task->due_date   = '2026-05-30';
        $task->project_id = null;

        $repo->shouldReceive('find')->with(1)->andReturn($task);

        $service = new TaskService($repo);

        $this->expectException(ValidationException::class);

        $service->updateTask(1, [
            'start_date' => '2026-06-20',
            'due_date'   => '2026-06-01', // before new start date
        ]);
    }

    public function test_update_task_throws_when_new_due_date_before_existing_start(): void
    {
        $repo = Mockery::mock(TaskRepositoryInterface::class);

        $task             = new Task();
        $task->id         = 2;
        $task->start_date = '2026-07-01';
        $task->due_date   = '2026-07-30';
        $task->project_id = null;

        $repo->shouldReceive('find')->with(2)->andReturn($task);

        $service = new TaskService($repo);

        $this->expectException(ValidationException::class);

        // only updating due_date to before existing start_date
        $service->updateTask(2, [
            'due_date' => '2026-06-15',
        ]);
    }

    public function test_delete_task_returns_false_when_task_not_found(): void
    {
        $repo = Mockery::mock(TaskRepositoryInterface::class);
        $repo->shouldReceive('find')->with(999)->andReturnNull();

        $service = new TaskService($repo);

        $result = $service->deleteTask(999);

        $this->assertFalse($result);
    }
}
