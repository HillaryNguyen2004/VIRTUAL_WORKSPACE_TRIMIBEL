@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
<div class="container py-4">
    {{-- Page Title --}}
    <h1 class="mb-4 fw-bold text-center">{{ __('create_user.title') }}</h1>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success text-center">{{ session('success') }}</div>
    @endif

    {{-- Error Message --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ __('create_user.error_message', ['error' => $error]) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        {{-- Create New User --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white fw-bold">
                    {{ __('create_user.create_new_user') }}
                </div>
                <div class="card-body">
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
                        <button class="btn btn-success w-100">
                            <i class="bi bi-person-plus"></i> {{ __('create_user.submit_button') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Import Users from CSV --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-secondary text-white fw-bold">
                    {{ __('create_user.import_csv_title') ?? 'Import Users from CSV' }}
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('create_user.csv_label') ?? 'CSV File' }}</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-primary">
                                <i class="bi bi-upload"></i> {{ __('create_user.import_button') ?? 'Import' }}
                            </button>
                            <a href="{{ route('admin.users.import.template') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-download"></i> {{ __('create_user.download_template') ?? 'Download Template' }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
