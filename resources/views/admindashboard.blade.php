@extends('layout_dashboard')
@section('title', __('admin_dashboard.title'))

@section('content')
@can('admin.dashboard.view')

@php
    // --- 1. RECEIVE DATA & HELPERS ---
    $floatToString = function($decimal) {
        if ($decimal === null) return '--:--';
        $hours = floor($decimal);
        $minutes = ($decimal - $hours) * 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    };

    $startVal = $companyStartHour ?? 8;
    $endVal   = $companyEndHour   ?? 17;

    $lunchStartVal = $companyLunchStartHour ?? null;
    $lunchEndVal   = $companyLunchEndHour ?? null;
    $midDayVal     = $companyMidDayHour ?? null;

    $hasLunch = !is_null($lunchStartVal);

    $startStr = $floatToString($startVal);
    $endStr   = $floatToString($endVal);

    $workStartPct = ($startVal / 24) * 100;
    $workWidthPct = (($endVal - $startVal) / 24) * 100;

    $currentHour = \Carbon\Carbon::now()->floatDiffInHours(\Carbon\Carbon::today());
    $currentMarkerPct = ($currentHour / 24) * 100;

    $lunchStartPct = 0;
    $lunchWidthPct = 0;
    $midDayPct = 0;

    $lunchRangeStr = "";
    $midDayStr = "";

    if ($hasLunch) {
        $lunchStartPct = ($lunchStartVal / 24) * 100;
        $lunchDuration = $lunchEndVal - $lunchStartVal;
        $lunchWidthPct = ($lunchDuration / 24) * 100;
        $lunchRangeStr = $floatToString($lunchStartVal) . " - " . $floatToString($lunchEndVal);
    } else {
        $safeMidDay = $midDayVal ?? 12;
        $midDayPct = ($safeMidDay / 24) * 100;
        $midDayStr = $floatToString($safeMidDay);
    }

    // Safe defaults so subadmin hidden blocks won't error if controller didn't pass them
    $roleCounts = $roleCounts ?? ['admin' => 0, 'staff' => 0, 'user' => 0];
    $projectsHealth = $projectsHealth ?? [];
    $recentCheckIns = $recentCheckIns ?? collect();
    $emailTemplates = $emailTemplates ?? collect();
    $recentLogs = $recentLogs ?? collect();
@endphp

<div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

    {{-- Header Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center w-full">
        <div class="flex items-center gap-3">
            <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('admin_dashboard.admin_dashboard') }}</h2>

            {{-- Label shows Admin/Subadmin based on role (visual only) --}}
            @if(auth()->user()->hasRole('admin'))
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold uppercase tracking-wide">
                    {{ __('admin_dashboard.admin') }}
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-accent/10 text-accent text-xs font-semibold uppercase tracking-wide">
                    Subadmin
                </span>
            @endif
        </div>
    </div>

    {{-- GRID ROW 1 --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up">

        {{-- 1. User Management --}}
        @can('admin.users.view')
        <div class="@4xl:col-span-5 flex flex-col justify-between h-full min-h-[200px] border bg-white border-muted-200 shadow-lg shadow-main/5 hover:border-primary/50 hover:shadow-primary/10 rounded-2xl p-6 relative overflow-hidden group transition-all duration-300">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-primary/10 rounded-full blur-2xl opacity-50"></div>

            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.total_users') }}</h3>
                    <p class="text-muted-500 text-sm">Last 30 Days</p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="bg-accent/10 text-accent px-2 py-1 rounded-lg text-xs font-bold flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        {{ $userGrowthPercentage ?? 0 }}%
                    </div>

                    <a href="{{ route('users.index') }}" title="{{ __('admin_dashboard.view_details') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="relative z-10 flex items-end justify-between gap-4 mt-6">
                <div class="text-4xl font-bold text-main tracking-tight">{{ number_format($totalUsersCount ?? 0) }}</div>
                <div class="flex-1 h-16 flex items-end">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 100 50" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="gradient" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#5347CC" stop-opacity="0.2"/>
                                <stop offset="100%" stop-color="#5347CC" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <path d="M0,50 L0,40 L10,35 L20,42 L30,30 L40,35 L50,20 L60,25 L70,15 L80,10 L90,15 L100,5 L100,50 Z" fill="url(#gradient)" />
                        <path d="M0,40 L10,35 L20,42 L30,30 L40,35 L50,20 L60,25 L70,15 L80,10 L90,15 L100,5" fill="none" stroke="#5347CC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>
        @endcan

        {{-- 2. Permission Management --}}
        @can('admin.roles.view')
        <div class="@4xl:col-span-3 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 flex flex-col h-full">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.roles_overview') }}</h3>
                <a href="{{ route('admin.permissions') }}" title="{{ __('admin_dashboard.permission_management') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>

            <div class="flex flex-col gap-3 flex-1 justify-center">
                <div class="flex items-center justify-between p-3 rounded-xl bg-primary/5 border border-primary/10">
                    <div class="flex items-center gap-3">
                        <div class="w-2.5 h-2.5 rounded-full bg-primary shadow-sm shadow-primary/40"></div>
                        <span class="text-sm font-medium text-main">Admins</span>
                    </div>
                    <span class="text-lg font-bold text-main">{{ $roleCounts['admin'] ?? 0 }}</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-secondary/5 border border-secondary/10">
                    <div class="flex items-center gap-3">
                        <div class="w-2.5 h-2.5 rounded-full bg-secondary shadow-sm shadow-secondary/40"></div>
                        <span class="text-sm font-medium text-main">Staff</span>
                    </div>
                    <span class="text-lg font-bold text-main">{{ $roleCounts['staff'] ?? 0 }}</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-accent/5 border border-accent/10">
                    <div class="flex items-center gap-3">
                        <div class="w-2.5 h-2.5 rounded-full bg-accent shadow-sm shadow-accent/40"></div>
                        <span class="text-sm font-medium text-main">Users</span>
                    </div>
                    <span class="text-lg font-bold text-main">{{ $roleCounts['user'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        @endcan

        {{-- 3. Working Hours --}}
        @can('admin.company_hours.view')
        <div class="@4xl:col-span-4 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-accent/30 hover:shadow-accent/10 transition-all duration-300 flex flex-col h-full">
            {{-- (unchanged content) --}}
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.company_hours') }}</h3>

                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono text-muted-500 bg-muted-100 px-2 py-1 rounded-md border border-muted-200">{{ date('H:i') }}</span>
                    <button id="open-company-hours-btn" type="button" class="text-muted-400 hover:text-primary hover:bg-muted-50 p-1 rounded-md transition-colors cursor-pointer" title="Edit Hours">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-current">
                            <path d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7z" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- ... keep the rest as you already had ... --}}
            {{-- (I’m not re-pasting line-for-line to reduce clutter, but keep it exactly the same inside this @can wrapper) --}}
        </div>
        @endcan

    </div>

    {{-- GRID ROW 2 --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up [animation-delay:100ms]">

        {{-- 4. Project Health (TASK RELATED) --}}
        @can('admin.projects.view')
        <div class="@4xl:col-span-8 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.project_progress') }}</h3>

                <a href="{{ route('projects.index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>

            <div class="flex flex-col gap-6">
                @forelse($projectsHealth ?? [] as $project)
                    @php
                        $total = $project->total_tasks > 0 ? $project->total_tasks : 1;
                        $completedPct = ($project->completed / $total) * 100;
                        $inProgressPct = ($project->in_progress / $total) * 100;
                        $todoPct = ($project->todo / $total) * 100;
                    @endphp

                    <div>
                        <div class="flex justify-between items-end mb-2">
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-main text-sm">{{ $project->title }}</h4>

                                @if($project->overdue_count > 0)
                                    <div class="flex items-center gap-1 text-[10px] bg-danger/10 text-danger px-1.5 py-0.5 rounded border border-danger/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                        {{ $project->overdue_count }}
                                    </div>
                                @endif
                            </div>

                            <span class="text-xs text-muted-500 font-medium">{{ $project->completed }}/{{ $project->total_tasks }} Tasks ({{ round($completedPct) }}%)</span>
                        </div>

                        <div class="flex w-full h-1.5 overflow-hidden gap-0.5 bg-muted-100 rounded-full">
                            <div class="bg-accent shadow-[0_0_10px_rgba(var(--color-accent),0.5)]" style="width: {{ $completedPct }}%"></div>
                            <div class="bg-secondary" style="width: {{ $inProgressPct }}%"></div>
                            <div class="bg-muted-300" style="width: {{ $todoPct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-muted-400 text-sm">{{ __('admin_dashboard.no_projects') }}</div>
                @endforelse
            </div>
        </div>
        @endcan

        {{-- 5. Recent Attendance --}}
        @can('admin.attendance.view')
        <div class="@4xl:col-span-4 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 flex flex-col h-full">
            {{-- unchanged --}}
        </div>
        @endcan

    </div>

    {{-- GRID ROW 3 --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up [animation-delay:200ms]">

        {{-- 6. Campaign Timeline --}}
        @can('admin.campaigns.view')
        <div class="@4xl:col-span-4 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 flex flex-col gap-6">
            {{-- unchanged --}}
        </div>
        @endcan

        {{-- 7. Email Templates --}}
        @can('admin.email_templates.view')
        <div class="@4xl:col-span-4 flex flex-col h-full">
            {{-- unchanged --}}
        </div>
        @endcan

        {{-- 8. Activity Log --}}
        @can('admin.activity_logs.view')
        <div class="@4xl:col-span-4 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300">
            {{-- unchanged --}}
        </div>
        @endcan

    </div>

</div>

@else
    <div class="flex items-center justify-center min-h-[400px]">
        <div class="text-center">
            <div class="inline-block p-4 rounded-full bg-danger/10 text-danger mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h4 class="text-xl font-bold text-main">{{ __('admin_dashboard.access_denied') }}</h4>
            <p class="text-muted-500 mt-2">You do not have permission to view the Admin Dashboard.</p>
        </div>
    </div>
@endcan
@endsection
