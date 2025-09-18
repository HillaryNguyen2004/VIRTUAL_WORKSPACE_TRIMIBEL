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
    <x-action-layout :route="$dashRoute" :title="'profile.back_to_dashboard'">
        <h2 class="font-medium text-[28px] md:text-[32px]">{{ __('admin_log.all_activity_logs') }}</h2>

        {{-- Search & Filter Bar --}}
        <form class="flex flex-wrap gap-2 animate-fade-in-up [animation-delay:150ms]" method="GET">
            <input type="text" name="search" id="search" placeholder="{{ __('admin_log.search_placeholder') }}"
                value="{{ request('search') }}"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
            <select name="action" id="action"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-3 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition cursor-pointer">
                <option value="">{{ __('admin_log.all_actions') }}</option>
                @foreach ($distinctActions as $action)
                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                        {{ ucfirst($action) }}
                    </option>
                @endforeach
            </select>
            <select name="sort_dir" id="sort_dir"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-3 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition cursor-pointer">
                <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>{{ __('admin_log.descending') }}
                </option>
                <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>{{ __('admin_log.ascending') }}
                </option>
            </select>
            <div class="flex gap-2">
                <!-- filter -->
                <button type="submit" title="{{ __('tasks.filter') }}"
                    class="border px-3 py-2 rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                    </svg>
                </button>
                <!-- reset -->
                <a href="{{ route('admin.activity.logs') }}" title="{{ __('tasks.reset') }}"
                    class="flex items-center justify-center border px-3 py-2 rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                    </svg>
                </a>
            </div>
        </form>
        {{-- list --}}
        <div
            class="overflow-x-auto rounded-2xl border border-gray-300 shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] animate-fade-in-up [animation-delay:200ms]">
            <table class="w-full">
                <thead class="bg-gray-100 text-gray-500 uppercase tracking-wide text-sm">
                    <tr>
                        <th class="py-3 pl-4 pr-3 text-left font-medium">{{ __('admin_log.id') }}</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('admin_log.user') }}</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('admin_log.task') }}</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('admin_log.deadline') }}</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('admin_log.status') }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-[#FDFDFF] text-sm">
                    @forelse($allLogs as $log)
                        <tr>
                            {{-- Id --}}
                            <td class="py-3 pl-4 pr-3">
                                <div class="max-w-xs truncate" title="{{ $log->id }}">{{ $log->id }}</div>
                            </td>

                            {{-- Name --}}
                            <td class="py-3 px-3">
                                <div class="max-w-xs truncate" title="{{ $log->user_name }}">{{ $log->user_name }}</div>
                            </td>

                            {{-- Action --}}
                            <td class="py-3 px-3">
                                <div class="max-w-xs truncate" title="{{ $log->action }}">{{ $log->action }}</div>
                            </td>

                            {{-- Created at --}}
                            <td class="py-3 px-3">
                                <div class="max-w-xs truncate" title="{{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i') }}">{{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i') }}</div>
                            </td>

                            {{-- Description --}}
                            <td class="py-3 px-3">
                                <div class="max-w-xs truncate">{{ \Illuminate\Support\Str::limit($log->description) }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400">
                                {{ __('admin_log.no_activity_logs') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($allLogs->hasPages())
                <div class="my-4 flex justify-center w-full">
                    {{ $allLogs->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </x-action-layout>

    {{-- <div class="container py-4">
        <h1 class="mb-3 fw-bold">{{ __('admin_log.all_activity_logs') }}</h1>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center mb-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control"
                            placeholder="{{ __('admin_log.search_placeholder') }}" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="action" class="form-select">
                            <option value="">{{ __('admin_log.all_actions') }}</option>
                            @foreach ($distinctActions as $action)
                            <option value="{{ $action }}" {{ request('action')==$action ? 'selected' : '' }}>
                                {{ ucfirst($action) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="sort_dir" class="form-select">
                            <option value="desc" {{ request('sort_dir')=='desc' ? 'selected' : '' }}>{{
                                __('admin_log.descending') }}</option>
                            <option value="asc" {{ request('sort_dir')=='asc' ? 'selected' : '' }}>{{
                                __('admin_log.ascending') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">{{ __('admin_log.filter') }}</button>
                        <a href="{{ route('admin.activity.logs') }}" class="btn btn-outline-secondary">{{
                            __('admin_log.reset') }}</a>
                    </div>
                </form>

                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('admin_log.id') }}</th>
                            <th>{{ __('admin_log.user') }}</th>
                            <th>{{ __('admin_log.task') }}</th>
                            <th>{{ __('admin_log.deadline') }}</th>
                            <th>{{ __('admin_log.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($allLogs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->user_name }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                <span class="badge bg-info text-dark" title="{{ $log->description }}">
                                    {{ \Illuminate\Support\Str::limit($log->description) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">{{ __('admin_log.no_activity_logs') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="d-flex justify-content-center">
                    {{ $allLogs->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div> --}}
@endsection