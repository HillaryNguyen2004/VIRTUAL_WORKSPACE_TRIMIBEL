@extends('layouts.app')
@section('header')
    @include('partials.headers.admin')
@endsection
@section('content')
<div class="container py-4">
    <div class="card border-primary shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title text-primary mb-4 fs-3 fw-semibold border-bottom pb-3">Manage User Permissions</h2>

            @foreach($users as $user)
            <div class="card mb-4 border border-info-subtle">
                <div class="card-body bg-light-subtle">
                    <form method="POST" action="{{ route('admin.permissions.update') }}">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">

                        <div class="mb-3">
                            <h5 class="fw-bold text-dark mb-1">{{ $user->name }}
                                <span class="text-muted fw-normal small">({{ $user->email }})</span>
                            </h5>
                        </div>

                        <div class="row">
                            @foreach($permissions as $permission)
                            <div class="col-sm-6 col-md-4 mb-2">
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        name="permissions[]" 
                                        value="{{ $permission->name }}"
                                        id="perm-{{ $user->id }}-{{ $permission->name }}"
                                        {{ $user->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                    >
                                    <label 
                                        class="form-check-label text-dark" 
                                        for="perm-{{ $user->id }}-{{ $permission->name }}"
                                    >
                                        {{ ucfirst(str_replace('-', ' ', $permission->name)) }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check2-circle me-1"></i> Update Permissions
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach

        </div>
    </div>
</div>
@endsection
