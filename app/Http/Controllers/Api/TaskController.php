<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Move task to a different phase
     */
    public function moveToPhase(Request $request, Task $task)
    {
        $validated = $request->validate([
            'phase_id' => 'required|exists:phases,id',
        ]);

        try {
            $task->update([
                'phase_id' => $validated['phase_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task moved successfully',
                'data' => $task->refresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder tasks within a phase
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'integer|exists:tasks,id',
            'phase_id' => 'required|exists:phases,id',
        ]);

        try {
            foreach ($validated['task_ids'] as $order => $taskId) {
                Task::where('id', $taskId)->update([
                    'phase_id' => $validated['phase_id'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tasks reordered successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update task status
     */
    public function updateStatus(Request $request, Task $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'percentage' => 'nullable|integer|min:0|max:100',
        ]);

        try {
            $task->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Task status updated',
                'data' => $task->refresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
}
