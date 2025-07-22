@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
<div class="container py-4">
    <h1 class="mb-3 fw-bold">{{ __('admin_log.all_activity_logs') }}</h1>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('admin_log.search_placeholder') }}"
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="action" class="form-select">
                        <option value="">{{ __('admin_log.all_actions') }}</option>
                        @foreach ($distinctActions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucfirst($action) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort_dir" class="form-select">
                        <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>{{ __('admin_log.descending') }}</option>
                        <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>{{ __('admin_log.ascending') }}</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">{{ __('admin_log.filter') }}</button>
                    <a href="{{ route('admin.activity.logs') }}" class="btn btn-outline-secondary">{{ __('admin_log.reset') }}</a>
                </div>
            </form>

            <table class="table table-bordered table-hover table-striped">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('admin_log.id') }}</th>
                        <th>{{ __('admin_log.user') }}</th>
                        <th>{{ __('admin_log.task') }}</th>
                        <th>{{ __('admin_log.deadline') }}</th>
                        <th>{{ __('admin_log.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($allLogs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->user_name }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                <span class="badge bg-info text-dark" title="{{ $log->description }}">
                                    {{ \Illuminate\Support\Str::limit($log->description) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">{{ __('admin_log.no_activity_logs') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="d-flex justify-content-center">
                {{ $allLogs->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
