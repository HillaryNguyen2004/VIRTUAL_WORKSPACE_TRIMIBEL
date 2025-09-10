<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Services\TaskService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }


    public function create()
    {
        $staffUsers = User::role('staff')->get();
        return view('tasks.create', compact('staffUsers'));
    }

    public function store(StoreTaskRequest $request)
    {
        $this->taskService->createTask($request->formatted());
        return redirect()->route('tasks.create')->with('success', __('messages.task_created'));
    }

    public function show($id)
    {
        $task = $this->taskService->getTaskById($id);
        return view('tasks.create', compact('task'));
    }

    public function edit($id)
    {
        $task = $this->taskService->getTaskById($id);
        $staffUsers = User::role('staff')->get();
        return view('tasks.edit', compact('task', 'staffUsers'));
    }

    public function update(UpdateTaskRequest $request, $id)
    {
        $this->taskService->updateTask($id, $request->formatted());
        if (auth()->user()->hasRole('admin')) {
            return redirect()->route('tasks.index')->with('success',  __('messages.task_updated'));
        }

            return redirect()->route('tasks.staff.index')->with('success',  __('messages.task_updated'));
        // return redirect()->route('tasks.index')->with('success', 'Task updated successfully!');
    }

    // public function destroy($id)
    // {
    //     $this->taskService->deleteTask($id);
    //     if (request()->has('redirect_to') && request('redirect_to') === 'staff') {
    //         return redirect()->route('staff.tasks.index')
    //             ->with('success', 'Task deleted successfully');
    //     }
    
    //     return redirect()->route('tasks.index')
    //     ->with('success', 'Task deleted successfully');
    //     // return redirect()->route('tasks.index')->with('success', __('messages.task_deleted'));
    // }

    // public function destroy($id)
    // {
    //     $this->taskService->deleteTask($id);

    //     $redirectTo = request('redirect_to');

    //     if ($redirectTo === 'staff') {
    //         return redirect()->route('staff.tasks.index')
    //             ->with('success', __('messages.task_deleted'));
    //     }

    //     // Default → admin
    //     return redirect()->route('tasks.index')
    //         ->with('success', __('messages.task_deleted'));
    // }


    // App\Http\Controllers\TaskController.php
public function destroy(\Illuminate\Http\Request $request, $id)
{
    // Delete via your service/repo
    $this->taskService->deleteTask($id);

    // Client hint from Blade:
    $hint = $request->input('redirect_to', 'tasks.index');

    // (Optional but safer) Enforce server-side by role:
    if (auth()->user()->hasRole('staff')) {
        return back()->with('success', __('admin_task.deleted_success'));
    }

    if ($hint === 'back') {
        return back()->with('success', __('admin_task.deleted_success'));
    }

    return redirect()->route($hint)->with('success', __('admin_task.deleted_success'));
}




    public function index(Request $request)
    {
        $tasks = $this->taskService->getFilteredTasks($request);
        $allUsers = User::role('staff')->get();

        return view('tasks.index', compact('tasks', 'allUsers'));
    }


    // THIS PART IS FOR STAFF 
    public function staffTasks(Request $request)
    {
        $tasks = $this->taskService->getTasksForStaff($request, auth()->id());
        return view('tasks.staff.index', compact('tasks'));
    }

    // public function upcomingTasks()
    // {
    //     $tasks = $this->taskService->getUpcomingTasks(auth()->id());
    //     return view('staffdashboard', compact('tasks'));
    // }
//     public function upcomingTasks()
// {
//     $tasks = $this->taskService->getAllUpcomingTasks();
//     return view('staffdashboard', compact('tasks'));
// }
public function upcomingTasks(Request $request)
{
    // Use the same logic as staff index page
    $tasks = $this->taskService->getTasksForStaff($request, auth()->id());
    return view('staffdashboard', compact('tasks'));
}



// public function updateStatus(Request $request, $id)
// {
//     $request->validate([
//         'status' => 'required|in:pending,in_progress,completed',
//         'percentage' => 'nullable|integer|min:0|max:100',
//     ]);

//     $task = $this->taskService->updateStatus(
//         $id,
//         $request->status,
//         $request->percentage
//     );

//     if (!$task) {
//         return response()->json(['success' => false, 'message' => 'Task not found'], 404);
//     }

//     return response()->json([
//         'success' => true,
//         'message' => 'Task status updated successfully',
//         'task' => $task
//     ]);
// }

public function updateStatus(Request $request, $id)
{
    try {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'percentage' => 'nullable|integer|min:0|max:100',
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $e->errors(),
        ], 422);
    }

    $task = $this->taskService->updateStatus(
        $id,
        $request->status,
        $request->percentage
    );

    if (!$task) {
        return response()->json([
            'success' => false,
            'message' => 'Task not found'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Task status updated successfully',
        'task'    => $task
    ]);
}




}
