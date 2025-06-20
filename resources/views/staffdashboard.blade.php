@extends('layouts.app')

@section('content')

@role('staff')
<div class="container-fluid px-0" style="background:#2563eb;">
    <div class="container py-3 d-flex align-items-center justify-content-between">
        <div>
            <span class="h4 text-white fw-bold">Task Management</span>
            <span class="badge bg-primary ms-2" style="background:#377dff;">STAFF</span>
        </div>
        <div>
            <a href="{{ route('staff.dashboard') }}" class="text-white me-4">Dashboard</a>
            <a href="{{ route('tasks.staff.index') }}" class="text-white me-4">My Tasks</a>
            <a href="{{ route('team.overview') }}" class="text-white me-4">Team</a>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-danger">Logout</button>
            </form>
        </div>
    </div>
</div>

<div class="container py-4">
    <h1 class="mb-2 fw-bold">Staff Dashboard</h1>
    <p class="mb-4">Welcome to your task portal. Create tasks, track progress, and collaborate with your team.</p>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-primary border-2" style="border-top:4px solid #2563eb;">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#377dff;"><i class="bi bi-plus-square"></i></div>
                    <div class="fw-bold mb-2">Create Task</div>
                    <div class="mb-3 text-secondary">Add a new task, assign members, and set deadlines.</div>
                    <a href="{{ route('tasks.create') }}" class="btn w-100" style="background:#2563eb;color:#fff;">
                        <i class="bi bi-plus-circle"></i> New Task
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-purple border-2" style="border-top:4px solid #a259f7;">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#a259f7;"><i class="bi bi-list-task"></i></div>
                    <div class="fw-bold mb-2">My Tasks</div>
                    <div class="mb-3 text-secondary">View and manage all your assigned tasks and statuses.</div>
                    <a href="{{ route('tasks.staff.index') }}" class="btn w-100" style="background:#a259f7;color:#fff;">
                        <i class="bi bi-eye"></i> View Tasks
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-success border-2" style="border-top:4px solid #00b96b;">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#00b96b;"><i class="bi bi-people"></i></div>
                    <div class="fw-bold mb-2">Team Overview</div>
                    <div class="mb-3 text-secondary">Check team members, task distribution, and roles.</div>
                    <a href="{{ route('team.overview') }}" class="btn w-100" style="background:#00b96b;color:#fff;">
                        <i class="bi bi-search"></i> View Team
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Your Upcoming Tasks</strong>
            <a href="{{ route('tasks.staff.index') }}" class="small text-primary">
                View all <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        @forelse($tasks as $task)
            <div class="card mb-2">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div>
                        <div class="fw-bold">{{ $task->title }}</div>
                        <div class="text-secondary">Due: {{ $task->due_date }}</div>
                        <a href="{{ route('tasks.show', $task->id) }}" class="text-primary me-3">View Details</a>
                    </div>
                    <span class="badge rounded-pill 
                        @if($task->status === 'pending') bg-warning text-dark 
                        @elseif($task->status === 'in_progress') bg-info text-dark 
                        @elseif($task->status === 'completed') bg-success 
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                    </span>
                </div>
            </div>
        @empty
            <p class="text-muted">No upcoming tasks found.</p>
        @endforelse
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card p-4">
                <strong class="mb-3 d-block">Recent Activity</strong>
                <div class="mb-2 d-flex align-items-center">
                    <span class="me-2" style="color:#00b96b;"><i class="bi bi-check-circle-fill"></i></span>
                    <span>Task completed</span>
                    <span class="text-secondary ms-2">Team Profile Page</span>
                    <span class="ms-auto text-secondary small">May 18, 2:30 PM</span>
                </div>
                <hr class="my-1">
                <div class="mb-2 d-flex align-items-center">
                    <span class="me-2" style="color:#ff4d4f;"><i class="bi bi-x-circle-fill"></i></span>
                    <span>Task deleted</span>
                    <span class="text-secondary ms-2">Obsolete Campaign Plan</span>
                    <span class="ms-auto text-secondary small">May 17, 9:15 AM</span>
                </div>
                <hr class="my-1">
                <div class="mb-2 d-flex align-items-center">
                    <span class="me-2" style="color:#377dff;"><i class="bi bi-plus-circle-fill"></i></span>
                    <span>Task created</span>
                    <span class="text-secondary ms-2">Write Report</span>
                    <span class="ms-auto text-secondary small">May 16, 11:45 AM</span>
                </div>
            </div>
        </div>
    </div>
</div>
@else
    <div class="container py-4">
        <h3 class="text-danger">Access Denied</h3>
        <p>You do not have permission to view this page.</p>
    </div>
@endrole

@endsection
