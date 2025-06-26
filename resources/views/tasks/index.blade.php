@extends('layouts.app')
@section('header')
    @include('partials.headers.admin')
@endsection
@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">Task Management</h1>

    <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>All Tasks</strong>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">+ Add New Task</a>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('tasks.index') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search by Task Name" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="due_date" class="form-control" value="{{ request('due_date') }}">
            </div>
            <div class="col-md-3">
                <select name="assigned_user_id" class="form-select">
                    <option value="">-- Filter by Assignee --</option>
                    @foreach($allUsers as $user)
                        <option value="{{ $user->id }}" {{ request('assigned_user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <select name="sort_by" class="form-select">
                    <option value="">-- Sort By --</option>
                    <option value="title" {{ request('sort_by') == 'title' ? 'selected' : '' }}>Task Name</option>
                    <option value="due_date" {{ request('sort_by') == 'due_date' ? 'selected' : '' }}>Due Date</option>
                </select>
                <button type="submit" class="btn btn-secondary">Apply</button>
            </div>
        </form>

        <table class="table align-middle">
            <thead>
                <tr>
                    <th>TASK ID</th>
                    <th>TASK NAME</th>
                    <th>ASSIGNEE</th>
                    <th>DUE DATE</th>
                    <th>STATUS</th>
                    <th>ACTIVE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                <tr data-bs-toggle="collapse" data-bs-target="#taskDetails{{ $task->task_id }}" aria-expanded="false" style="cursor:pointer;">
                    <td>{{ $task->task_id }}</td>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->assigneeUser?->name ?? 'N/A' }}</td>
                    <td>
                        {{ $task->due_date }}
                        @if(\Carbon\Carbon::parse($task->due_date)->isPast() && $task->status !== 'completed')
                            <span class="badge bg-danger ms-2">Overdue</span>
                        @endif
                    </td>
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
                        <div onclick="event.stopPropagation();">
                            <a href="#" title="View">
                                <i class="bi bi-eye text-primary fs-5"></i>
                            </a>
                            <a href="{{ route('tasks.edit', $task->task_id) }}" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('tasks.destroy', $task->task_id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link p-0 m-0 text-danger" onclick="return confirm('Are you sure?')">
                                    <i class="bi bi-trash fs-5"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <tr class="collapse" id="taskDetails{{ $task->task_id }}">
                    <td colspan="7" class="bg-light">
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
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="d-flex justify-content-center">
            {{ $tasks->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection