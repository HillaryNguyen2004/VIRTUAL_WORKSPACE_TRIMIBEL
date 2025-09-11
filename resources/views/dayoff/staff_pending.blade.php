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

        <!-- Title -->
        <h2 class="font-medium text-[28px] md:text-[32px]">{{ __('staff_pending_day_off.title') }}</h2>

        @if(session('success'))
            <div class="bg-[#D6F5E3] text-[#5AE194] border border-[#5AE194] text-lg text-center px-3 py-2 rounded-2xl w-full animate-fade-in-up [animation-delay:150ms]">{{ session('success') }}</div>
        @endif

        <!-- Content -->
        <div class="flex flex-wrap items-center justify-center gap-3 w-full">
            @forelse($requests as $req)
                <div
                    class="flex flex-col gap-2 bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-5 px-6 animate-fade-in-up [animation-delay:150ms]">
                    <div class="flex gap-1">
                        <p class="font-semibold">Username:</p>
                        <p>{{ $req->user->name }}</p>
                    </div>
                    <div class="flex gap-1">
                        <p class="font-semibold">{{ __('staff_pending_day_off.date_label') }}:</p>
                        <p>{{ $req->date }}</p>
                    </div>
                    <div class="flex gap-1">
                        <p class="font-semibold">{{ __('staff_pending_day_off.type_label') }}:</p>
                        <p>{{ $req->leave_type }}</p>
                    </div>
                    <div class="flex gap-1">
                        <p class="font-semibold">{{ __('staff_pending_day_off.reason_label') }}:</p>
                        <p>{{ $req->reason ?? 'N/A' }}</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <!-- accepted button -->
                        <form id="accepted-form" action="{{ route('dayoff.approve', $req->id) }}" method="POST">
                            @csrf
                            <button id="accepted-btn"
                                class="px-3 py-1 bg-[#46F196] text-white hover:bg-[#41e28c] rounded-full transition">
                                {{ __('staff_pending_day_off.accepted_btn') }}
                            </button>
                        </form>
                        <!-- rejected button -->
                        <form id="rejected-form" action="{{ route('dayoff.reject', $req->id) }}" method="POST">
                            @csrf
                            <button id="rejected-btn"
                                class="px-3 py-1 bg-[#F14646] text-white hover:bg-[#e14242] rounded-full transition">
                                {{ __('staff_pending_day_off.rejected_btn') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p>{{ __('staff_pending_day_off.no_request_pending') }}</p>
            @endforelse
        </div>
    </div>
    @else
        <p>{{ __('staff_pending_day_off.no_permission') }}</p>
    @endrole
@endsection