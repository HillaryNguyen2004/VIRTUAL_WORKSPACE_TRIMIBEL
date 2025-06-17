@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">Add New User</h1>
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-bold">Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Role</label>
            <select name="roles" class="form-select" required>
                <option value="user">User</option>
                <option value="staff">Staff</option>
            </select>
        </div>
        <button class="btn btn-primary">Add User</button>
    </form>
</div>
@endsection
