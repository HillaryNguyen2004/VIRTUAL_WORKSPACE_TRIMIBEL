@extends('layout_dashboard')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }
    @endphp
    <div class="flex flex-col gap-6 w-full">
        <a href="{{ route('tasks.staff.index') }}" class="text-[#5D3FD3] text-xl font-medium w-fit">
            &larr; {{ __('task_create.back_to_task') }}
        </a>

        @if(session('success'))
            <div
                class="bg-[#D6F5E3] text-[#5AE194] border border-[#5AE194] text-lg text-center px-3 py-2 rounded-2xl w-full animate-fade-in-up [animation-delay:150ms]">
                {{ session('success') }}</div>
        @endif

        <x-staff.task-form :title="__('task_create.title')" :action="route('tasks.store')"
            :staff-users="$staffUsers"></x-staff.task-form>
    </div>
    <!-- <div class="container py-4">
                <h1 class="mb-4 fw-bold">{{ __('task_create.title') }}</h1>

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('tasks.store') }}" method="POST">
                    @csrf
                    <div class="card p-4 mb-4">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('task_create.task_name_label') }} *</label>
                                <input type="text" name="title" class="form-control" placeholder="{{ __('task_create.task_name_placeholder') }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-bold">{{ __('task_create.assignee_label') }} *</label>
                                <select name="assignee" class="form-control" required>
                                    <option value="">{{ __('task_create.select_assignee') }}</option>
                                    @foreach($staffUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('task_create.due_date_label') }} *</label>
                                <input type="date" name="due_date" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('task_create.description_label') }}</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="{{ __('task_create.description_placeholder') }}"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('task_create.tags_label') }}</label>
                            <input type="text" name="tags[]" class="form-control mb-2" placeholder="{{ __('task_create.tag_placeholder') }}">
                            <a href="#" class="text-primary small" id="add-tag">{{ __('task_create.add_tag') }}</a>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="active" id="active" value="1" checked>
                            <label class="form-check-label" for="active">
                                {{ __('task_create.active_label') }}
                            </label>
                        </div>

                        <div class="d-flex justify-content-end">
                            @role('admin')
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary me-2">
                                    {{ __('task_create.cancel_button') }}
                                </a>
                            @endrole

                            @role('staff')
                                <a href="{{ route('staff.dashboard') }}" class="btn btn-outline-secondary me-2">
                                    {{ __('task_create.cancel_button') }}
                                </a>
                            @endrole
                            <button type="submit" class="btn btn-primary" style="background:#2563eb;border:none;">
                                <i class="bi bi-save"></i> {{ __('task_create.save_button') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div> -->
@endsection