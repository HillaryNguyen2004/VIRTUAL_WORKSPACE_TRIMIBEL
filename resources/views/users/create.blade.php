@extends('layouts.app')
@section('header')
    @include('partials.headers.admin')
@endsection
@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">Add New User</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-bold">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Role</label>
            <select name="roles" class="form-select" required>
                <option value="" disabled selected>Select Role</option>
                <option value="user" {{ old('roles') == 'user' ? 'selected' : '' }}>User</option>
                <option value="staff" {{ old('roles') == 'staff' ? 'selected' : '' }}>Staff</option>
            </select>
        </div>
        <button class="btn btn-primary">Add User</button>
    </form>
</div>
@endsection
