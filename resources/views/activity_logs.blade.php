@extends('layout_dashboard')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        // Determine dashboard route for the "Back" functionality
        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }
    @endphp

    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER SECTION --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full mb-8">
            <div class="flex items-center gap-4">
                @include('components.back-btn' , ['route' => $dashRoute])
                
                <div>
                    <h2 class="font-bold text-3xl text-main tracking-tight">
                        {{ __('admin_log.all_activity_logs') }}
                    </h2>
                    <p class="text-muted-500 text-sm mt-2">{{ __('admin_log.activity_logs_subtitle') ?? 'View system events and user actions' }}</p>
                </div>
            </div>
        </div>

        {{-- COMBINED CARD CONTAINER --}}
        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-hidden flex flex-col animate-fade-in-up">

            {{-- SEARCH & FILTER BAR --}}
            <form class="p-5 border-b border-muted-200 flex flex-wrap gap-4 bg-white" method="GET">
                
                {{-- Search Input --}}
                <x-form.search-input
                    name="search"
                    id="search"
                    placeholder="admin_log.search_placeholder"
                    :value="request('search')"
                />

                {{-- Action Dropdown --}}
                <x-form.select
                    name="action"
                    id="action"
                    placeholder="admin_log.all_actions"
                    :value="request('action')"
                    :options="$distinctActions->mapWithKeys(fn($a) => [$a => ucfirst($a)])->toArray()"
                />

                {{-- Sort Dropdown --}}
                <x-form.select
                    name="sort_dir"
                    id="sort_dir"
                    :value="request('sort_dir', 'desc')"
                    :options="[
                        'desc' => __('admin_log.descending'),
                        'asc'  => __('admin_log.ascending'),
                    ]"
                />

                <div class="flex gap-2">
                    {{-- Filter button --}}
                    <button type="submit" title="{{ __('tasks.filter') }}"
                        class="border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                        </svg>
                    </button>

                    {{-- Reset button --}}
                    <a href="{{ route('admin.activity.logs') }}" title="{{ __('tasks.reset') }}"
                        class="flex items-center justify-center border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                        </svg>
                    </a>
                </div>
            </form>

            {{-- TABLE SECTION --}}
            <div class="overflow-x-auto w-full">
                <table class="w-full table-fixed">
                    <thead class="bg-muted-50 border-b border-muted-200">
                        <tr>
                            <th class="w-[8%] py-4 pl-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('admin_log.id') }}</th>
                            <th class="w-[15%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('admin_log.user') }}</th>
                            <th class="w-[15%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('admin_log.task') }}</th> {{-- Mapped to Action --}}
                            <th class="w-[15%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('admin_log.deadline') }}</th> {{-- Mapped to Date --}}
                            <th class="w-[47%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('admin_log.status') }}</th> {{-- Mapped to Desc --}}
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-muted-100">
                        @forelse($allLogs as $log)
                            <tr class="hover:bg-canvas transition-colors">
                                {{-- ID --}}
                                <td class="py-4 pl-6 text-sm text-muted-500">
                                    {{ $log->id }}
                                </td>

                                {{-- User Name --}}
                                <td class="py-4 px-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold ring-1 ring-primary/20">
                                            {{ substr($log->user_name ?? 'U', 0, 1) }}
                                        </div>
                                        <span class="text-sm font-medium text-main truncate" title="{{ $log->user_name }}">
                                            {{ $log->user_name }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Action --}}
                                <td class="py-4 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-muted-100 text-muted-600 border border-muted-200">
                                        {{ ucfirst($log->action) }}
                                    </span>
                                </td>

                                {{-- Date --}}
                                <td class="py-4 px-3 text-sm text-muted-500">
                                    {{ \Carbon\Carbon::parse($log->created_at)->format('H:i d/m/Y') }}
                                </td>

                                {{-- Description --}}
                                <td class="py-4 px-3 text-sm text-muted-600">
                                    <div class="truncate" title="{{ $log->description }}">
                                        {{ \Illuminate\Support\Str::limit($log->description, 80) }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="p-3 rounded-full bg-muted-100 text-muted-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-muted-500 font-medium">{{ __('admin_log.no_activity_logs') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINATION --}}
        @if ($allLogs->hasPages())
            <div class="mt-6 flex justify-center w-full">
                {{ $allLogs->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
            </div>
        @endif

    </div>
@endsection