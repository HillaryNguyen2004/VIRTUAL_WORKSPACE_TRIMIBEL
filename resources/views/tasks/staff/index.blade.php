@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">My Tasks</h1>

    {{-- Search & Filter Bar --}}
    <form method="GET" action="{{ route('tasks.staff.index') }}" class="card p-4 mb-4">
        <div class="row align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label fw-bold">Search by Task Name</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Enter task name"
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label fw-bold">Filter by Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel-fill me-1"></i> Apply Filter
                </button>
            </div>
        </div>
    </form>

    {{-- Task Table --}}
    <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>All Tasks Assigned to My Team</strong>
        </div>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>TASK ID</th>
                    <th>TASK NAME</th>
                    <th>DUE DATE</th>
                    <th>STATUS</th>
                    <th>ACTIVE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr>
                    <td>{{ $task->task_id }}</td>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->due_date }}</td>
                    <td>
                        @if($task->status === 'pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($task->status === 'in_progress')
                            <span class="badge bg-info text-dark">In Progress</span>
                        @elseif($task->status === 'completed')
                            <span class="badge bg-success">Completed</span>
                        @endif
                    </td>
                    <td>
                        @if($task->active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#taskDetails{{ $task->task_id }}" aria-expanded="false" aria-controls="taskDetails{{ $task->task_id }}">
                            <i class="bi bi-eye text-primary fs-5"></i>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="p-0 border-0">
                        <div class="collapse" id="taskDetails{{ $task->task_id }}">
                            <div class="p-3 bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Description:</strong>
                                        <div>{{ $task->description ?? 'No description' }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Tags:</strong>
                                        <div>
                                            @if(!empty($task->tags))
                                                @foreach(json_decode($task->tags, true) as $tag)
                                                    <span class="badge bg-primary">{{ $tag }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">No tags</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <strong>Assigned Users:</strong>
                                <ul>
                                    @forelse($task->assignedUsers as $user)
                                        <li>{{ $user->name }} ({{ $user->email }})</li>
                                    @empty
                                        <li class="text-muted">No users assigned</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No tasks found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection