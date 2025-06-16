@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-2 fw-bold">Admin Dashboard</h1>
    <p class="mb-4">Welcome to the Task Management & User Administration Panel.</p>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#377dff;"><i class="bi bi-list-task"></i></div>
                    <div class="fw-bold text-secondary">Pending Tasks</div>
                    <div class="h4 mb-0">5</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#00b96b;"><i class="bi bi-kanban"></i></div>
                    <div class="fw-bold text-secondary">Active Projects</div>
                    <div class="h4 mb-0">3/4</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#a259f7;"><i class="bi bi-people"></i></div>
                    <div class="fw-bold text-secondary">Total Users</div>
                    <div class="h4 mb-0">12</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Task Management</strong>
                        <a href="#" class="small text-primary">View All <i class="bi bi-list"></i></a>
                    </div>
                    <div class="text-secondary mb-2">Review and manage tasks assigned to your team members.</div>
                    <ul class="mb-3 ps-3">
                        <li class="mb-1">Assign tasks to users based on project priorities</li>
                        <li class="mb-1">Remove or archive completed or outdated tasks</li>
                        <li class="mb-1">Track deadlines and task completion progress</li>
                    </ul>
                    <a href="#" class="btn btn-primary w-100" style="background:#2563eb;border:none;">
                        <i class="bi bi-check2-circle"></i> Edit Tasks
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Permission Management</strong>
                        <a href="#" class="small text-primary">View All <i class="bi bi-list"></i></a>
                    </div>
                    <div class="text-secondary mb-2">Organize Staff Permission.</div>
                    <ul class="mb-3 ps-3">
                        <li class="mb-1">Grant or delete a staff permission</li>
                    </ul>
                    <a href="#" class="btn w-100" style="background:#00b96b;color:#fff;border:none;">
                        <i class="bi bi-folder-plus"></i> Edit Permissions
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>User Management</strong>
                        <a href="#" class="small text-primary">View All <i class="bi bi-list"></i></a>
                    </div>
                    <div class="text-secondary mb-2">Manage team members and their access roles.</div>
                    <ul class="mb-3 ps-3">
                        <li class="mb-1">View user profiles and assigned tasks</li>
                        <li class="mb-1">Change roles (admin, staff, user)</li>
                        <li class="mb-1">Track last activity and login time</li>
                    </ul>
                    <a href="#" class="btn w-100" style="background:#a259f7;color:#fff;border:none;">
                        <i class="bi bi-person-plus"></i> Add New User
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <strong>Recent Task Submissions</strong>
                    <table class="table table-sm mt-3">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>USER</th>
                                <th>TASK</th>
                                <th>DEADLINE</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>TK0098</td>
                                <td>John Doe</td>
                                <td>Design Landing Page</td>
                                <td>June 18, 2025<br>5:00 PM</td>
                                <td>
                                    <span class="badge rounded-pill" style="background:#ffe066;color:#856404;">PENDING</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <a href="#" class="small text-primary">View All Tasks <i class="bi bi-list"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card p-3 shadow-sm border-0">
                <strong>Quick Actions</strong>
                <div class="row mt-3">
                    <div class="col-md-3 mb-2">
                        <a href="#" class="btn btn-outline-primary w-100"><i class="bi bi-check2-square"></i> Review Tasks</a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('tasks.create') }}" class="btn btn-outline-success w-100"><i class="bi bi-folder-plus"></i> New Task</a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="#" class="btn btn-outline-secondary w-100"><i class="bi bi-people"></i> Manage Users</a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="#" class="btn btn-outline-info w-100"><i class="bi bi-bar-chart"></i> View Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card p-3 shadow-sm border-0">
                <div class="row text-center">
                    <div class="col-md-4 mb-2">
                        <div class="fw-bold">Today's Tasks</div>
                        <div class="h4">7</div>
                        <a href="#" class="small text-primary">View details <i class="bi bi-list"></i></a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="fw-bold">Project Completion</div>
                        <div class="h4">75%</div>
                        <a href="#" class="small text-primary">View progress <i class="bi bi-bar-chart"></i></a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="fw-bold">Unassigned Tasks</div>
                        <div class="h4">3</div>
                        <a href="#" class="small text-primary">Assign now <i class="bi bi-person-lines-fill"></i></a>
                    </div>
                </div>
                <div class="text-end text-muted mt-2">
                    Wednesday, June 18, 2025
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
