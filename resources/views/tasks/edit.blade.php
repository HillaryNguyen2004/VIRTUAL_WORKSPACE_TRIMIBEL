@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">{{ __('task_edit.title') }}</h1>
    <form action="{{ route('tasks.update', $task) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card p-4 mb-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('task_edit.task_name_label') }} *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $task->title) }}" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label fw-bold">{{ __('task_edit.assignee_label') }} *</label>
                    <select name="assignee" class="form-control" required>
                        <option value="">{{ __('task_edit.select_assignee') }}</option>
                        @foreach($staffUsers as $user)
                            <option value="{{ $user->id }}" {{ $task->assigned_user_id == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('task_edit.due_date_label') }} *</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $task->due_date) }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('task_edit.description_label') }}</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $task->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('task_edit.status_label') }}</label>
                <select name="status" class="form-control" required>
                    <option value="pending" {{ $task->status == 'pending' ? 'selected' : '' }}>{{ __('task_edit.status_pending') }}</option>
                    <option value="in_progress" {{ $task->status == 'in_progress' ? 'selected' : '' }}>{{ __('task_edit.status_in_progress') }}</option>
                    <option value="completed" {{ $task->status == 'completed' ? 'selected' : '' }}>{{ __('task_edit.status_completed') }}</option>
                </select>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="active" id="active" value="1" {{ $task->active ? 'checked' : '' }}>
                <label class="form-check-label" for="active">
                    {{ __('task_edit.active_label') }}
                </label>
            </div>
            <div class="d-flex justify-content-end">
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary me-2">{{ __('task_edit.cancel_button') }}</a>
                <button type="submit" class="btn btn-primary" style="background:#2563eb;border:none;">
                    <i class="bi bi-save"></i> {{ __('task_edit.save_button') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection