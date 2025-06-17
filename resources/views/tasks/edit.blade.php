@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">Edit Task</h1>
    <form action="{{ route('tasks.update', $task->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card p-4 mb-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Task Name *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $task->title) }}" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label fw-bold">Assignee *</label>
                    <select name="assignee" class="form-control" required>
                        <option value="">Select staff...</option>
                        @foreach($staffUsers as $user)
                            <option value="{{ $user->id }}" {{ $task->assigned_user_id == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Due Date *</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $task->due_date) }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $task->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-control" required>
                    <option value="pending" {{ $task->status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ $task->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ $task->status == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="active" id="active" value="1" {{ $task->active ? 'checked' : '' }}>
                <label class="form-check-label" for="active">
                    Task is active and visible
                </label>
            </div>
            <div class="d-flex justify-content-end">
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary" style="background:#2563eb;border:none;">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection