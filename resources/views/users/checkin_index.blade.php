@extends('layout_dashboard')

@section('content')
    @vite([
        'resources/js/admin/close_info_alert.js',
        'resources/js/admin/monthly_attendance_charts.js',
    ])

    @php
        use Illuminate\Support\Facades\Route;
        use Illuminate\Support\Js;

        // Determine dashboard route based on role
        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('subadmin') && Route::has('subadmin.dashboard')) {
            $dashRoute = 'subadmin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        } elseif (auth()->user()->hasRole('substaff') && Route::has('substaff.dashboard')) {
            $dashRoute = 'substaff.dashboard';
        }

        $reportCollection = collect($attendanceReports ?? []);

        $totalEmployees = $reportCollection->count();
        $totalExpectedHours = round($reportCollection->sum('expected_hours'), 2);
        $totalActualHours = round($reportCollection->sum('actual_hours'), 2);
        $totalVarianceHours = round($reportCollection->sum('variance'), 2);

        $employeesAhead = $reportCollection->filter(fn ($r) => $r['variance'] >= 2)->count();
        $employeesOnTrack = $reportCollection->filter(fn ($r) => $r['variance'] > -2 && $r['variance'] < 2)->count();
        $employeesBehind = $reportCollection->filter(fn ($r) => $r['variance'] <= -2)->count();

        $avgCompletionRate = $totalExpectedHours > 0
            ? round(($totalActualHours / $totalExpectedHours) * 100, 1)
            : 0;

        $chartReports = $reportCollection
            ->sortByDesc(fn ($r) => abs($r['variance']))
            ->values();

        $chartPayload = [
            'labels' => $chartReports->map(fn ($r) => $r['user']->name)->values()->all(),
            'expected' => $chartReports->map(fn ($r) => round($r['expected_hours'], 2))->values()->all(),
            'actual' => $chartReports->map(fn ($r) => round($r['actual_hours'], 2))->values()->all(),
            'statusCounts' => [
                'ahead' => $employeesAhead,
                'on_track' => $employeesOnTrack,
                'behind' => $employeesBehind,
            ],
        ];
    @endphp

    <div class="flex flex-col gap-4 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                @include('components.back-btn', ['route' => $dashRoute])

                <div>
                    <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">
                        {{ __('checkin_logs.title') }}
                    </h1>
                    <p class="text-muted-500 text-sm md:text-base mt-1">
                        Monthly-first view for employee attendance and working hours.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('users.checkin_index') }}" class="flex items-center gap-3">
                    <input type="hidden" name="username" value="{{ request('username') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">

                    <div class="relative">
                        <select
                            name="month"
                            onchange="this.form.submit()"
                            class="appearance-none w-48 bg-canvas border border-muted-300 rounded-xl px-5 py-2.5"
                        >
                            @foreach($availableMonths as $monthKey => $monthLabel)
                                <option value="{{ $monthKey }}" {{ $monthKey === $month ? 'selected' : '' }}>
                                    {{ $monthLabel }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Custom Chevron Icon -->
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-muted-500">
                            <svg class="h-5 w-5 fill-current" viewBox="0 0 20 20">
                            <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                            </svg>
                        </div>
                    </div>
                </form>

                <form method="GET" action="{{ route('checkins.export') }}">
                    <input type="hidden" name="username" value="{{ request('username') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    <input type="hidden" name="month" value="{{ $month }}">

                    <button
                        type="submit"
                        class="flex w-48 items-center justify-center gap-2 rounded-xl bg-primary py-3 text-white shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="font-medium">{{ __('checkin_logs.export_button') }}</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- MONTH HERO --}}
        <div class="relative overflow-hidden p-6 bg-primary-gradient shadow-xl shadow-primary/20 hover:shadow-primary/25 transition-all duration-300 rounded-3xl">
            
            {{-- Decorative Background Icons --}}
            {{-- Large Calendar Watermark --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute -bottom-12 -right-8 h-56 w-56 text-white/10 -rotate-12 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            
            {{-- Small Clock Watermark --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute top-4 right-1/4 h-24 w-24 text-white/5 rotate-12 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>

            {{-- Main Content --}}
            <div class="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-medium text-canvas/50 uppercase tracking-widest text-sm">Monthly Attendance Overview</p>
                    <h3 class="mt-2 text-2xl md:text-3xl font-semibold text-canvas override">
                        {{ $availableMonths[$month] ?? $month }}
                    </h3>
                    <p class="font-medium text-canvas/50 text-xs mt-1">
                        Focus on expected hours, actual worked hours, and who is ahead, on track, or behind this month.
                    </p>
                </div>

                <a
                    href="#daily-attendance-section"
                    onclick="document.getElementById('daily-attendance-section').open = true"
                    class="inline-flex items-center justify-center rounded-xl bg-white text-primary transition-all duration-300 hover:scale-105 px-4 py-2.5 text-sm font-semibold shadow-sm"
                >
                    Jump to daily logs
                </a>
            </div>
        </div>

        {{-- MONTHLY KPI CARDS --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            
            <x-white-card-container color="primary/50" class="p-3 items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-400">Tracked Employees</p>
                    <p class="text-lg font-bold text-main leading-tight">{{ $totalEmployees }}</p>
                </div>
            </x-white-card-container>

            <x-white-card-container class="hover:border-secondary/80 p-3 items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-secondary/10 text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-400">Expected Hours</p>
                    <p class="text-lg font-bold text-main leading-tight">{{ number_format($totalExpectedHours, 2) }}</p>
                </div>
            </x-white-card-container>

            <x-white-card-container color="accent/50" class="p-3 items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-accent/10 text-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-400">Actual Hours</p>
                    <p class="text-lg font-bold text-main leading-tight">{{ number_format($totalActualHours, 2) }}</p>
                </div>
            </x-white-card-container>

            <x-white-card-container class="hover:border-success/80 p-3 items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-success/10 text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-400">Completion Rate</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-lg font-bold text-main leading-tight">{{ number_format($avgCompletionRate, 1) }}%</p>
                        <span class="text-[10px] font-bold {{ $totalVarianceHours >= 0 ? 'text-success' : 'text-danger' }}">
                            ({{ $totalVarianceHours >= 0 ? '+' : '' }}{{ number_format($totalVarianceHours, 1) }})
                        </span>
                    </div>
                </div>
            </x-white-card-container>

        </div>

        {{-- CHARTS --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[2fr_1fr]">
            <x-white-card-container color="primary/50" class="p-6 flex flex-col">
                <div class="mb-4">
                    <h4 class="text-lg font-semibold text-main">Workforce Health</h4>
                    <p class="text-sm text-muted-500">Expected hours vs actual hours for the biggest gaps.</p>
                </div>
                <div class="relative h-[420px]">
                    <canvas id="monthlyHoursChart"></canvas>
                </div>
            </x-white-card-container>

            <x-white-card-container color="primary/50" class="p-6 flex flex-col">
                <div class="mb-4">
                    <h4 class="text-lg font-semibold text-main">Monthly status distribution</h4>
                    <p class="text-sm text-muted-500">How many employees are ahead, on track, or behind.</p>
                </div>
                <div class="relative h-[420px]">
                    <canvas id="monthlyStatusChart"></canvas>
                </div>
            </x-white-card-container>
        </div>

        {{-- MONTHLY TABLE --}}
        <x-white-card-container color="secondary/50" class="overflow-hidden flex-col">
            <div class="flex items-center justify-between border-b border-muted-200 px-5 py-4">
                <div>
                    <h4 class="text-lg font-semibold text-main">Individual Monthly Attendance</h4>
                    <p class="text-sm text-muted-500">This is now the main section of the page.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-muted-50 text-xs uppercase tracking-wider text-muted-500">
                        <tr>
                            <th class="px-5 py-4 text-left font-semibold">Employee</th>
                            <th class="px-4 py-4 text-center font-semibold">Expected</th>
                            <th class="px-4 py-4 text-center font-semibold">Actual</th>
                            <th class="px-4 py-4 text-center font-semibold">Completion</th>
                            <th class="px-4 py-4 text-center font-semibold">Difference</th>
                            <th class="px-5 py-4 text-center font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-muted-100 text-sm">
                        @forelse($attendanceReports as $report)
                            @php
                                $expected = (float) $report['expected_hours'];
                                $actual = (float) $report['actual_hours'];
                                $variance = (float) $report['variance'];
                                $completion = $expected > 0 ? min(100, round(($actual / $expected) * 100, 1)) : 0;

                                $barClass = $completion >= 100
                                    ? 'bg-success'
                                    : 'bg-accent';
                            @endphp

                            <tr class="hover:bg-muted-50 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-main">{{ $report['user']->name }}</div>
                                    <div class="text-xs text-muted-500">{{ $report['user']->username }}</div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="font-semibold text-main">{{ number_format($expected, 2) }}</div>
                                    <div class="text-xs text-muted-500">hrs</div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="font-semibold text-main">{{ number_format($actual, 2) }}</div>
                                    <div class="text-xs text-muted-500">hrs</div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="mx-auto max-w-[170px]">
                                        <div class="mb-2 flex items-center justify-between text-xs text-muted-500">
                                            <span>Progress</span>
                                            <span>{{ number_format($completion, 1) }}%</span>
                                        </div>
                                        <div class="h-2.5 w-full rounded-full bg-muted-100">
                                            <div
                                                class="h-2.5 rounded-full {{ $barClass }}"
                                                style="width: {{ $completion }}%;"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-lg px-3 py-2 text-sm font-semibold {{ $variance >= 0 ? 'bg-success/5 text-success' : 'bg-danger/10 text-danger' }}">
                                        {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 2) }}h
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    @if($variance >= 2)
                                        <span class="inline-flex items-center rounded-full bg-success/5 px-3 py-1 text-xs font-semibold text-success">
                                            Ahead
                                        </span>
                                    @elseif($variance > -2 && $variance < 2)
                                        <span class="inline-flex items-center rounded-full bg-secondary/10 px-3 py-1 text-xs font-semibold text-secondary">
                                            On Track
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-danger/10 px-3 py-1 text-xs font-semibold text-danger">
                                            Behind
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-muted-500">
                                    No monthly attendance data for this month.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-white-card-container>

        {{-- DAILY ATTENDANCE - DE-EMPHASIZED --}}
        <x-white-card-container color="secondary/50" class="overflow-hidden flex-col">
            <details id="daily-attendance-section" class="group" @if(request()->hasAny(['username', 'status', 'date_from', 'date_to'])) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4">
                    <div>
                        <h4 class="text-lg font-semibold text-main">Daily Attendance Logs</h4>
                        <p class="text-sm text-muted-500">Secondary section for daily inspection and auditing.</p>
                    </div>

                    <div class="flex items-center gap-1 text-sm font-medium text-primary">
                        <span>Show details</span>
                        <svg class="h-4 w-4 transition-transform duration-200 -rotate-90 group-open:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </summary>

                <div class="border-t border-muted-200">
                    {{-- INFO ALERT --}}
                    <div id="info-alert" class="relative m-5 flex flex-col items-start justify-between gap-3 rounded-2xl bg-primary/5 p-4 text-primary md:flex-row md:items-center">
                        <div class="flex gap-3">
                            <div class="shrink-0 rounded-lg bg-primary/10 p-2 text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-current" viewBox="0 0 512 512">
                                    <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-208a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="mb-1 text-sm font-bold uppercase tracking-wide opacity-80">{{ __('checkin_logs.checkin_policy') }}</h4>
                                <p class="text-sm leading-relaxed">
                                    {{ __('checkin_logs.checkin_policy_details_1') }}
                                    <span class="rounded bg-red-100 px-1.5 py-0.5 text-xs font-bold text-red-600">
                                        {{ __('checkin_logs.late') }}
                                    </span>
                                    {{ __('checkin_logs.checkin_policy_details_2') }}
                                </p>
                            </div>
                        </div>

                        <button id="close-info" type="button" class="absolute right-2 top-2 rounded-lg p-1.5 text-primary transition hover:bg-primary/10 hover:text-primary/80 md:relative md:right-auto md:top-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- KEEP YOUR EXISTING FILTER FORM HERE --}}
                    <form class="border-y border-muted-200 bg-white p-5 flex flex-wrap gap-4" method="GET">
                        <x-form.search-input
                            name="username"
                            placeholder="checkin_logs.search_placeholder_username"
                            :value="request('username')"
                        />

                        <div class="relative group">
                            <span class="absolute -top-2 left-3 bg-white px-1 text-xs font-medium text-muted-400 group-focus-within:text-primary transition-colors">
                                {{ __('checkin_logs.filter_label_from') }}
                            </span>
                            <x-form.input type="date" name="date_from" :value="request('date_from')" />
                        </div>

                        <div class="relative group">
                            <span class="absolute -top-2 left-3 bg-white px-1 text-xs font-medium text-muted-400 group-focus-within:text-primary transition-colors">
                                {{ __('checkin_logs.filter_label_to') }}
                            </span>
                            <x-form.input type="date" name="date_to" :value="request('date_to')" />
                        </div>

                        <x-form.select
                            name="status"
                            placeholder="checkin_logs.filter_option_all_statuses"
                            :value="request('status')"
                            :options="[
                                'late' => __('checkin_logs.filter_option_late'),
                                'on_time' => __('checkin_logs.filter_option_on_time'),
                            ]"
                        />

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
                            <button type="submit" class="rounded-xl border border-muted-200 px-3 py-2.5 text-muted-500 transition-colors hover:border-primary/30 hover:bg-primary/5 hover:text-primary">
                                Filter
                            </button>
                            <a href="{{ route('users.checkin_index') }}" class="flex items-center justify-center rounded-xl border border-muted-200 px-3 py-2.5 text-muted-500 transition-colors hover:border-primary/30 hover:bg-primary/5 hover:text-primary">
                                Reset
                            </a>
                        </div>
                    </form>

                    {{-- DAILY TABLE --}}
                    <div class="h-[360px] overflow-auto">
                        <table class="min-w-full table-fixed">
                            <thead class="sticky top-0 z-10 border-b border-muted-200 bg-muted-50">
                                <tr>
                                    <th class="w-[5%] py-4 pl-6 text-left text-xs font-semibold uppercase tracking-wider text-muted-400">ID</th>
                                    <th class="w-[20%] px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-muted-400">{{ __('checkin_logs.table_header_user_name') }}</th>
                                    <th class="w-[15%] px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-muted-400">{{ __('checkin_logs.table_header_date') }}</th>
                                    <th class="w-[20%] px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-muted-400">{{ __('checkin_logs.table_header_check_in_time') }}</th>
                                    <th class="w-[15%] px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-muted-400">{{ __('checkin_logs.table_header_check_out_time') }}</th>
                                    <th class="w-[15%] px-3 py-4 text-left text-xs font-semibold uppercase tracking-wider text-muted-400">{{ __('checkin_logs.table_header_working_hours') }}</th>
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

                                                    @if (!empty($log->is_half_day_off) && $log->is_half_day_off)
                                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                            Half Day
                                                        </span>
                                                    @elseif (!empty($log->is_full_day_off) && $log->is_full_day_off)
                                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                                            Full Off
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted-400">-</span>
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

                    @if ($checkIns instanceof \Illuminate\Contracts\Pagination\Paginator && $checkIns->hasPages())
                        <div class="flex justify-center px-5 py-5">
                            {{ $checkIns->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                        </div>
                    @endif
                </div>
            </details>
        </x-white-card-container>
    </div>

    <script>
        window.monthlyAttendanceChartData = {{ Js::from($chartPayload) }};
    </script>
@endsection
