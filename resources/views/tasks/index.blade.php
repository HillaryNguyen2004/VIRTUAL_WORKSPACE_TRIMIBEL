@extends('layouts.app')
@section('header')
    @include('partials.headers.admin')
@endsection
@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">{{ __('admin_task.title') }}</h1>

    <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>{{ __('admin_task.all_tasks') }}</strong>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">{{ __('admin_task.add_new_task') }}</a>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('tasks.index') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="{{ __('admin_task.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="due_date" class="form-control" value="{{ request('due_date') }}">
            </div>
            <div class="col-md-3">
                <select name="assigned_user_id" class="form-select">
                    <option value="">{{ __('admin_task.filter_by_assignee') }}</option>
                    @foreach($allUsers as $user)
                        <option value="{{ $user->id }}" {{ request('assigned_user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <select name="sort_by" class="form-select">
                    <option value="">{{ __('admin_task.sort_by') }}</option>
                    <option value="title" {{ request('sort_by') == 'title' ? 'selected' : '' }}>{{ __('admin_task.sort_task_name') }}</option>
                    <option value="due_date" {{ request('sort_by') == 'due_date' ? 'selected' : '' }}>{{ __('admin_task.sort_due_date') }}</option>
                </select>
                <button type="submit" class="btn btn-secondary">{{ __('admin_task.apply_button') }}</button>
            </div>
        </form>

        <table class="table align-middle">
            <thead>
                <tr>
                    <th>{{ __('admin_task.task_id_column') }}</th>
                    <th>{{ __('admin_task.task_name_column') }}</th>
                    <th>{{ __('admin_task.assignee_column') }}</th>
                    <th>{{ __('admin_task.due_date_column') }}</th>
                    <th>{{ __('admin_task.status_column') }}</th>
                    <th>{{ __('admin_task.active_column') }}</th>
                    <th>{{ __('admin_task.actions_column') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                <tr data-bs-toggle="collapse" data-bs-target="#taskDetails{{ $task->task_id }}" aria-expanded="false" style="cursor:pointer;">
                    <td>{{ $task->task_id }}</td>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->assigneeUser?->name ?? __('admin_task.no_assignee') }}</td>
                    <td>
                        {{ $task->due_date }}
                        @if(\Carbon\Carbon::parse($task->due_date)->isPast() && $task->status !== 'completed')
                            <span class="badge bg-danger ms-2">{{ __('admin_task.overdue') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($task->status === 'pending')
                            <span class="badge bg-warning text-dark">{{ __('admin_task.status_pending') }}</span>
                        @elseif($task->status === 'in_progress')
                            <span class="badge bg-info text-dark">{{ __('admin_task.status_in_progress') }}</span>
                        @elseif($task->status === 'completed')
                            <span class="badge bg-success">{{ __('admin_task.status_completed') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($task->active)
                            <span class="badge bg-success">{{ __('admin_task.active') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ __('admin_task.inactive') }}</span>
                        @endif
                    </td>
                    <td>
                        <div onclick="event.stopPropagation();">
                            <a href="#" title="{{ __('admin_task.view_action') }}">
                                <i class="bi bi-eye text-primary fs-5"></i>
                            </a>
                            <a href="{{ route('tasks.edit', $task->task_id) }}" title="{{ __('admin_task.edit_action') }}">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('tasks.destroy', $task->task_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    @role('admin')
                                        <input type="hidden" name="redirect_to" value="tasks.index">
                                    @endrole
                                    @role('staff')
                                        <input type="hidden" name="redirect_to" value="back">
                                    @endrole
                                    <button type="submit" class="btn btn-link p-0 m-0 text-danger" onclick="return confirm('{{ __('admin_task.delete_confirm') }}')">
                                        <i class="bi bi-trash fs-5"></i>
                                    </button>
                            </form>
                    </td>
                </tr>
                <tr class="collapse" id="taskDetails{{ $task->task_id }}">
                    <td colspan="7" class="bg-light">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>{{ __('admin_task.description_label') }}:</strong>
                                <div>{{ $task->description ?? __('admin_task.no_description') }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>{{ __('admin_task.tags_label') }}:</strong>
                                <div>
                                    @if(!empty($task->tags))
                                        @foreach(json_decode($task->tags, true) as $tag)
                                            <span class="badge bg-primary">{{ $tag }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">{{ __('admin_task.no_tags') }}</span>
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