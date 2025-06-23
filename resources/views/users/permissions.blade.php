@extends('layouts.app')
@section('content')
<div class="container py-4">
    <h2 class="mb-4">Grant Permissions</h2>
    @foreach($users as $user)
    <form method="POST" action="{{ route('admin.permissions.update') }}" class="card p-3 mb-3">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <h5>{{ $user->name }} ({{ $user->email }})</h5>
        <div class="row">
            @foreach($permissions as $permission)
                <div class="col-md-3 form-check">
                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                        {{ $user->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ ucfirst($permission->name) }}</label>
                </div>
            @endforeach
        </div>
        <button class="btn btn-success mt-3">Update</button>
    </form>
    @endforeach
</div>
@endsection
