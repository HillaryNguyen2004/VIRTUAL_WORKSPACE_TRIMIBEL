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
    @role('staff')
    <div class="flex flex-col gap-6 w-full">
        <a href="{{ route($dashRoute) }}" class="text-[#5D3FD3] text-xl font-medium w-fit">
            &larr; {{ __('profile.back_to_dashboard') }}
        </a>

        {{-- title --}}
        <h2 class="font-medium text-[28px] md:text-[32px]">{{ __('staff_team.my_team') }}</h2>

        @if(session('success'))
            <div class="bg-[#D6F5E3] text-[#5AE194] border border-[#5AE194] text-lg text-center px-3 py-2 rounded-2xl w-full animate-fade-in-up [animation-delay:150ms]">
                {{ session('success') }}
            </div>
        @endif

        {{-- content --}}
        @if($teamMembers->isEmpty())
            <p class="">{{ __('staff_team.no_team_members') }}</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 w-full gap-3">
                @foreach ($teamMembers as $member)
                    <div
                        class="flex flex-col items-center justify-center gap-2 h-fit bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-5 px-6 animate-fade-in-up [animation-delay:150ms]">
                        <p class="break-all"><span class="font-semibold">Name:</span> {{ $member->name }}</p>
                        <p class="break-all"><span class="font-semibold">Username:</span> {{ $member->username }}</p>
                        <div><span class="font-semibold">Email:</span> <a href="mailto:{{ $member->email }}"
                                class="break-all hover:underline" title="{{ $member->email }}">{{ $member->email }}</a></div>
                        <form action="{{ route('team.assignTask') }}" method="POST" class="flex flex-col gap-2">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $member->id }}">
                            <select name="task_id"
                                class="rounded-xl w-full border border-gray-300 px-4 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                                required>
                                <option value="">{{ __('staff_team.select_task') }}</option>
                                @foreach($staffTasks as $task)
                                    <option value="{{ $task->task_id }}" @selected(old('member_id', $member->id ?? null) == $task->assigned_user_id)>{{ $task->title }}</option>
                                @endforeach
                            </select>
                            <button type="submit"
                                class="px-4 py-2 w-full bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                                {{ __('staff_team.assign_task') }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <!-- <div class="container py-4">
                        <h2 class="mb-4">{{ __('staff_team.my_team') }}</h2>

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if($teamMembers->isEmpty())
                            <p class="text-muted">{{ __('staff_team.no_team_members') }}</p>
                        @else
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        @foreach($teamMembers as $member)
                            <div class="col">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title text-primary fw-bold mb-1">{{ $member->name }}</h5>
                                        <p class="card-subtitle mb-3 text-muted">{{ $member->email }}</p>

                                        <form action="{{ route('team.assignTask') }}" method="POST" class="mt-auto">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $member->id }}">
                                            <div class="mb-2">
                                                <select name="task_id" class="form-select" required>
                                                    <option value="">{{ __('staff_team.select_task') }}</option>
                                                    @foreach($staffTasks as $task)
                                                        <option value="{{ $task->task_id }}">{{ $task->title }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                                {{ __('staff_team.assign_task') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                        @endif
                    </div>
                    @else
                    <div class="container py-4">
                        <h3 class="text-danger">{{ __('staff_team.access_denied') }}</h3>
                        <p>{{ __('staff_team.no_permission') }}</p>
                    </div>
                    @endrole -->

@endsection