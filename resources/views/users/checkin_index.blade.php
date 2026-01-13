@extends('layout_dashboard')

@section('content')
    @vite(['resources/js/admin/close_info_alert.js'])
    
    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }
    @endphp

    @role('admin')
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER SECTION --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full">
            <div class="flex items-center gap-4">
                @include('components.back-btn' , ['route' => $dashRoute])
                
                <div>
                    <h2 class="font-bold text-3xl text-main tracking-tight">
                        {{ __('checkin_logs.title') }}
                    </h2>
                    <p class="text-muted-500 text-sm mt-2">{{ __('checkin_logs.subtitle') }}</p>
                </div>
            </div>

            {{-- Export Button (Submits current filters) --}}
            <form method="GET" action="{{ route('checkins.export') }}">
                <input type="hidden" name="username" value="{{ request('username') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                
                <button type="submit"
                    class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-primary/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="font-medium">{{ __('checkin_logs.export_button') }}</span>
                </button>
            </form>
        </div>

        {{-- INFO ALERT --}}
        <div id="info-alert" class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-3 p-4 rounded-2xl bg-primary/5 text-primary animate-fade-in-up [animation-delay:100ms]">
            <div class="flex gap-3">
                <div class="p-2 bg-primary/10 text-primary rounded-lg shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 512 512">
                        <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-208a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-sm uppercase tracking-wide opacity-80 mb-1">{{ __('checkin_logs.checkin_policy') }}</h4>
                    <!-- <p class="text-sm leading-relaxed">
                        Employees are considered <span class="font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded text-xs">LATE</span> if they check in >5 mins after start time.
                        <span class="font-bold text-emerald-600 bg-emerald-100 px-1.5 py-0.5 rounded text-xs">ON TIME</span> includes a 5-minute grace period.
                    </p> -->
                    <p class="text-sm leading-relaxed">
                        {{ __('checkin_logs.checkin_policy_details_1') }} <span class="font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded text-xs">{{ __('checkin_logs.late') }}</span> {{ __('checkin_logs.checkin_policy_details_2') }}
                    </p>
                </div>
            </div>
            <button id="close-info" type="button" class="absolute top-2 right-2 md:relative md:top-auto md:right-auto p-1.5 rounded-lg hover:bg-primary/10 text-primary hover:text-primary/80 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- SUMMARY CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-fade-in-up [animation-delay:200ms]">
            @php
                $totalCheckIns = $checkIns->total();
                $onTimeCheckIns = $checkIns->where('is_late', false)->where('check_in_time', '!=', null)->count();
                $lateCheckIns = $checkIns->where('is_late', true)->count();
                $latePercentage = $totalCheckIns > 0 ? round(($lateCheckIns / $totalCheckIns) * 100, 1) : 0;

                $summaryCards = [
                    [
                        'label' => __('checkin_logs.total_check_in'),
                        'value' => $totalCheckIns,
                        'color' => 'text-primary',
                        'bg' => 'bg-primary/10',
                        'hover' => 'hover:border-primary/80 hover:shadow-primary/10',
                        'icon' => '<path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
                    ],
                    [
                        'label' => __('checkin_logs.on_time'),
                        'value' => $onTimeCheckIns,
                        'color' => 'text-accent',
                        'bg' => 'bg-accent/10',
                        'hover' => 'hover:border-accent/80 hover:shadow-accent/10',
                        'icon' => '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    ],
                    [
                        'label' => __('checkin_logs.late_arrivals'),
                        'value' => $lateCheckIns,
                        'color' => 'text-danger',
                        'bg' => 'bg-danger/10',
                        'hover' => 'hover:border-danger/30 hover:shadow-danger/5',
                        'icon' => '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    ],
                    [
                        'label' => __('checkin_logs.late_rate'),
                        'value' => $latePercentage . '%',
                        'color' => 'text-secondary',
                        'bg' => 'bg-secondary/10',
                        'hover' => 'hover:border-secondary/80 hover:shadow-secondary/10',
                        'icon' => '<path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />',
                    ],
                ];
            @endphp

            @foreach($summaryCards as $card)
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 {{ $card['hover'] }} transition-all duration-300 group">
                <div class="flex items-start h-full justify-between">
                    <div class="flex flex-col justify-between h-full">
                        <p class="text-muted-500 font-medium text-sm">{{ $card['label'] }}</p>
                        <p class="text-3xl font-bold text-main tracking-tight">{{ $card['value'] }}</p>
                    </div>
                    <div class="p-3 rounded-xl {{ $card['bg'] }} {{ $card['color'] }} group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            {!! $card['icon'] !!}
                        </svg>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- MAIN CARD (Filters + Table) --}}
        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-hidden flex flex-col animate-fade-in-up [animation-delay:300ms]">
            
            {{-- FILTER BAR --}}
            <form class="p-5 border-b border-muted-200 flex flex-wrap gap-4 bg-white" method="GET">
                {{-- Username Search --}}
                <x-form.search-input
                    name="username"
                    placeholder="checkin_logs.search_placeholder_username"
                    :value="request('username')"
                />

                {{-- Date From --}}
                <div class="relative group">
                    <span class="absolute -top-2 left-3 bg-white px-1 text-xs font-medium text-muted-400 group-focus-within:text-primary transition-colors">
                        {{ __('checkin_logs.filter_label_from') }}
                    </span>

                    <x-form.input
                        type="date"
                        name="date_from"
                        :value="request('date_from')"
                    />
                </div>

                {{-- Date To --}}
                <div class="relative group">
                    <span class="absolute -top-2 left-3 bg-white px-1 text-xs font-medium text-muted-400 group-focus-within:text-primary transition-colors">
                        {{ __('checkin_logs.filter_label_to') }}
                    </span>

                    <x-form.input
                        type="date"
                        name="date_to"
                        :value="request('date_to')"
                    />
                </div>

                {{-- Status --}}
                <x-form.select
                    name="status"
                    placeholder="checkin_logs.filter_option_all_statuses"
                    :value="request('status')"
                    :options="[
                        'late' => __('checkin_logs.filter_option_late'),
                        'on_time' => __('checkin_logs.filter_option_on_time'),
                    ]"
                />

                {{-- Per Page --}}
                <x-form.select
                    name="per_page"
                    :value="request('per_page', 10)"
                    :options="[
                        10 => '10 Rows',
                        25 => '25 Rows',
                        50 => '50 Rows',
                    ]"
                />

                <div class="flex gap-2">
                    <button type="submit" title="{{ __('tasks.filter') }}"
                        class="border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                        </svg>
                    </button>

                    <a href="{{ route('users.checkin_index') }}" title="{{ __('tasks.reset') }}"
                        class="flex items-center justify-center border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                        </svg>
                    </a>
                </div>
            </form>

            {{-- TABLE SECTION --}}
            <div class="overflow-x-auto w-full h-[629px]">
                <table class="w-full table-fixed">
                    <thead class="bg-muted-50 border-b border-muted-200">
                        <tr>
                            <th class="w-[5%] py-4 pl-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">ID</th>
                            <th class="w-[20%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('checkin_logs.table_header_user_name') }}</th>
                            <th class="w-[15%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('checkin_logs.table_header_date') }}</th>
                            <th class="w-[20%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('checkin_logs.table_header_check_in_time') }}</th>
                            <th class="w-[15%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('checkin_logs.table_header_check_out_time') }}</th>
                            <th class="w-[15%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('checkin_logs.table_header_working_hours') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-muted-100">
                        @forelse($checkIns as $index => $log)
                            @php
                                // Compute Working Hours
                                $computedWorking = null;
                                if (!empty($log->working_hours)) {
                                    $computedWorking = $log->working_hours;
                                } elseif (!empty($log->check_in_time) && !empty($log->check_out_time)) {
                                    try {
                                        $in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->date . ' ' . $log->check_in_time, 'Asia/Ho_Chi_Minh');
                                        $out = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->date . ' ' . $log->check_out_time, 'Asia/Ho_Chi_Minh');
                                        $seconds = $out->diffInSeconds($in);
                                        $hours = intdiv($seconds, 3600);
                                        $minutes = intdiv($seconds % 3600, 60);
                                        $computedWorking = sprintf('%02d:%02d', $hours, $minutes);
                                    } catch (\Exception $e) { $computedWorking = null; }
                                }
                                
                                // Late styling logic
                                $isLate = $log->is_late;
                                $rowClass= 'hover:bg-canvas transition-colors';
                            @endphp

                            <tr class="{{ $rowClass }}">
                                {{-- ID --}}
                                <td class="py-4 pl-6 text-sm text-muted-500">
                                    {{ $checkIns->firstItem() + $index }}
                                </td>

                                {{-- User Name --}}
                                <td class="py-4 px-3 text-sm font-medium text-main">
                                    <div class="truncate" title="{{ $log->user_name }}">{{ $log->user_name }}</div>
                                </td>

                                {{-- Date --}}
                                <td class="py-4 px-3 text-sm text-muted-500">
                                    {{ $log->date }}
                                </td>

                                {{-- Check In Time --}}
                                <td class="py-4 px-3">
                                    <div class="flex items-center gap-2">
                                        @if ($log->check_in_time)
                                            <span class="text-sm font-medium {{ $isLate ? 'text-rose-600' : 'text-main' }}">
                                                {{ $log->check_in_time }}
                                            </span>
                                            
                                            @if($isLate)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-danger/10 px-2 py-0.5 text-xs font-medium text-danger ring-1 ring-inset ring-danger/10">
                                                    {{ __('checkin_logs.late') }}
                                                </span>
                                            @else
                                                <!-- <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success ring-1 ring-inset ring-success/10">
                                                    ON TIME
                                                </span> -->
                                            @endif
                                        @else
                                            <span class="text-muted-400">-</span>
                                        @endif

                                        @if (!empty($log->is_half_day_off) && $log->is_half_day_off)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                Half Day
                                            </span>
                                        @elseif (!empty($log->is_full_day_off) && $log->is_full_day_off)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                                Full Off
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Check Out Time --}}
                                <td class="py-4 px-3 text-sm text-muted-500">
                                    {{ $log->check_out_time ?? '-' }}
                                </td>

                                {{-- Working Hours --}}
                                <td class="py-4 px-3">
                                    @if (!empty($computedWorking))
                                        <span class="inline-flex items-center rounded-md bg-primary/10 px-2 py-1 text-xs font-medium text-primary ring-1 ring-inset ring-primary/20">
                                            {{ $computedWorking }} hrs
                                        </span>
                                    @elseif ($log->check_in_time && !$log->check_out_time)
                                        <span class="inline-flex items-center rounded-md bg-accent/10 px-2 py-1 text-xs font-medium text-accent ring-1 ring-inset ring-accent/20">
                                            {{ __('checkin_logs.badge_checked_in') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-muted-400 italic">{{ __('checkin_logs.badge_not_checked_in') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="p-3 rounded-full bg-muted-100 text-muted-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <p class="text-muted-500 font-medium">{{ __('checkin_logs.table_no_records') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($checkIns instanceof \Illuminate\Contracts\Pagination\Paginator && $checkIns->hasPages())
                <div class="mt-6 flex justify-center w-full pb-6">
                    {{ $checkIns->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>
    @endrole

    @unlessrole('admin')
    <div class="container py-12 text-center">
        <h4 class="text-xl font-bold text-danger">{{ __('checkin_logs.access_denied_title') }}</h4>
        <p class="text-muted-500 mt-2">{{ __('checkin_logs.access_denied_message') }}</p>
    </div>
    @endunlessrole
@endsection