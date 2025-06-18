@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">Add New Task</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('tasks.store') }}" method="POST">
        @csrf
        <div class="card p-4 mb-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Task Name *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Prepare Report" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label fw-bold">Assignee *</label>
                    <select name="assignee" class="form-control" required>
                        <option value="">Select staff...</option>
                        @foreach($staffUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Due Date *</label>
                    <input type="date" name="due_date" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Enter task description..."></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Tags</label>
                <input type="text" name="tags[]" class="form-control mb-2" placeholder="Tag 1">
                <a href="#" class="text-primary small" id="add-tag">+ Add Tag</a>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="active" id="active" value="1" checked>
                <label class="form-check-label" for="active">
                    Task is active and visible
                </label>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary" style="background:#2563eb;border:none;">
                    <i class="bi bi-save"></i> Save Task
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('add-tag').addEventListener('click', function (e) {
            e.preventDefault();
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'tags[]';
            input.classList.add('form-control', 'mb-2');
            input.placeholder = 'Another Tag';
            this.before(input);
        });
    });
</script>
@endpush
@endsection
