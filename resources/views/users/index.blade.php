@extends('layouts.app')
@section('header')
    @include('partials.headers.admin')
@endsection
@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">{{ __('user_management.title') }}</h1>

    <div class="card p-4 mb-4">
        <form class="row g-3 mb-3" method="GET" action="{{ route('users.index') }}">
            <div class="col-md-4">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('user_management.search_placeholder') }}">
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select">
                    <option value="">{{ __('user_management.all_roles') }}</option>
                    <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>{{ __('user_management.staff_role') }}</option>
                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>{{ __('user_management.user_role') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('users.index', array_merge(request()->except('page'), ['sort' => request('sort') === 'asc' ? 'desc' : 'asc'])) }}" class="btn btn-outline-dark w-100">
                    {{ __('user_management.sort_label') }}: {{ request('sort') === 'desc' ? __('user_management.sort_desc') : __('user_management.sort_asc') }}
                </a>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">{{ __('user_management.search_button') }}</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary w-100">{{ __('user_management.reset_button') }}</a>
            </div>
        </form>

        <table class="table align-middle">
            <thead>
                <tr>
                    <th>{{ __('user_management.username_column') }}</th>
                    <th>{{ __('user_management.email_column') }}</th>
                    <th>{{ __('user_management.role_column') }}</th>
                    <th>{{ __('user_management.actions_column') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    @include('users.partials.user_row', compact('user', 'users'))
                @endforeach
            </tbody>
        </table>
        <div class="mb-3 text-end">
            <a href="{{ url('/export-users-excel') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i> Export to Excel
            </a>
        </div>
        <!-- Pagination Links -->
        <div class="d-flex justify-content-center">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/user-management.js') }}"></script>
@endpush
@endsection