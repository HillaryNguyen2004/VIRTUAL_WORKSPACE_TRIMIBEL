@extends('layouts.app')

@section('content')
<div class="container-fluid px-0" style="background:#2563eb;">
    <div class="container py-3 d-flex align-items-center justify-content-between">
        <div>
            <span class="h4 text-white fw-bold">Task Management</span>
            <span class="badge bg-primary ms-2" style="background:#377dff;">STAFF</span>
        </div>
        <div>
            <a href="#" class="text-white me-4">Dashboard</a>
            <a href="#" class="text-white me-4">My Tasks</a>
            <a href="#" class="text-white me-4">Team</a>
            <a href="#" class="btn btn-danger">Logout</a>
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
                    <a href="#" class="btn w-100" style="background:#2563eb;color:#fff;"><i class="bi bi-plus-circle"></i> New Task</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-purple border-2" style="border-top:4px solid #a259f7;">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#a259f7;"><i class="bi bi-list-task"></i></div>
                    <div class="fw-bold mb-2">My Tasks</div>
                    <div class="mb-3 text-secondary">View and manage all your assigned tasks and statuses.</div>
                    <a href="#" class="btn w-100" style="background:#a259f7;color:#fff;"><i class="bi bi-eye"></i> View Tasks</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-success border-2" style="border-top:4px solid #00b96b;">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#00b96b;"><i class="bi bi-people"></i></div>
                    <div class="fw-bold mb-2">Team Overview</div>
                    <div class="mb-3 text-secondary">Check team members, task distribution, and roles.</div>
                    <a href="#" class="btn w-100" style="background:#00b96b;color:#fff;"><i class="bi bi-search"></i> View Team</a>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Your Upcoming Tasks</strong>
            <a href="#" class="small text-primary">View all <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="card mb-2">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <div class="fw-bold">Design Homepage</div>
                    <div class="text-secondary">Due: May 25, 2025</div>
                    <a href="#" class="text-primary me-3">View Details</a>
                    <a href="#" class="text-danger">Mark as Complete</a>
                </div>
                <span class="badge rounded-pill" style="background:#b7f5d8;color:#1a7f37;font-weight:500;">IN PROGRESS</span>
            </div>
        </div>
        <div class="card mb-2">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <div class="fw-bold">Client Meeting Prep</div>
                    <div class="text-secondary">Due: May 27, 2025</div>
                    <a href="#" class="text-primary">View Details</a>
                    <span class="text-secondary ms-3">Pending review</span>
                </div>
                <span class="badge rounded-pill" style="background:#ffe066;color:#856404;font-weight:500;">PENDING</span>
            </div>
        </div>
        <div class="card mb-2">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <div class="fw-bold">Write Report</div>
                    <div class="text-secondary">Due: June 1, 2025</div>
                    <a href="#" class="text-primary">View Details</a>
                    <span class="text-secondary ms-3">Not started</span>
                </div>
                <span class="badge rounded-pill" style="background:#f8d7da;color:#721c24;font-weight:500;">NOT STARTED</span>
            </div>
        </div>
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
@endsection
