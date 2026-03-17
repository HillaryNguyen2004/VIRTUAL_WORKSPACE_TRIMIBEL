@extends('layout_dashboard')

@section('title', __('admin_dashboard.title'))

@section('content')
@can('admin.dashboard.view')

    @php
        // --- 1. RECEIVE DATA & HELPERS ---

        // Helper: Convert decimal hour (12.5) to string ("12:30")
        $floatToString = function($decimal) {
            if ($decimal === null) return '--:--';
            $hours = floor($decimal);
            $minutes = ($decimal - $hours) * 60;
            return sprintf('%02d:%02d', $hours, $minutes);
        };

        // Grab variables from Controller (Controller now sends floats like 8.5)
        // IMPORTANT: Defaults should match Controller defaults if null is passed unexpectedly
        $startVal = $companyStartHour ?? 8;
        $endVal   = $companyEndHour   ?? 17;

        // Check mode based on NULL values passed from Controller
        // If lunch is NULL in DB, Controller sends NULL. If Midday is NULL, Controller sends NULL.
        $lunchStartVal = $companyLunchStartHour; // Can be null
        $lunchEndVal   = $companyLunchEndHour;   // Can be null
        $midDayVal     = $companyMidDayHour;     // Can be null

        // --- 2. DETERMINE MODE & VALUES ---

        // Logic: If Lunch variables exist (are not null), use Lunch Mode.
        // If Lunch is null but Midday exists, use Midday Mode.
        // If BOTH are null (shouldn't happen with correct DB defaults, but safe fallback), default to Lunch.

        $hasLunch = !is_null($lunchStartVal); // Strict null check

        // Initialize Display Strings and Math Values
        $startStr = $floatToString($startVal);
        $endStr   = $floatToString($endVal);

        // Math Percentages (Always based on 24hr clock)
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
            // --- LUNCH MODE ---
            $lunchStartPct = ($lunchStartVal / 24) * 100;
            $lunchDuration = $lunchEndVal - $lunchStartVal;
            $lunchWidthPct = ($lunchDuration / 24) * 100;

            $lunchRangeStr = $floatToString($lunchStartVal) . " - " . $floatToString($lunchEndVal);
        } else {
            // --- MIDDAY MODE ---
            // If midDayVal is null here (rare fallback), default to 12 for safety
            $safeMidDay = $midDayVal ?? 12;

            $midDayPct = ($safeMidDay / 24) * 100;
            $midDayStr = $floatToString($safeMidDay);
        }
    @endphp

    {{-- Main Container (Matches User Dashboard) --}}
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex flex-col @2xl:flex-row gap-4 justify-between @2xl:items-center w-full">
            <div class="flex flex-col sm:justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">
                        {{ __('admin_dashboard.admin_dashboard') }}</h1>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold uppercase tracking-wide">
                        @if(auth()->user()->hasRole('admin'))
                            {{ __('admin_dashboard.admin') }}
                        @else
                            Subadmin
                        @endif
                    </span>
                </div>
                <p class="text-muted-500 text-sm md:text-base mt-1">{{ __('user_dashboard.subheading') }}</p>
            </div>

            @if(auth()->user()->hasRole('subadmin'))
                <div class="flex gap-4 ">
                    {{-- Check In Button --}}
                    <a href="{{ route('checkin.face.page', 'checkin') }}"
                        class="group flex items-center gap-3 rounded-xl bg-primary-gradient px-6 py-3 text-white text-md md:text-base font-semibold shadow-lg shadow-primary/20 transition-all hover:opacity-90 focus:ring-4 focus:ring-primary/30 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-log-in-icon lucide-log-in">
                                <path d="m10 17 5-5-5-5"/>
                                <path d="M15 12H3"/>
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            </svg>
                        {{ __('user_dashboard.check_in') }}
                    </a>

                    {{-- Check Out Button --}}
                    <a href="{{ route('checkin.face.page', 'checkout') }}"
                        class="group flex items-center gap-3 rounded-xl bg-primary-gradient px-6 py-3 text-white text-md md:text-base font-semibold shadow-lg shadow-primary/20 transition-all hover:opacity-90 focus:ring-4 focus:ring-primary/30 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-log-out-icon lucide-log-out"><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>
                        {{ __('user_dashboard.check_out') }}
                    </a>
                </div>
            @endif
        </div>
        


        {{-- MAIN GRID CONTAINER --}}
        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full">

            {{-- Users --}}
            @can('admin.users.view')
                <div class="@4xl:col-span-12 flex flex-col @3xl:flex-row h-full min-h-[200px] gap-6 @container animate-fade-in-up [animation-delay:100ms]">
                    {{-- Online Users --}}
                    <x-white-card-container color="primary/50" class="p-6 w-full flex-col justify-between h-full">
                        <div class="relative z-10 flex justify-between items-start">
                            <div class="flex gap-1">
                                <h3 class="text-md md:text-lg font-semibold tracking-tight">
                                    Online Users
                                </h3>
                                <div class="flex items-center">
                                    <p class="text-muted-500 text-md md:text-lg">(</p>
                                    <p id="active-users-count" class="text-muted-500 text-md md:text-lg">
                                        <svg class="animate-spin h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </p>    
                                    <p class="text-muted-500 text-md md:text-lg">)</p>
                                </div>
                            </div>

                            {{-- Action Area --}}
                            <div class="flex items-center gap-2">
                                <!-- <div class="bg-accent/10 text-accent px-2 py-1 rounded-lg text-xs font-bold flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                    {{ $userGrowthPercentage ?? 0 }}%
                                </div> -->

                                <a href="{{ route('admin.users.index') }}" class="text-xs md:text-sm text-primary font-medium hover:underline">View All</a>
                            </div>
                        </div>

                        <div class="relative flex items-end justify-between gap-6 mt-2">
                            <div id="active-users-list" class="w-full max-h-[360px] @3xl:max-h-[220px] overflow-y-auto pt-2">
                                {{-- A loading state while JS fetches the data --}}
                                <div class="flex items-center justify-center h-40 text-gray-500">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading active users...
                                </div>
                            </div>
                        </div>
                    </x-white-card-container>

                    {{-- Department Distribution (Donut Chart) --}}
                    @can('admin.roles.view') {{-- Update this permission if needed --}}
                        <x-white-card-container color="primary/50" class="p-6 w-full flex-col justify-between">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-semibold text-main">Departments Overview</h3>
                                <a href="{{ route('admin.permissions') }}" class="text-xs md:text-sm text-primary font-medium hover:underline">View Permissions</a>

                            </div>

                            {{-- Chart Container --}}
                            <div class="flex flex-col @container mt-2 flex-1 justify-center">
                                {{-- At small sizes it stacks, at larger sizes (using container queries) it goes side-by-side --}}
                                <div class="flex flex-col @xs:flex-row items-center gap-6 w-full">
                                    
                                    {{-- LEFT: The Donut & Center Text --}}
                                    <div class="relative w-[160px] h-[160px] flex-shrink-0">
                                        <canvas id="departmentDonutChart"></canvas>
                                        
                                        {{-- Notice we removed pb-8 because the canvas legend is gone, so it centers perfectly now --}}
                                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                            <span class="text-2xl md:text-3xl font-semibold leading-none">{{ $totalUsersCount }}</span>
                                            <span class="font-medium text-muted-500 uppercase tracking-widest text-xs">Users</span>
                                        </div>
                                    </div>

                                    {{-- RIGHT: The Custom HTML Legend --}}
                                    <div id="custom-legend" class="flex flex-col flex-1 w-full min-w-0">
                                        {{-- JavaScript will inject the legend items here --}}
                                    </div>

                                </div>
                            </div>
                        </x-white-card-container>
                    @endcan
                </div>

                
            @endcan

            {{-- TWO COLUMN SPLIT VIEW - LEFT --}}
            <div class="@4xl:col-span-8 flex flex-col gap-6 h-full">
                {{-- 1. Daily Attendance --}}
                @can('admin.attendance.view')
                    <div class="@container flex flex-row w-full gap-6 animate-fade-in-up [animation-delay:150ms]">
                        {{-- Statistics Chart --}}
                        <x-white-card-container color="primary/50" class="p-6 w-full flex-col justify-between h-full">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-semibold text-main">Attendance Statistics</h3>
                                <!-- <a href="{{ route('admin.users.index') }}" class="text-xs md:text-sm text-primary font-medium hover:underline">View All</a> -->
                                <a href="{{ route('users.checkin_index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-primary/5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>

                            {{-- Custom Legend --}}
                            <div class="flex items-end justify-between mb-4">
                                <div class="flex items-center gap-4 text-sm text-muted-500 font-medium">
                                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-primary"></span> Present</div>
                                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-secondary"></span> Absent</div>
                                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-accent"></span> Leave</div>
                                </div>
                                {{-- Dropdown matching the image --}}
                                <div class="relative z-20">
                                    <select id="attendanceTimeframe" class="appearance-none bg-white border border-muted-300 text-sm text-main font-medium rounded-xl px-4 py-2 pr-8 cursor-pointer">
                                        <option value="weekly">Weekly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Chart Canvas --}}
                            <div class="relative w-full flex-1 min-h-[250px]">
                                <canvas id="attendanceBarChart"></canvas>
                            </div>
                        </x-white-card-container>

                        {{-- Recent Attendance --}}
                        <x-white-card-container color="primary/50" class="hidden @3xl:block p-6 w-1/2 flex-col justify-between h-full">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.recent_attendance') }}</h3>
                                <a href="{{ route('users.checkin_index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-primary/5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>

                            <div class="overflow-y-visible max-h-[300px] pr-2 custom-scrollbar">
                                @if($recentCheckIns->isNotEmpty())
                                    <ul class="flex flex-col gap-4">
                                        @foreach($recentCheckIns->take(5) as $log)
                                            <li class="flex items-center gap-4 group">
                                                {{-- Dynamic Avatar Logic --}}
                                                @php
                                                    // Try to get photo from flat properties or relation
                                                    $photoData = $log->user_profile_photo ?? $log->avatar ?? ($log->user->profile_photo ?? ($log->user->avatar ?? null));
                                                    $userName = $log->user_name ?? ($log->user->name ?? 'U');
                                                    $userId = $log->user_id ?? ($log->user->id ?? 0);
                                                    $initial = strtoupper(mb_substr($userName, 0, 1));
                                                    
                                                    $colors = ['bg-primary/10 text-primary', 'bg-secondary/10 text-secondary', 'bg-accent/20 text-accent'];
                                                    $colorClass = $colors[$userId % count($colors)];
                                                @endphp

                                                @if($photoData)
                                                    <img src="{{ str_starts_with($photoData, 'http') ? $photoData : asset('storage/' . $photoData) }}" 
                                                        alt="{{ $userName }}" 
                                                        class="h-10 w-10 rounded-full object-cover flex-shrink-0">
                                                @else
                                                    <div class="h-10 w-10 rounded-full {{ $colorClass }} grid place-items-center font-bold text-sm flex-shrink-0">
                                                        {{ $initial }}
                                                    </div>
                                                @endif

                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-main truncate">{{ $userName }}</p>
                                                    <div class="flex items-center gap-2 text-xs text-muted-400">
                                                        <span class="flex items-center gap-1 text-primary font-medium">
                                                            {{ $log->check_in_time ?? '--:--' }}
                                                        </span>
                                                        <span>•</span>
                                                        <span>{{ $log->date }}</span>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="text-center py-6 text-muted-400 text-sm">{{ __('admin_dashboard.no_check_ins') }}</div>
                                @endif
                            </div>
                        </x-white-card-container>
                    </div>
                @endcan

                {{-- 2. Project Progress --}}
                @can('admin.projects.view')
                    <x-white-card-container color="primary/50" class="p-6 w-full flex-col justify-between h-full animate-fade-in-up [animation-delay:200ms]">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.project_progress') }}</h3>

                            <a href="{{ route('projects.index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-primary/5">
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
                                            {{-- NOTE: Changed from $project->name to $project->title based on your Model --}}
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
                    </x-white-card-container>
                @endcan

                <div class="flex flex-col @2xl:flex-row w-full gap-6 animate-fade-in-up [animation-delay:300ms]">
                    {{-- 3. Campaigns --}}
                    @can('admin.campaigns.view')
                        <x-dashboard_widgets.campaigns :upcomingCampaigns="$upcomingCampaigns" :sentCampaigns="$sentCampaigns" class="w-full h-full"/>
                    @endcan

                    {{-- 4. Email Templates --}}
                    @can('admin.email_templates.view')
                        <x-dashboard_widgets.email_templates :emailTemplates="$emailTemplates" class="w-full"/>
                    @endcan
                </div>
            </div>


            {{-- TWO COLUMN SPLIT VIEW - RIGHT --}}
            <div class="@4xl:col-span-4 flex flex-col gap-6">
                {{-- 1. Working Hours --}}
                @can('admin.company_hours.view')
                    <x-white-card-container color="accent/50" class="p-6 w-full flex-col justify-between animate-fade-in-up [animation-delay:150ms]">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.company_hours') }}</h3>

                            <div class="flex items-center gap-3">
                                <span class="text-xs font-mono text-muted-500 bg-muted-100 px-2 py-1 rounded-md border border-muted-200">{{ date('H:i') }}</span>
                                <button id="open-company-hours-btn" type="button" class="text-muted-400 hover:text-primary hover:bg-primary/5 p-1 rounded-md transition-colors cursor-pointer" title="Edit Hours">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-current">
                                        <path d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col justify-between">
                            <div class="my-auto">
                                {{-- TIMELINE BAR --}}
                                <div class="relative w-full h-8 bg-muted-100 rounded-full overflow-hidden shadow-inner">
                                    {{-- 1. Main Work Shift --}}
                                    <div class="absolute top-0 bottom-0 bg-accent/20 border-x border-accent/50"
                                        style="left: {{ $workStartPct }}%; width: {{ $workWidthPct }}%;"></div>

                                    {{-- 2. Conditional: Lunch vs Midday --}}
                                    @if($hasLunch)
                                        {{-- Lunch Block (Solid Range) --}}
                                        <div class="absolute top-0 bottom-0 bg-secondary/40 border-x border-secondary/60"
                                            style="left: {{ $lunchStartPct }}%; width: {{ $lunchWidthPct }}%;"></div>
                                    @else
                                        {{-- Midday Marker (Dashed Line) --}}
                                        <div class="absolute top-0 bottom-0 border-l-2 border-dashed border-secondary z-10"
                                            style="left: {{ $midDayPct }}%;"></div>
                                    @endif

                                    {{-- 3. Current Time Marker --}}
                                    <div class="absolute top-0 bottom-0 w-0.5 bg-danger z-20 shadow-[0_0_8px_rgba(239,68,68,0.8)]"
                                        style="left: {{ $currentMarkerPct }}%;"></div>
                                </div>

                                {{-- TIMELINE LABELS --}}
                                <div class="relative w-full h-6 mt-2 text-xs text-muted-400 font-medium">
                                    <span class="absolute left-0">00:00</span>

                                    {{-- Start Time Label --}}
                                    <span class="absolute -translate-x-1/2 text-main font-bold" style="left: {{ $workStartPct }}%;">
                                        {{ $startStr }}
                                    </span>

                                    {{-- End Time Label --}}
                                    <span class="absolute -translate-x-1/2 text-main font-bold" style="left: {{ $workStartPct + $workWidthPct }}%;">
                                        {{ $endStr }}
                                    </span>

                                    <span class="absolute right-0">23:59</span>
                                </div>
                            </div>

                            {{-- LEGEND (BOTTOM ROW) --}}
                            <div class="flex items-center gap-6 mt-4 pt-4 border-t border-muted-200">
                                {{-- Work Legend --}}
                                <div class="flex items-center gap-2 w-full">
                                    <div class="w-2.5 h-2.5 rounded-full bg-accent/50 border border-accent"></div>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[10px] uppercase text-muted-400 font-bold tracking-wider">{{ __('admin_dashboard.work') }}</span>
                                        <span class="text-xs text-main font-medium leading-none">{{ $startStr }} - {{ $endStr }}</span>
                                    </div>
                                </div>

                                {{-- Conditional Legend: Lunch or Midday --}}
                                @if($hasLunch)
                                    <div class="flex items-center gap-2 w-full">
                                        <div class="w-2.5 h-2.5 rounded-full bg-secondary/60 border border-secondary"></div>
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[10px] uppercase text-muted-400 font-bold tracking-wider">{{ __('admin_dashboard.lunch') }}</span>
                                            <span class="text-xs text-main font-medium leading-none">{{ $lunchRangeStr }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 w-full">
                                        {{-- Purple Marker for Midday --}}
                                        <div class="w-2.5 h-0.5 bg-secondary border-none"></div>
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[10px] uppercase text-muted-400 font-bold tracking-wider">{{ __('admin_dashboard.midday') }}</span>
                                            <span class="text-xs text-main font-medium leading-none">{{ $midDayStr }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-white-card-container>
                @endcan

                {{-- 2. Upcoming Holidays --}}
                @can('admin.holidays.view')
                    <div class="grid grid-cols-1 gap-6 w-full animate-fade-in-up [animation-delay:200ms]">
                        <div class=" flex flex-col h-full">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-main">Upcoming Holidays</h3>
                                @can('admin.holidays.create')
                                    <button id="openHolidayModal" title="Add Holiday" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-primary/5">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-plus-icon lucide-plus">
                                            <path d="M5 12h14"/>
                                            <path d="M12 5v14"/>
                                        </svg>
                                    </button>
                                @endcan
                            </div>

                            {{-- Holidays Grid --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                @forelse($upcomingHolidays ?? [] as $holiday)
                                    <div onclick="openHolidayModal('edit', {{ json_encode($holiday) }})"
                                        class="group flex items-center gap-4 p-4 rounded-2xl bg-white border border-muted-200 shadow-lg shadow-main/5 hover:border-accent/30 hover:shadow-accent/10 transition-all duration-300 cursor-pointer">

                                        {{-- Calendar Icon --}}
                                        <div class="relative w-12 h-12 flex-none bg-accent/5 rounded-xl flex flex-col items-center justify-center border border-accent/10 group-hover:bg-accent/10 transition-colors">
                                            <span class="text-[10px] font-bold text-accent uppercase tracking-wider">{{ $holiday->start_date->format('M') }}</span>
                                            <span class="text-lg font-bold text-main leading-none">{{ $holiday->start_date->format('d') }}</span>
                                        </div>

                                        {{-- Info --}}
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-bold text-main group-hover:text-accent transition-colors truncate">{{ $holiday->title }}</h4>
                                            <p class="text-xs text-muted-500 mt-0.5 truncate">
                                                {{ $holiday->start_date->format('l, H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full text-center py-6 text-muted-400 text-sm border border-dashed border-muted-200 rounded-2xl">
                                        No upcoming holidays found.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- 3. Activity Log --}}
                @can('admin.activity_logs.view')
                    <x-white-card-container color="primary/50" class="p-6 w-full flex-col justify-between animate-fade-in-up [animation-delay:300ms]">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.recent_activities') }}</h3>
                            <a href="{{ route('admin.activity.logs') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-primary/5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                        </div>

                        <div class="relative">
                            @if($recentLogs->isNotEmpty())
                                {{-- Timeline Line --}}
                                <div class="absolute left-4 top-2 bottom-4 w-px bg-muted-300"></div>

                                <ul class="flex flex-col gap-6 relative">
                                    @foreach($recentLogs->take(3) as $log)
                                        <li class="flex gap-4 group">
                                            {{-- Dynamic Avatar Logic replacing the Timeline Dot --}}
                                            @php
                                                $actUser = $log->user ?? null;
                                                $actPhotoData = $actUser->profile_photo ?? ($actUser->avatar ?? null);
                                                $actUserName = $actUser->name ?? 'User';
                                                $actUserId = $actUser->id ?? 0;
                                                $actInitial = strtoupper(mb_substr($actUserName, 0, 1));
                                                
                                                $actColors = ['primary', 'secondary', 'accent'];
                                                $actColorClass = $actColors[$actUserId % count($actColors)];
                                            @endphp

                                            <div class="relative z-10 mt-0.5 flex-shrink-0 w-8 h-8 bg-white rounded-full">
                                                @if($actPhotoData)
                                                    <img src="{{ str_starts_with($actPhotoData, 'http') ? $actPhotoData : asset('storage/' . $actPhotoData) }} " 
                                                        alt="{{ $actUserName }}" 
                                                        class="w-8 h-8 rounded-full object-cover ring-4 ring-white">
                                                @else
                                                    <div class="w-8 h-8 rounded-full text-{{ $actColorClass }} bg-{{ $actColorClass }}/10 flex items-center justify-center font-bold text-xs ring-4 ring-white">
                                                        {{ $actInitial }}
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-main">
                                                    <span class="font-bold text-{{ $actColorClass }}">{{ $actUserName }}</span>
                                                    {{ $log->action }}
                                                </p>
                                                <p class="text-xs text-muted-500 mt-1 line-clamp-2">{{ $log->description }}</p>
                                                <p class="text-[10px] text-muted-400 mt-1.5">{{ $log->created_at->diffForHumans() }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-center py-6 text-muted-400 text-sm">{{ __('admin_dashboard.no_activity_logs') }}</div>
                            @endif
                        </div>
                    </x-white-card-container>
                @endcan
            </div>
        </div>
    </div>

    @include('holidays_modal')

@else
    <div class="flex items-center justify-center min-h-[400px]">
        <div class="text-center">
            <div class="inline-block p-4 rounded-full bg-danger/10 text-danger mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h4 class="text-xl font-bold text-main">{{ __('admin_dashboard.access_denied') }}</h4>
            <p class="text-muted-500 mt-2">You do not have permission to view the Admin Dashboard.</p>
        </div>
    </div>
@endcan
@endsection

@push('scripts')
    @vite(['resources/js/admin/edit_holiday.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const listElement = document.getElementById('active-users-list');
            const countElement = document.getElementById('active-users-count');
            
            
            // Keep a local array of online users so we can add/remove them in real-time
            let currentOnlineUsers = [];

            // 1. Initial Fetch (Get who is online right now)
            fetch('/api/chat/users/online')
                .then(response => response.json())
                .then(data => {
                    if (data && data.success && data.data && data.data.users) {
                        currentOnlineUsers = data.data.users;
                        renderActiveUsers();
                    }
                });

            // 2. Real-Time Listener (Listen for changes via WebSockets)
            if (window.Echo) {
                window.Echo.channel('user-status').listen('.user.status.changed', (e) => {
                    const user = e.user;
                    const status = e.status;

                    if (!user || !user.id) return;

                    if (status === 'online') {
                        // Check if they are already in the array, if not, add them
                        if (!currentOnlineUsers.find(u => u.id === user.id)) {
                            // We push the base user data. The next time they refresh, 
                            // the API will fetch their exact task % and department.
                            currentOnlineUsers.push({
                                ...user,
                                role_name: user.role_name || 'Staff',
                                department_name: user.department_name || 'General',
                                task_completion: user.task_completion || 0
                            });
                        }
                    } else if (status === 'offline') {
                        // Remove user from the list
                        currentOnlineUsers = currentOnlineUsers.filter(u => u.id !== user.id);
                    }

                    renderActiveUsers();
                });
            }

            // 3. Render the UI matching the mockup
            function renderActiveUsers() {
                if (countElement) countElement.innerText = currentOnlineUsers.length;

                if (currentOnlineUsers.length === 0) {
                    listElement.innerHTML = '<div class="text-center text-gray-500 py-10">No users currently online.</div>';
                    return;
                }

                const usersToDisplay = currentOnlineUsers.slice(0, 10);
                let htmlString = '';

                usersToDisplay.forEach(user => {
                    const percentage = user.task_completion || 0;
                    const isHighCompletion = percentage >= 50;
                    const barColor = isHighCompletion ? 'bg-success-light' : 'bg-danger-light';
                    const textColor = isHighCompletion ? 'text-muted-500' : 'text-danger';

                    // --- NEW AVATAR LOGIC ---
                    // Fallback to user.avatar just in case the backend hasn't been updated yet
                    const photoData = user.user_profile_photo || user.avatar; 
                    let avatarHtml = '';

                    if (photoData) {
                        // Has photo: Check if it's already a full URL, otherwise append /storage/
                        const photoUrl = photoData.startsWith('http') ? photoData : `/storage/${photoData}`;
                        avatarHtml = `
                            <div class="relative flex-shrink-0">
                                <img src="${photoUrl}" alt="${user.name}" class="w-12 h-12 rounded-full object-cover">
                                <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full ring-2 ring-white bg-success"></span>
                            </div>
                        `;
                    } else {
                        // No photo: Fallback to realtime.blade colored initials
                        const initial = (user.name || 'U').charAt(0).toUpperCase();
                        const colors = [
                            'bg-primary/10 text-primary',
                            'bg-secondary/10 text-secondary',
                            'bg-accent/20 text-accent',
                        ];
                        // Assign consistent color based on user ID
                        const colorClass = colors[(user.id || 0) % colors.length];
                        
                        avatarHtml = `
                            <div class="relative flex-shrink-0">
                                <div class="w-12 h-12 rounded-full ${colorClass} flex items-center justify-center font-bold text-lg">
                                    ${initial}
                                </div>
                                <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full ring-2 ring-white bg-success"></span>
                            </div>
                        `;
                    }
                    // ------------------------

                    htmlString += `
                        <div class="flex items-center justify-between gap-4 pt-3 pb-3 last:pb-0 border-b border-gray-100 last:border-b-0 animate-fade-in-up">
                            
                            <div class="flex items-center gap-4 flex-1">
                                
                                ${avatarHtml}
                                
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs md:text-sm font-medium truncate">${user.name}</p>
                                    <p class="text-xs text-muted-500 truncate">${user.role_name} - ${user.department_name}</p>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1.5 w-24 flex-shrink-0">
                                <div class="h-1.5 w-full rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full rounded-full ${barColor} transition-all duration-500" style="width: ${percentage}%"></div>
                                </div>
                                <p class="text-xs font-semibold truncate ${textColor}">${percentage}%</p>
                            </div>

                        </div>
                    `;
                });

                listElement.innerHTML = htmlString;
            }

            // --- DEPARTMENT DONUT CHART LOGIC ---
            // Pass the sorted PHP data to Javascript
            const rawData = @json($departmentStats);
            
            // Apply the "Top 4 + Other" Logic we discussed
            let labels = [];
            let chartData = [];
            
            if (rawData.length > 5) {
                const top4 = rawData.slice(0, 4);
                const others = rawData.slice(4).reduce((sum, item) => sum + item.count, 0);
                
                top4.forEach(item => {
                    labels.push(item.name);
                    chartData.push(item.count);
                });
                labels.push('Other'); // Collapse the rest
                chartData.push(others);
            } else {
                rawData.forEach(item => {
                    labels.push(item.name);
                    chartData.push(item.count);
                });
            }

            const palette = [
                '#5347CC', // primary.DEFAULT
                '#4896FE', // secondary.DEFAULT
                '#17C8C6', // accent.DEFAULT
                '#766CD6', // primary.light (for a 4th slice if needed)
                '#E5E7EB'  // muted.200 (Perfect for the 'Other' slice)
            ];

            const ctx = document.getElementById('departmentDonutChart');
            if (ctx && chartData.length > 0) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: chartData,
                            backgroundColor: palette.slice(0, chartData.length),
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        layout: { padding: 0 }, // Removed padding since native legend is gone
                        plugins: {
                            // 1. Hide the default Chart.js legend
                            legend: {
                                display: false 
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return ' ' + context.label + ': ' + context.raw + ' Users';
                                    }
                                }
                            }
                        }
                    }
                });

                // 2. Generate the Custom HTML Legend
                const legendContainer = document.getElementById('custom-legend');
                if (legendContainer) {
                    let legendHTML = '';
                    
                    labels.forEach((label, index) => {
                        const color = palette[index];
                        const count = chartData[index];
                        
                        legendHTML += `
                            <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl hover:bg-primary/5 transition-colors">
                                <div class="flex items-center gap-3 truncate">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: ${color}"></span>
                                    <span class="text-xs md:text-sm font-medium truncate">${label}</span>
                                </div>
                                <span class="text-xs md:text-sum font-medium text-muted-500 truncate">${count}</span>
                            </div>
                        `;
                    });
                    
                    legendContainer.innerHTML = legendHTML;
                }
            }

            // --- ATTENDANCE MULTI-LINE CHART ---
            const attendanceDataSets = @json($attendanceChartData);
            const ctxChart = document.getElementById('attendanceBarChart');
            const timeframeSelect = document.getElementById('attendanceTimeframe'); // Get dropdown early
            let attendanceChart;

            if (ctxChart && attendanceDataSets) {
                // 1. Check local storage for previous selection, default to 'weekly'
                const savedTimeframe = localStorage.getItem('attendanceTimeframePref') || 'weekly';

                // 2. Update the dropdown UI to match the saved preference
                if (timeframeSelect) {
                    timeframeSelect.value = savedTimeframe;
                }

                // 3. Initialize chart with the correct data (saved or default)
                let currentData = attendanceDataSets[savedTimeframe] || attendanceDataSets.weekly;

                attendanceChart = new Chart(ctxChart, {
                    type: 'line',
                    data: {
                        labels: currentData.labels,
                        datasets: [
                            {
                                label: 'Leave',
                                data: currentData.leave, 
                                borderColor: '#17C8C6',
                                backgroundColor: '#17C8C6',
                                borderWidth: 3,
                                tension: 0.4, 
                                pointRadius: 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Absent',
                                data: currentData.absent, 
                                borderColor: '#4896FE',
                                backgroundColor: '#4896FE',
                                borderWidth: 3,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Present',
                                data: currentData.present, 
                                borderColor: '#5347CC',
                                backgroundColor: '#5347CC',
                                borderWidth: 3,
                                tension: 0.4,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { 
                                display: false 
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                padding: 12,
                                backgroundColor: '#1F2937',
                                titleFont: { size: 14 },
                                bodyFont: { size: 13 },
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { font: { size: 13 }, color: '#6B7280' }
                            },
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: {
                                    color: '#F3F4F6', 
                                    drawTicks: false,
                                },
                                ticks: {
                                    stepSize: 1, 
                                    font: { size: 13 },
                                    color: '#6B7280',
                                    padding: 16,
                                    callback: function(value) {
                                        if (Math.floor(value) === value) {
                                            return value; 
                                        }
                                    }
                                }
                            }
                        }
                    } // <--- THIS WAS THE MISSING BRACE!
                });

                // 4. Listen for dropdown changes to update chart AND save preference
                if (timeframeSelect) {
                    timeframeSelect.addEventListener('change', function(e) {
                        const timeframe = e.target.value; // 'weekly' or 'yearly'
                        
                        // Save preference to local storage
                        localStorage.setItem('attendanceTimeframePref', timeframe);
                        
                        const selectedData = attendanceDataSets[timeframe];

                        // Update Chart labels and dataset data
                        attendanceChart.data.labels = selectedData.labels;
                        attendanceChart.data.datasets[0].data = selectedData.leave;
                        attendanceChart.data.datasets[1].data = selectedData.absent;
                        attendanceChart.data.datasets[2].data = selectedData.present;
                        
                        // Re-render chart smoothly
                        attendanceChart.update();
                    });
                }
            }
        });
    </script>
@endpush
