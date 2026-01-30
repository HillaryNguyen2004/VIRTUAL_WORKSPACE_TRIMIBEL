@extends('layout_dashboard')
@section('title', __('admin_dashboard.title'))

@section('content')
    @role('admin')
    
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
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        
        {{-- Header Section --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center w-full">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('admin_dashboard.admin_dashboard') }}</h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold uppercase tracking-wide">
                    {{ __('admin_dashboard.admin') }}
                </span>
            </div>
        </div>

        {{-- GRID ROW 1 --}}
        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up">
            
            {{-- 1. User Management --}}
            <div class="@4xl:col-span-5 flex flex-col justify-between h-full min-h-[200px] border bg-white border-muted-200 shadow-lg shadow-main/5 hover:border-primary/50 hover:shadow-primary/10 rounded-2xl p-6 relative overflow-hidden group transition-all duration-300">
                {{-- Decorative background element --}}
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-primary/10 rounded-full blur-2xl opacity-50"></div>

                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.total_users') }}</h3>
                        <p class="text-muted-500 text-sm">Last 30 Days</p>
                    </div>
                    
                    {{-- Action Area --}}
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

            {{-- 2. Permission Management --}}
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
                    {{-- Admin --}}
                    <div class="flex items-center justify-between p-3 rounded-xl bg-primary/5 border border-primary/10">
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full bg-primary shadow-sm shadow-primary/40"></div>
                            <span class="text-sm font-medium text-main">Admins</span>
                        </div>
                        <span class="text-lg font-bold text-main">{{ $roleCounts['admin'] }}</span>
                    </div>
                    {{-- Manager --}}
                    <div class="flex items-center justify-between p-3 rounded-xl bg-secondary/5 border border-secondary/10">
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full bg-secondary shadow-sm shadow-secondary/40"></div>
                            <span class="text-sm font-medium text-main">Staff</span>
                        </div>
                        <span class="text-lg font-bold text-main">{{ $roleCounts['staff'] }}</span>
                    </div>
                    {{-- Staff --}}
                    <div class="flex items-center justify-between p-3 rounded-xl bg-accent/5 border border-accent/10">
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full bg-accent shadow-sm shadow-accent/40"></div>
                            <span class="text-sm font-medium text-main">Users</span>
                        </div>
                        <span class="text-lg font-bold text-main">{{ $roleCounts['user'] }}</span>
                    </div>
                </div>
            </div>

            {{-- 3. Working Hours --}}
            <div class="@4xl:col-span-4 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-accent/30 hover:shadow-accent/10 transition-all duration-300 flex flex-col h-full">
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
                    <div class="flex items-center gap-6 mt-4 pt-4 border-t border-muted-100">
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
                                <div class="w-2.5 h-0.5 bg-secondary border-none"></div> {{-- Flat line icon --}}
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[10px] uppercase text-muted-400 font-bold tracking-wider">{{ __('admin_dashboard.midday') }}</span>
                                    <span class="text-xs text-main font-medium leading-none">
                                        {{ $midDayStr }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
        </div>

        {{-- GRID ROW 2 --}}
        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up [animation-delay:100ms]">
            
            {{-- 4. Project Health --}}
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
            </div>

            {{-- 5. Recent Attendance --}}
            <div class="@4xl:col-span-4 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 flex flex-col h-full">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.recent_attendance') }}</h3>
                    <a href="{{ route('users.checkin_index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
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
                                    {{-- Avatar matching User Dashboard style --}}
                                    <div class="h-10 w-10 rounded-full bg-muted-100 text-muted-600 border border-muted-200 grid place-items-center font-bold text-sm shadow-lg shadow-main/5 ">
                                        {{ mb_substr($log->user_name, 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-main truncate">{{ $log->user_name }}</p>
                                        <div class="flex items-center gap-2 text-xs text-muted-400">
                                            <span class="flex items-center gap-1 text-accent font-medium">
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
            </div>
        </div>

        {{-- GRID ROW 3 --}}
        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up [animation-delay:200ms]">
            
            {{-- 6. Campaign Timeline --}}
            <div class="@4xl:col-span-4 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 flex flex-col gap-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.campaign_management') }}</h3>

                    <a href="{{ route('campaigns.index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
                
                <div class="flex flex-col gap-6">
                    <div>
                        <h4 class="text-xs font-bold text-muted-400 uppercase tracking-wider mb-4">{{ __('admin_dashboard.campaign_scheduled') }}</h4>
                        <ul class="space-y-4">
                            @forelse($upcomingCampaigns ?? [] as $camp)
                                <li class="pl-4 border-l-2 border-primary/30 relative">
                                    <div class="absolute -left-[5px] w-2 h-2 rounded-full bg-primary"></div>
                                    <p class="text-xs font-bold text-main uppercase tracking-wide">{{ $camp->scheduled_at->format('M d, H:i') }}</p>
                                    <p class="text-sm text-muted-600 mt-0.5">{{ $camp->name }}</p>
                                    <span class="inline-block mt-2 px-2 py-0.5 bg-primary/10 text-primary text-[10px] rounded-full font-bold">Scheduled</span>
                                </li>
                            @empty
                                <li class="text-xs text-muted-400">{{ __('admin_dashboard.campaign_no_scheduled') }}</li>
                            @endforelse
                        </ul>
                    </div>
                    <hr class="border-muted-200">
                    <div>
                        <h4 class="text-xs font-bold text-muted-400 uppercase tracking-wider mb-4">{{ __('admin_dashboard.campaign_sent') }}</h4>
                        <ul class="space-y-4">
                            @forelse($sentCampaigns ?? [] as $camp)
                                <li class="flex justify-between items-center group">
                                    <div>
                                        <p class="text-sm font-bold text-main group-hover:text-primary transition-colors">{{ $camp->name }}</p>
                                        <span class="inline-block mt-1 px-2 py-0.5 bg-accent/10 text-accent text-[10px] rounded-full font-bold">Sent</span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-main">{{ number_format($camp->sent_count) }}</p>
                                        <p class="text-[10px] text-muted-400">{{ __('admin_dashboard.campaign_users_reached') }}</p>
                                    </div>
                                </li>
                            @empty
                                <li class="text-xs text-muted-400">{{ __('admin_dashboard.campaign_no_sent') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{-- 7. Email Templates (Updated) --}}
            <div class="@4xl:col-span-4 flex flex-col h-full">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.email_templates') }}</h3>
                    <a href="{{ route('email-templates.index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
                
                {{-- Template List --}}
                <div class="flex flex-col gap-4 flex-1">
                    @foreach($emailTemplates->take(4) as $template)
                        <div class="group flex items-center gap-4 p-4 rounded-2xl bg-white border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 cursor-pointer">
                            {{-- Circular ID Ring --}}
                            <div class="relative w-12 h-12 flex-none">
                                <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                                    <path class="text-primary/10" 
                                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                                          fill="none" 
                                          stroke="currentColor" 
                                          stroke-width="3" />
                                    <path class="text-primary/40 group-hover:text-primary transition-colors duration-300" 
                                          stroke-dasharray="{{ rand(40, 85) }}, 100" 
                                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                                          fill="none" 
                                          stroke="currentColor" 
                                          stroke-width="3" 
                                          stroke-linecap="round" />
                                </svg>
                                {{-- ID Number --}}
                                <div class="absolute inset-0 flex items-center justify-center text-sm font-bold text-main">
                                    {{ $template->id }}
                                </div>
                            </div>
                            
                            {{-- Text Content --}}
                            <div class="flex-1 min-w-0">
                                <h4 class="text-base font-bold text-main group-hover:text-primary transition-colors">{{ $template->name }}</h4>
                                <p class="text-xs text-muted-500 mt-1 truncate">{{ $template->subject }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 8. Activity Log --}}
            <div class="@4xl:col-span-4 bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.recent_activities') }}</h3>
                    <a href="{{ route('admin.activity.logs') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>

                <div class="relative">
                     @if($recentLogs->isNotEmpty())
                        {{-- Timeline Line --}}
                        <div class="absolute left-1.5 top-2 bottom-4 w-px bg-muted-200"></div>
                        
                        <ul class="flex flex-col gap-6 relative">
                            @foreach($recentLogs->take(3) as $log)
                                <li class="flex gap-4 group">
                                    {{-- Dot --}}
                                    <div class="relative z-10 w-4 h-4 rounded-full bg-white border-2 border-primary mt-0.5 flex-shrink-0 shadow-sm group-hover:scale-110 ring-8 ring-white transition-transform"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-main">
                                            <span class="font-bold text-primary">{{ $log->user->name ?? 'User' }}</span> 
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
            </div>
        </div>

        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up [animation-delay:300ms]">
            
            <div class="@4xl:col-span-12 flex flex-col h-full">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-main">Upcoming Holidays</h3>
                    <div class="flex items-center gap-2">
                        {{-- Add Button --}}
                        <button id="openHolidayModal" class="flex items-center gap-1 bg-primary/10 hover:bg-primary/20 text-primary px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add New
                        </button>
                        
                        {{-- <a href="{{ route('holidays.index') }}" title="View All" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a> --}}
                    </div>
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
    </div>
    @include ('holidays_modal')
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
    @endrole
@endsection

@push('scripts')
    @vite(['resources/js/admin/edit_holiday.js'])
@endpush