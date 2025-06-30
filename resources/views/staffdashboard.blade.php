@extends('layouts.app')

@section('content')

@role('staff')
<div class="container-fluid px-0" style="background:#2563eb;">
    <div class="container py-3 d-flex align-items-center justify-content-between">
        <div>
            <span class="h4 text-white fw-bold">{{ __('staff_dashboard.task_management') }}</span>
            <span class="badge bg-primary ms-2" style="background:#377dff;">{{ __('staff_dashboard.staff') }}</span>
        </div>

        <div class="d-flex align-items-center">
            <a href="{{ route('staff.dashboard') }}" class="text-white me-4">{{ __('staff_dashboard.dashboard') }}</a>
            <a href="{{ route('tasks.staff.index') }}" class="text-white me-4">{{ __('staff_dashboard.my_tasks') }}</a>
            <a href="{{ route('team.overview') }}" class="text-white me-4">{{ __('staff_dashboard.team') }}</a>

            {{-- 🌐 Language Switcher --}}
            @php $currentLocale = app()->getLocale(); @endphp
            @if ($currentLocale === 'en')
                <a class="text-white text-decoration-none me-2" href="{{ route('lang.switch', 'vi') }}">
                    🇻🇳 <span class="d-none d-md-inline">{{ __('staff_dashboard.vietnamese') }}</span>
                </a>
            @elseif ($currentLocale === 'vi')
                <a class="text-white text-decoration-none me-2" href="{{ route('lang.switch', 'en') }}">
                    🇺🇸 <span class="d-none d-md-inline">{{ __('staff_dashboard.english') }}</span>
                </a>
            @endif
        </div>
    </div>
</div>

<div class="container py-4">
    <h1 class="mb-2 fw-bold">{{ __('staff_dashboard.staff_dashboard') }}</h1>
    <p class="mb-4">{{ __('staff_dashboard.welcome_message') }}</p>

    <div class="row mb-4">

        {{-- ✅ Permission check: Only show Create Task if staff has all permissions --}}
        @php
            $hasPermissions = auth()->user()->can('task.create');
        @endphp

        @if($hasPermissions)
        <div class="col-md-4 mb-3">
            <div class="card border-primary border-2" style="border-top:4px solid #2563eb;">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#377dff;"><i class="bi bi-plus-square"></i></div>
                    <div class="fw-bold mb-2">{{ __('staff_dashboard.create_task') }}</div>
                    <div class="mb-3 text-secondary">{{ __('staff_dashboard.create_task_description') }}</div>
                    <a href="{{ route('tasks.create') }}" class="btn w-100" style="background:#2563eb;color:#fff;">
                        <i class="bi bi-plus-circle"></i> {{ __('staff_dashboard.new_task') }}
                    </a>
                </div>
            </div>
        </div>
        @endif

        <div class="col-md-4 mb-3">
            <div class="card border-purple border-2" style="border-top:4px solid #a259f7;">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#a259f7;"><i class="bi bi-list-task"></i></div>
                    <div class="fw-bold mb-2">{{ __('staff_dashboard.my_tasks') }}</div>
                    <div class="mb-3 text-secondary">{{ __('staff_dashboard.my_tasks_description') }}</div>
                    <a href="{{ route('tasks.staff.index') }}" class="btn w-100" style="background:#a259f7;color:#fff;">
                        <i class="bi bi-eye"></i> {{ __('staff_dashboard.view_tasks') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-success border-2" style="border-top:4px solid #00b96b;">
                <div class="card-body">
                    <div class="mb-2" style="font-size:2rem; color:#00b96b;"><i class="bi bi-people"></i></div>
                    <div class="fw-bold mb-2">{{ __('staff_dashboard.team_overview') }}</div>
                    <div class="mb-3 text-secondary">{{ __('staff_dashboard.team_overview_description') }}</div>
                    <a href="{{ route('team.overview') }}" class="btn w-100" style="background:#00b96b;color:#fff;">
                        <i class="bi bi-search"></i> {{ __('staff_dashboard.view_team') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>{{ __('staff_dashboard.upcoming_tasks') }}</strong>
            <a href="{{ route('tasks.staff.index') }}" class="small text-primary">
                {{ __('staff_dashboard.view_all') }} <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        @forelse($tasks as $task)
            <div class="card mb-2">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div>
                        <div class="fw-bold">{{ $task->title }}</div>
                        <div class="text-secondary">{{ __('staff_dashboard.due_date') }}: {{ $task->due_date }}</div>
                        <a href="{{ route('tasks.show', $task->id) }}" class="text-primary me-3">{{ __('staff_dashboard.view_details') }}</a>
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
            <p class="text-muted">{{ __('staff_dashboard.no_upcoming_tasks') }}</p>
        @endforelse
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card p-4">
                <strong class="mb-3 d-block">{{ __('staff_dashboard.recent_activity') }}</strong>
                <div class="mb-2 d-flex align-items-center">
                    <span class="me-2" style="color:#00b96b;"><i class="bi bi-check-circle-fill"></i></span>
                    <span>{{ __('staff_dashboard.task_completed') }}</span>
                    <span class="text-secondary ms-2">{{ __('staff_dashboard.team_profile_page') }}</span>
                    <span class="ms-auto text-secondary small">{{ __('staff_dashboard.activity_date_1') }}</span>
                </div>
                <hr class="my-1">
                <div class="mb-2 d-flex align-items-center">
                    <span class="me-2" style="color:#ff4d4f;"><i class="bi bi-x-circle-fill"></i></span>
                    <span>{{ __('staff_dashboard.task_deleted') }}</span>
                    <span class="text-secondary ms-2">{{ __('staff_dashboard.obsolete_campaign_plan') }}</span>
                    <span class Johansen-auto text-secondary small">{{ __('staff_dashboard.activity_date_2') }}</span>
                </div>
                <hr class="my-1">
                <div class="mb-2 d-flex align-items-center">
                    <span class="me-2" style="color:#377dff;"><i class="bi bi-plus-circle-fill"></i></span>
                    <span>{{ __('staff_dashboard.task_created') }}</span>
                    <span class="text-secondary ms-2">{{ __('staff_dashboard.write_report') }}</span>
                    <span class="ms-auto text-secondary small">{{ __('staff_dashboard.activity_date_3') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@else
    <div class="container py-4">
        <h3 class="text-danger">{{ __('staff_dashboard.access_denied') }}</h3>
        <p>{{ __('staff_dashboard.no_permission') }}</p>
    </div>
@endrole

@endsection