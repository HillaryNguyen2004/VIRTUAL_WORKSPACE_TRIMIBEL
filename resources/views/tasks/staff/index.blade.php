@extends('layouts.app')
@section('header')
    @include('partials.headers.staff')
@endsection
@section('content')

@role('staff')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">{{ __('tasks.my_tasks') }}</h1>

    {{-- Search & Filter Bar --}}
    <form method="GET" action="{{ route('tasks.staff.index') }}" class="card shadow-sm p-4 mb-4">
        <div class="row gy-3">
            {{-- Search --}}
            <div class="col-md-4">
                <label for="search" class="form-label fw-semibold text-primary">🔍 {{ __('tasks.search') }}</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="{{ __('tasks.search') }}..." value="{{ request('search') }}">
            </div>

            {{-- Status Filter --}}
            <div class="col-md-3">
                <label for="status" class="form-label fw-semibold text-primary">📋 {{ __('tasks.status') }}</label>
                <select name="status" id="status" class="form-select">
                    <option value="">{{ __('tasks.all_statuses') }}</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('tasks.pending') }}</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>{{ __('tasks.in_progress') }}</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('tasks.completed') }}</option>
                </select>
            </div>

            {{-- Sort --}}
            <div class="col-md-3">
                <label for="sort" class="form-label fw-semibold text-primary">⬇️ {{ __('tasks.sort') }}</label>
                <select name="sort" id="sort" class="form-select">
                    <option value="">{{ __('tasks.default_sort') }}</option>
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>{{ __('tasks.name_asc') }}</option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>{{ __('tasks.name_desc') }}</option>
                    <option value="due_asc" {{ request('sort') == 'due_asc' ? 'selected' : '' }}>{{ __('tasks.due_asc') }}</option>
                    <option value="due_desc" {{ request('sort') == 'due_desc' ? 'selected' : '' }}>{{ __('tasks.due_desc') }}</option>
                </select>
            </div>

            {{-- Submit --}}
            <div class="col-md-2 d-grid align-self-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel-fill me-1"></i> {{ __('tasks.apply') }}
                </button>
            </div>
        </div>
    </form>

    {{-- Task Table --}}
    <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>{{ __('tasks.all_tasks') }}</strong>
        </div>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>{{ __('tasks.task_id') }}</th>
                    <th>{{ __('tasks.task_name') }}</th>
                    <th>{{ __('tasks.due_date') }}</th>
                    <th>{{ __('tasks.status') }}</th>
                    <th>{{ __('tasks.active') }}</th>
                    <th>{{ __('tasks.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr>
                    <td>{{ $task->task_id }}</td>
                    <td>{{ $task->title }}</td>
                    <td>
                        {{ $task->due_date }}
                        @if(\Carbon\Carbon::parse($task->due_date)->isPast() && $task->status !== 'completed')
                            <span class="badge bg-danger ms-2">{{ __('tasks.overdue') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($task->status === 'pending')
                            <span class="badge bg-warning text-dark">{{ __('tasks.pending') }}</span>
                        @elseif($task->status === 'in_progress')
                            <span class="badge bg-info text-dark">{{ __('tasks.in_progress') }}</span>
                        @elseif($task->status === 'completed')
                            <span class="badge bg-success">{{ __('tasks.completed') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($task->active)
                            <span class="badge bg-success">{{ __('tasks.active_yes') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('tasks.active_no') }}</span>
                        @endif
                    </td>
                    <td class="d-flex gap-2">
                        {{-- View button --}}
                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#taskDetails{{ $task->task_id }}" aria-expanded="false" aria-controls="taskDetails{{ $task->task_id }}">
                            <i class="fas fa-eye text-primary fs-5"></i>
                        </button>

                        {{-- Edit button with permission --}}
                        @can('task.edit')
                        <a href="{{ route('tasks.edit', $task->task_id) }}" class="btn btn-sm btn-link">
                            <i class="fas fa-edit text-success fs-5"></i>
                        </a>
                        @endcan

                        {{-- Delete button with permission --}}
                        @can('task.delete')
                        <form action="{{ route('tasks.destroy', $task->task_id) }}" method="POST" onsubmit="return confirm('{{ __('tasks.confirm_delete') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link">
                                <i class="fas fa-trash text-danger fs-5"></i>
                            </button>
                        </form>
                        @endcan
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="p-0 border-0">
                        <div class="collapse" id="taskDetails{{ $task->task_id }}">
                            <div class="p-3 bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>{{ __('tasks.description') }}:</strong>
                                        <div>{{ $task->description ?? __('tasks.no_description') }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>{{ __('tasks.tags') }}:</strong>
                                        <div>
                                            @if(!empty($task->tags))
                                                @foreach(json_decode($task->tags, true) as $tag)
                                                    <span class="badge bg-primary">{{ $tag }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">{{ __('tasks.no_tags') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <strong>{{ __('tasks.assigned_users') }}:</strong>
                                <ul>
                                    @forelse($task->assignedUsers as $user)
                                        <li>{{ $user->name }} ({{ $user->email }})</li>
                                    @empty
                                        <li class="text-muted">{{ __('tasks.no_users') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">{{ __('tasks.no_tasks') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($tasks->hasPages())
            <div class="mt-3 d-flex justify-content-center">
                {{ $tasks->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@else
<div class="container py-4">
    <h3 class="text-danger">{{ __('tasks.access_denied') }}</h3>
    <p>{{ __('tasks.no_permission') }}</p>
</div>
@endrole

@endsection