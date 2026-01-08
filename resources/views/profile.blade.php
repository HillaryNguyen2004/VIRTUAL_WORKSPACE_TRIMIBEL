@extends('layout_dashboard')

@section('content')
    <div class="flex flex-col gap-6 w-full">
        @php
            use Illuminate\Support\Facades\Route;

            $dashRoute = 'user.dashboard';
            if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
                $dashRoute = 'admin.dashboard';
            } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
                $dashRoute = 'staff.dashboard';
            }
        @endphp
        <!-- <a href="{{ route($dashRoute) }}" class="text-[#5D3FD3] text-lg font-medium w-fit">
            &larr; {{ __('profile.back_to_dashboard') }}
        </a> -->
        <x-back-btn :route="$dashRoute" />
        <div
            class="flex flex-col items-center w-full h-fit bg-[#FDFDFF] rounded-2xl shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] animate-fade-in-up [animation-delay:150ms]">
            <div class="w-full py-3 text-center text-xl bg-[#F1EFFC] text-[#5D3FD3] font-medium rounded-t-2xl relative">
                <h1>{{ __('profile.profile_title') }}</h1>
            </div>
            <div class="flex flex-col items-center justify-center gap-6 py-6 px-8">
                <img src="{{ getUserAvatar(Auth::user()) }}"
                    class="w-[150px] h-[150px] object-cover md:w-[200px] md:h-[200px] rounded-full" alt="User Avatar">
                <dl class="flex flex-col items-center justify-center gap-3 w-full text-sm xl:text-lg">
                    <div class="flex gap-1">
                        <dt class="text-gray-500">{{ __('profile.full_name_label') }}:</dt>
                        <dd class="text-gray-900 break-all" title="{{ Auth::user()->name }}">
                            {{ Auth::user()->name }}
                        </dd>
                    </div>
                    <div class="flex gap-1">
                        <dt class="text-gray-500">Username:</dt>
                        <dd class="text-gray-900 break-all" title="{{ Auth::user()->name }}">
                            {{ Auth::user()->username }}
                        </dd>
                    </div>
                    <div class="flex gap-1">
                        <dt class="text-gray-500">{{ __('profile.email_label') }}:</dt>
                        <dd class="text-gray-900 break-all" title="{{ Auth::user()->email }}">
                            {{ Auth::user()->email }}
                        </dd>
                    </div>
                    <div class="flex gap-1">
                        <dt class="text-gray-500">{{ __('profile.joined_label') }}:</dt>
                        <dd class="text-gray-900 break-all">
                            {{ Auth::user()->created_at->format('F d, Y') }}
                        </dd>
                    </div>
                </dl>
                <a href="{{ route('settings') }}"
                    class="px-4 py-2 w-52 bg-[#5D3FD3] hover:opacity-95 text-white text-center text-sm xl:text-lg rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                    {{ __('profile.edit_profile_button') }}
                </a>
            </div>
        </div>
    </div>
@endsection