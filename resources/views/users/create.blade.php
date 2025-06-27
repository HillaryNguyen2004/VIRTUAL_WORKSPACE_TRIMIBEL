@extends('layouts.app')
@section('header')
    @include('partials.headers.admin')
@endsection
@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">{{ __('create_user.title') }}</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ __('create_user.error_message', ['error' => $error]) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-bold">{{ __('create_user.name_label') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">{{ __('create_user.email_label') }}</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">{{ __('create_user.role_label') }}</label>
            <select name="roles" class="form-select" required>
                <option value="" disabled selected>{{ __('create_user.select_role') }}</option>
                <option value="user" {{ old('roles') == 'user' ? 'selected' : '' }}>{{ __('create_user.user_role') }}</option>
                <option value="staff" {{ old('roles') == 'staff' ? 'selected' : '' }}>{{ __('create_user.staff_role') }}</option>
            </select>
        </div>
        <button class="btn btn-primary">{{ __('create_user.submit_button') }}</button>
    </form>
</div>
@endsection