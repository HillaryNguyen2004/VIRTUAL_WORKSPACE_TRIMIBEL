@extends('layouts.app')

@section('content')
@role('staff')
<div class="container py-4">
    <h2 class="mb-4 fw-bold">Edit My Assigned Task</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('tasks.update', $task->task_id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="title" class="form-label fw-bold">Task Title</label>
            <input type="text" name="title" id="title" class="form-control"
                value="{{ old('title', $task->title) }}" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label fw-bold">Description</label>
            <textarea name="description" id="description" rows="4" class="form-control">{{ old('description', $task->description) }}</textarea>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label fw-bold">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="pending" {{ $task->status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>

        {{-- You can allow tags, but usually not change assignees or due_date for staff --}}
        <div class="mb-3">
            <label for="tags" class="form-label fw-bold">Tags (comma-separated)</label>
            <input type="text" name="tags" id="tags" class="form-control"
                value="{{ old('tags', implode(', ', json_decode($task->tags ?? '[]', true))) }}">
        </div>

        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary">Update Task</button>

            {{-- Delete form --}}
            @can('task delete')
                <form action="{{ route('tasks.destroy', $task->task_id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this task?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        Delete Task
                    </button>
                </form>
            @endcan
        </div>
    </form>
</div>
@else
<div class="container py-4">
    <h3 class="text-danger">Access Denied</h3>
    <p>You do not have permission to view this page.</p>
</div>
@endrole
@endsection
