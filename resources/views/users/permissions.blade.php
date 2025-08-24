@extends('layouts.app')
@section('header')
    @include('partials.headers.admin')
@endsection
@section('content')
<div class="container py-4">
    <div class="card border-primary shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title text-primary mb-4 fs-3 fw-semibold border-bottom pb-3">{{ __('user_permission.title') }}</h2>

            @foreach($roles as $role)
                @if($role->name !== 'admin') {{-- ✅ Skip admin role --}}
                    <div class="card mb-4 border border-info-subtle">
                        <div class="card-body bg-light-subtle">
                            <form method="POST" action="{{ route('admin.permissions.update') }}">
                                @csrf
                                <input type="hidden" name="role_name" value="{{ $role->name }}">

                                <div class="mb-3">
                                    <h5 class="fw-bold text-dark mb-1">
                                        {{ ucfirst($role->name) }}
                                        <span class="text-muted fw-normal small">
                                            ({{ \App\Models\User::role($role->name)->whereHas('roles', function ($q) {
                                                $q->where('name', '!=', 'admin');
                                            })->count() }} users)
                                        </span>
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
                                                    id="perm-{{ $role->id }}-{{ $permission->name }}"
                                                    {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                                >
                                                <label 
                                                    class="form-check-label text-dark" 
                                                    for="perm-{{ $role->id }}-{{ $permission->name }}"
                                                >
                                                    {{ __('user_permission.' . str_replace('-', '_', $permission->name)) }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-check2-circle me-1"></i> {{ __('user_permission.update_button') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endsection
