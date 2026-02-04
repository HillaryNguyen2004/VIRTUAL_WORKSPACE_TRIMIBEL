@extends('layout_dashboard')
@section('title', __('staff_dashboard.title'))

@section('content')

@php
    // ✅ Mode: 'staff' or 'substaff' (Controller should pass: $dashboardMode = 'substaff' for substaff route)
    $dashboardMode = $dashboardMode ?? 'staff';

    $isStaff = auth()->user()->hasRole('staff');
    $isSubstaffDashboard = ($dashboardMode === 'substaff');

    // ✅ Access rule:
    // - Staff dashboard: staff role only
    // - Substaff dashboard: permission only
    $canView = $isSubstaffDashboard
        ? auth()->user()->can('staff.dashboard.view')
        : $isStaff;

    // ✅ SAFETY: avoid "Undefined variable $teamMembers"
    $teamMembers = $teamMembers ?? collect();

    // ✅ Route helper (only used if you need a "back to dashboard" link somewhere)
    $dashboardHomeRoute = $isSubstaffDashboard ? route('substaff.dashboard') : route('staff.dashboard');
@endphp

@if($canView)
    {{-- Main Container --}}
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between w-full">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('staff_dashboard.dashboard') }}</h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-secondary/10 text-secondary text-xs font-semibold uppercase tracking-wide">
                    {{ $isSubstaffDashboard ? 'Substaff' : __('staff_dashboard.staff') }}
                </span>
            </div>
        </div>

        {{-- ✅ TEAM MEMBERS SECTION (kept as is, just moved BELOW header and anchored) --}}
        @can('staff.substaff.create')
            <div id="team-members" class="bg-white rounded-2xl p-6 border border-muted-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold">Team Members</h3>
                </div>

                @if($teamMembers->isEmpty())
                    <div class="text-sm text-muted-500">
                        No team members found for your account.
                    </div>
                @else
                    @foreach($teamMembers as $member)
                        <div class="flex items-center justify-between py-2 border-b last:border-0">
                            <div>
                                <div class="font-medium">{{ $member->name }}</div>
                                <div class="text-xs text-muted-500">{{ $member->email }}</div>
                            </div>

                            <form method="POST" action="{{ route('staff.substaff.make', $member) }}">
                                @csrf
                                <button class="px-3 py-2 rounded-lg bg-primary text-white hover:bg-primary-hover transition-colors" type="submit">
                                    Make Substaff
                                </button>
                            </form>
                        </div>
                    @endforeach
                @endif
            </div>
        @endcan

        {{-- Top Cards Section --}}
        <div class="grid grid-cols-1 @2xl:grid-cols-2 @4xl:grid-cols-3 gap-6 w-full animate-fade-in-up">

            {{-- Card 1: Upcoming Tasks --}}
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-primary/10 text-primary group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">{{ __('staff_dashboard.upcoming_tasks') }}</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">{{ __('staff_dashboard.upcoming_tasks_description') }}</p>
                </div>
                <a href="{{ route('projects.index') }}" class="flex justify-center items-center gap-2 w-full bg-primary hover:bg-primary-hover text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-primary/20">
                    {{ __('staff_dashboard.upcoming_tasks_btn') }}
                </a>
            </div>

            {{-- Card 2: Request Day Off --}}
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-accent/50 hover:shadow-accent/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-accent/10 text-accent group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-current" viewBox="0 0 640 640">
                                <path d="M216 64C229.3 64 240 74.7 240 88L240 128L400 128L400 88C400 74.7 410.7 64 424 64C437.3 64 448 74.7 448 88L448 128L480 128C515.3 128 544 156.7 544 192L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 192C96 156.7 124.7 128 160 128L192 128L192 88C192 74.7 202.7 64 216 64zM480 496C488.8 496 496 488.8 496 480L496 416L408 416L408 496L480 496zM496 368L496 288L408 288L408 368L496 368zM360 368L360 288L280 288L280 368L360 368zM232 368L232 288L144 288L144 368L232 368zM144 416L144 480C144 488.8 151.2 496 160 496L232 496L232 416L144 416zM280 416L280 496L360 496L360 416L280 416zM216 176L160 176C151.2 176 144 183.2 144 192L144 240L496 240L496 192C496 183.2 488.8 176 480 176L216 176z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">{{ __('staff_dashboard.view_requests') }}</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">{{ __('staff_dashboard.view_requests_description') }}</p>
                </div>
                <a href="{{ route('dayoff.staff.pending') }}" class="flex justify-center items-center gap-2 w-full bg-accent hover:bg-accent-hover text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-accent/20">
                    {{ __('staff_dashboard.view_requests_btn') }}
                </a>
            </div>

            {{-- Card 3: Team Overview --}}
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-secondary/30 hover:shadow-secondary/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-secondary/10 text-secondary group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-current" viewBox="0 0 640 640">
                                <path d="M320 64C355.3 64 384 92.7 384 128C384 163.3 355.3 192 320 192C284.7 192 256 163.3 256 128C256 92.7 284.7 64 320 64zM416 376C416 401 403.3 423 384 435.9L384 528C384 554.5 362.5 576 336 576L304 576C277.5 576 256 554.5 256 528L256 435.9C236.7 423 224 401 224 376L224 336C224 283 267 240 320 240C373 240 416 283 416 336L416 376zM160 96C190.9 96 216 121.1 216 152C216 182.9 190.9 208 160 208C129.1 208 104 182.9 104 152C104 121.1 129.1 96 160 96zM176 336L176 368C176 400.5 188.1 430.1 208 452.7L208 528C208 529.2 208 530.5 208.1 531.7C199.6 539.3 188.4 544 176 544L144 544C117.5 544 96 522.5 96 496L96 439.4C76.9 428.4 64 407.7 64 384L64 352C64 299 107 256 160 256C172.7 256 184.8 258.5 195.9 262.9C183.3 284.3 176 309.3 176 336zM432 528L432 452.7C451.9 430.2 464 400.5 464 368L464 336C464 309.3 456.7 284.4 444.1 262.9C455.2 258.4 467.3 256 480 256C533 256 576 299 576 352L576 384C576 407.7 563.1 428.4 544 439.4L544 496C544 522.5 522.5 544 496 544L464 544C451.7 544 440.4 539.4 431.9 531.7C431.9 530.5 432 529.2 432 528zM480 96C510.9 96 536 121.1 536 152C536 182.9 510.9 208 480 208C449.1 208 424 182.9 424 152C424 121.1 449.1 96 480 96z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">{{ __('staff_dashboard.team_overview') }}</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">{{ __('staff_dashboard.team_overview_description') }}</p>
                </div>
                <a href="{{ route('team.overview') }}" class="flex justify-center items-center gap-2 w-full bg-secondary hover:bg-secondary-hover text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-secondary/20">
                    {{ __('staff_dashboard.team_overview_btn') }}
                </a>
            </div>

            {{-- NEW PERMISSION-BASED CARDS ADDED BELOW (Only adding new cards, not modifying existing ones) --}}

            {{-- Card 4: Admin Dashboard View --}}
            @can('admin.dashboard.view')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-indigo-500/30 hover:shadow-indigo-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-600 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">Admin Dashboard</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">View administrative dashboard with system overview</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="flex justify-center items-center gap-2 w-full bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-indigo-500/20">
                    View Admin Dashboard
                </a>
            </div>
            @endcan

            {{-- Card 5: Admin Users View --}}
            @can('admin.users.view')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-blue-500/30 hover:shadow-blue-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-blue-500/10 text-blue-600 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">Users Management</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">View and manage system users and their profiles</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="flex justify-center items-center gap-2 w-full bg-blue-500 hover:bg-blue-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-blue-500/20">
                    Manage Users
                </a>
            </div>
            @endcan

            {{-- Card 6: Admin Roles View --}}
            @can('admin.roles.view')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-amber-500/30 hover:shadow-amber-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-amber-500/10 text-amber-600 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">Roles & Permissions</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">View system roles and their assigned permissions</p>
                </div>
                <a href="{{ route('admin.roles.index') }}" class="flex justify-center items-center gap-2 w-full bg-amber-500 hover:bg-amber-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-amber-500/20">
                    Manage Roles
                </a>
            </div>
            @endcan

            {{-- Card 7: Admin Projects View --}}
            @can('admin.projects.view')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-emerald-500/30 hover:shadow-emerald-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-600 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">Projects Management</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">View and manage all projects in the system</p>
                </div>
                <a href="{{ route('admin.projects.index') }}" class="flex justify-center items-center gap-2 w-full bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-emerald-500/20">
                    Manage Projects
                </a>
            </div>
            @endcan

            {{-- Card 8: Admin Attendance View --}}
            @can('admin.attendance.view')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-cyan-500/30 hover:shadow-cyan-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-cyan-500/10 text-cyan-600 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">Attendance Tracking</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">View attendance records and time tracking data</p>
                </div>
                <a href="{{ route('admin.attendance.index') }}" class="flex justify-center items-center gap-2 w-full bg-cyan-500 hover:bg-cyan-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-cyan-500/20">
                    View Attendance
                </a>
            </div>
            @endcan

            {{-- Card 9: Admin Campaigns View --}}
            @can('admin.campaigns.view')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-pink-500/30 hover:shadow-pink-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-pink-500/10 text-pink-600 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">Campaigns Management</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">View and manage marketing campaigns</p>
                </div>
                {{-- CHANGE THIS LINE: campaign.index to campaigns.index --}}
                <a href="{{ route('campaigns.index') }}" class="flex justify-center items-center gap-2 w-full bg-pink-500 hover:bg-pink-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-pink-500/20">
                    Manage Campaigns
                </a>
            </div>
            @endcan

            {{-- Card 10: Admin Email Templates View --}}
            @can('admin.email_templates.view')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-rose-500/30 hover:shadow-rose-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-rose-500/10 text-rose-600 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">Email Templates</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">View and manage email templates and communications</p>
                </div>
                {{-- CHANGE THIS LINE: admin.email_templates.index to email-templates.index --}}
                <a href="{{ route('email-templates.index') }}" class="flex justify-center items-center gap-2 w-full bg-rose-500 hover:bg-rose-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-rose-500/20">
                    Manage Templates
                </a>
            </div>
            @endcan

{{-- Card 11: Admin Activity Logs View --}}
@can('admin.activity_logs.view')
<div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-slate-500/30 hover:shadow-slate-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
    <div>
        <div class="flex items-center gap-3 mb-3">
            <div class="p-3 rounded-xl bg-slate-500/10 text-slate-600 group-hover:scale-110 transition-transform duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-main">Activity Logs</h3>
    </div>
    <p class="text-muted-500 text-sm mb-6">View system activity logs and audit trails</p>
    </div>
    {{-- CHANGE THIS LINE: admin.activity_logs.index to admin.activity.logs --}}
    <a href="{{ route('admin.activity.logs') }}" class="flex justify-center items-center gap-2 w-full bg-slate-500 hover:bg-slate-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-slate-500/20">
        View Logs
    </a>
</div>
@endcan

            {{-- Card 11: Admin Activity Logs View --}}
            @can('admin.activity_logs.view')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-slate-500/30 hover:shadow-slate-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-slate-500/10 text-slate-600 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">Activity Logs</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">View system activity logs and audit trails</p>
                </div>
                <a href="{{ route('admin.activity_logs.index') }}" class="flex justify-center items-center gap-2 w-full bg-slate-500 hover:bg-slate-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-slate-500/20">
                    View Logs
                </a>
            </div>
            @endcan

            {{-- Card 12: Admin Company Hours View --}}
            @can('admin.company_hours.view')
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-orange-500/30 hover:shadow-orange-500/10 transition-all duration-300 flex flex-col justify-between h-full group">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-3 rounded-xl bg-orange-500/10 text-orange-600 group-hover:scale-110 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-main">Company Hours</h3>
                    </div>
                    <p class="text-muted-500 text-sm mb-6">View and manage company working hours and schedules</p>
                </div>
                <a href="{{ route('admin.company_hours.index') }}" class="flex justify-center items-center gap-2 w-full bg-orange-500 hover:bg-orange-600 text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-orange-500/20">
                    Manage Hours
                </a>
            </div>
            @endcan

        </div>

        {{-- Project Section --}}
        <div class="bg-white border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 rounded-2xl p-6 animate-fade-in-up [animation-delay:150ms]">
            <div class="flex items-center justify-between mb-6">
                <h4 class="text-lg font-bold text-main">{{ __('staff_dashboard.my_projects') }}</h4>
                <a href="{{ route('projects.index') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>

            @php
                $statusMap = [
                    'pending' => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                    'in_progress' => 'bg-secondary/10 text-secondary ring-secondary/20',
                    'completed' => 'bg-accent/10 text-accent ring-accent/20',
                ];
            @endphp

            <div class="w-full">
                {{-- Header Row --}}
                <div class="grid grid-cols-12 gap-4 pb-3 border-b border-muted-200 text-xs font-semibold text-muted-400 uppercase tracking-wider">
                    <div class="col-span-6">{{ __('staff_dashboard.project_name') }}</div>
                    <div class="col-span-2 text-center hidden @2xl:block">{{ __('staff_dashboard.percentage') }}</div>
                    <div class="col-span-3 @2xl:col-span-2 text-right">{{ __('staff_dashboard.status') }}</div>
                    <div class="col-span-3 @2xl:col-span-2 text-right">{{ __('staff_dashboard.tasks_count') }}</div>
                </div>

                {{-- List Rows --}}
                @forelse ($projects->take(3) as $project)
                    <div class="grid grid-cols-12 gap-4 py-4 items-center border-b border-muted-100 last:border-0 hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                        <div class="col-span-6">
                            <p class="text-sm font-medium text-main truncate" title="{{ $project->name }}">{{ $project->title }}</p>
                        </div>

                        <div class="col-span-2 text-center hidden @2xl:block">
                            <span class="text-sm text-muted-500 font-medium">{{ $project->percentage ?? 0 }}%</span>
                        </div>

                        <div class="col-span-3 @2xl:col-span-2 flex justify-end">
                            <span class="inline-flex items-center text-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusMap[$project->status] ?? $statusMap['pending'] }}">
                                {{ __('staff_dashboard.' . $project->status) }}
                            </span>
                        </div>

                        <div class="col-span-3 @2xl:col-span-2 flex justify-end">
                            <span class="text-sm text-muted-500 font-medium">{{ $project->tasks->count() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-muted-400 text-sm">
                        {{ __('staff_dashboard.no_upcoming_projects') ?? 'No data available' }}
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Activity Section --}}
        <div class="bg-white border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 rounded-2xl p-6 animate-fade-in-up [animation-delay:200ms]">
            <div class="flex items-center justify-between mb-6">
                <h4 class="text-lg font-bold text-main">{{ __('staff_dashboard.recent_activity') }}</h4>
            </div>

            @php
                $activityStatusMap = [
                    'add' => 'bg-secondary/10 text-secondary ring-1 ring-inset ring-secondary/20',
                    'remove' => 'bg-danger/10 text-danger ring-1 ring-inset ring-danger/20',
                    'completed' => 'bg-accent/10 text-accent ring-1 ring-inset ring-accent/20',
                ];
            @endphp

            <div class="w-full">
                <div class="grid grid-cols-12 gap-4 pb-3 border-b border-muted-200 text-xs font-semibold text-muted-400 uppercase tracking-wider">
                    <div class="col-span-6">{{ __('staff_dashboard.activity_title') }}</div>
                    <div class="col-span-3 text-center hidden @2xl:block">{{ __('staff_dashboard.activity_time') }}</div>
                    <div class="col-span-6 @2xl:col-span-3 text-right">{{ __('staff_dashboard.activity_status_label') }}</div>
                </div>

                {{-- Example rows --}}
                <div class="grid grid-cols-12 gap-4 py-4 items-center border-b border-muted-100 hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                    <div class="col-span-6">
                        <p class="text-sm font-medium text-main truncate">Viết báo cáo</p>
                    </div>
                    <div class="col-span-3 text-center hidden @2xl:block">
                        <span class="text-sm text-muted-500">2025-09-26</span>
                    </div>
                    <div class="col-span-6 @2xl:col-span-3 flex justify-end">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $activityStatusMap['add'] }}">
                            Add
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4 py-4 items-center border-b border-muted-100 hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                    <div class="col-span-6">
                        <p class="text-sm font-medium text-main truncate">Viết báo cáo</p>
                    </div>
                    <div class="col-span-3 text-center hidden @2xl:block">
                        <span class="text-sm text-muted-500">2025-09-26</span>
                    </div>
                    <div class="col-span-6 @2xl:col-span-3 flex justify-end">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $activityStatusMap['remove'] }}">
                            Remove
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4 py-4 items-center hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                    <div class="col-span-6">
                        <p class="text-sm font-medium text-main truncate">Viết báo cáo</p>
                    </div>
                    <div class="col-span-3 text-center hidden @2xl:block">
                        <span class="text-sm text-muted-500">2025-09-26</span>
                    </div>
                    <div class="col-span-6 @2xl:col-span-3 flex justify-end">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $activityStatusMap['completed'] }}">
                            Done
                        </span>
                    </div>
                </div>

            </div>
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
            <h4 class="text-xl font-bold text-main">{{ __('staff_dashboard.no_permission') }}</h4>
        </div>
    </div>
@endif

@endsection