@extends('layouts.app')
@section('header')
    @include('partials.headers.admin')
@endsection
@section('content')

@role('admin')
<div class="container py-4">
    <h1 class="mb-2 fw-bold">{{ __('admin_dashboard.admin_dashboard') }}</h1>
    <p class="mb-4">{{ __('admin_dashboard.welcome_message') }}</p>

    <div class="row mb-4">
        <!-- Cards: Pending Tasks / Active Projects / Total Users -->
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#377dff;"><i class="bi bi-list-task"></i></div>
                    <div class="fw-bold text-secondary">{{ __('admin_dashboard.pending_tasks') }}</div>
                    <div class="h4 mb-0">5</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#00b96b;"><i class="bi bi-kanban"></i></div>
                    <div class="fw-bold text-secondary">{{ __('admin_dashboard.active_projects') }}</div>
                    <div class="h4 mb-0">3/4</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#a259f7;"><i class="bi bi-people"></i></div>
                    <div class="fw-bold text-secondary">{{ __('admin_dashboard.total_users') }}</div>
                    <div class="h4 mb-0">12</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Task & Permission Management -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>{{ __('admin_dashboard.task_management') }}</strong>
                        <a href="{{ route('tasks.index') }}" class="small text-primary">{{ __('admin_dashboard.view_all') }} <i class="bi bi-list"></i></a>
                    </div>
                    <div class="text-secondary mb-2">{{ __('admin_dashboard.task_management_description') }}</div>
                    <ul class="mb-3 ps-3">
                        <li class="mb-1">{{ __('admin_dashboard.task_management_item_1') }}</li>
                        <li class="mb-1">{{ __('admin_dashboard.task_management_item_2') }}</li>
                        <li class="mb-1">{{ __('admin_dashboard.task_management_item_3') }}</li>
                    </ul>
                    <a href="{{ route('tasks.index') }}" class="btn btn-primary w-100" style="background:#2563eb;border:none;">
                        <i class="bi bi-check2-circle"></i> {{ __('admin_dashboard.edit_tasks') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>{{ __('admin_dashboard.permission_management') }}</strong>
                        <a href="{{ route('admin.permissions') }}" class="small text-primary">{{ __('admin_dashboard.view_all') }} <i class="bi bi-list"></i></a>
                    </div>
                    <div class="text-secondary mb-2">{{ __('admin_dashboard.permission_management_description') }}</div>
                    <ul class="mb-3 ps-3">
                        <li class="mb-1">{{ __('admin_dashboard.permission_management_item_1') }}</li>
                    </ul>
                    <a href="{{ route('admin.permissions') }}" class="btn w-100" style="background:#00b96b;color:#fff;border:none;">
                        <i class="bi bi-folder-plus"></i> {{ __('admin_dashboard.edit_permissions') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- User Management -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>{{ __('admin_dashboard.user_management') }}</strong>
                        <a href="{{ route('users.index') }}" class="small text-primary">{{ __('admin_dashboard.view_all') }} <i class="bi bi-list"></i></a>
                    </div>
                    <div class="text-secondary mb-2">{{ __('admin_dashboard.user_management_description') }}</div>
                    <ul class="mb-3 ps-3">
                        <li class="mb-1">{{ __('admin_dashboard.user_management_item_1') }}</li>
                        <li class="mb-1">{{ __('admin_dashboard.user_management_item_2') }}</li>
                        <li class="mb-1">{{ __('admin_dashboard.user_management_item_3') }}</li>
                    </ul>
                    <a href="{{ route('admin.users.create') }}" class="btn w-100" style="background:#a259f7;color:#fff;border:none;">
                        <i class="bi bi-person-plus"></i> {{ __('admin_dashboard.add_new_user') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Task Submissions -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <strong>{{ __('admin_dashboard.recent_task_submissions') }}</strong>
                    <table class="table table-sm mt-3">
                        <thead>
                            <tr>
                                <th>{{ __('admin_dashboard.id') }}</th>
                                <th>{{ __('admin_dashboard.user') }}</th>
                                <th>{{ __('admin_dashboard.task') }}</th>
                                <th>{{ __('admin_dashboard.deadline') }}</th>
                                <th>{{ __('admin_dashboard.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>
                                    <td>{{ $log->user->name ?? 'N/A' }}</td>
                                    <td>{{ $log->action }}</td>
                                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="badge rounded-pill bg-info text-dark">
                                            {{ \Illuminate\Support\Str::limit($log->description, 30) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        {{ __('admin_dashboard.no_activity_logs') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <a href="{{ route('admin.activity.logs') }}" class="small text-primary">{{ __('admin_dashboard.view_all_tasks') }} <i class="bi bi-list"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card p-3 shadow-sm border-0">
                <strong>{{ __('admin_dashboard.quick_actions') }}</strong>
                <div class="row mt-3">
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('tasks.index') }}" class="btn btn-outline-primary w-100"><i class="bi bi-check2-square"></i> {{ __('admin_dashboard.review_tasks') }}</a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('tasks.create') }}" class="btn btn-outline-success w-100"><i class="bi bi-folder-plus"></i> {{ __('admin_dashboard.new_task') }}</a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-people"></i> {{ __('admin_dashboard.manage_users') }}</a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="#" class="btn btn-outline-info w-100"><i class="bi bi-bar-chart"></i> {{ __('admin_dashboard.view_reports') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Stats -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card p-3 shadow-sm border-0">
                <div class="row text-center">
                    <div class="col-md-4 mb-2">
                        <div class="fw-bold">{{ __('admin_dashboard.todays_tasks') }}</div>
                        <div class="h4">7</div>
                        <a href="#" class="small text-primary">{{ __('admin_dashboard.view_details') }} <i class="bi bi-list"></i></a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="fw-bold">{{ __('admin_dashboard.project_completion') }}</div>
                        <div class="h4">75%</div>
                        <a href="#" class="small text-primary">{{ __('admin_dashboard.view_progress') }} <i class="bi bi-bar-chart"></i></a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="fw-bold">{{ __('admin_dashboard.unassigned_tasks') }}</div>
                        <div class="h4">3</div>
                        <a href="#" class="small text-primary">{{ __('admin_dashboard.assign_now') }} <i class="bi bi-person-lines-fill"></i></a>
                    </div>
                </div>
                <div class="text-end text-muted mt-2">
                    {{ __('admin_dashboard.current_date') }}
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="container py-4">
    <h3 class="text-danger">{{ __('admin_dashboard.access_denied') }}</h3>
    <p>{{ __('admin_dashboard.no_permission') }}</p>
</div>
@endrole

@endsection
