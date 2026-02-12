@extends('layout_dashboard')
@section('title', __('user_dashboard.title'))

@section('content')
    @role('user')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        
        <div class="flex flex-row gap-4 justify-between @2xl:items-center w-full">
            <div>
                <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">{{ __('user_dashboard.heading') }}</h1>
                <p class="text-muted-500 text-sm md:text-base mt-1">{{ __('user_dashboard.subheading') }}</p>
            </div>
            
            <button
                id="open-request-dayoff"
                class="group flex items-center justify-center gap-2 rounded-xl bg-primary-gradient px-6 py-3 text-white text-md md:text-base font-semibold shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ __('user_dashboard.request_day_off') }}
            </button>
        </div>

        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full">
            
            <div class="@4xl:col-span-5 flex flex-col justify-between h-full min-h-[320px] bg-primary-gradient border-muted-200 shadow-xl shadow-primary/20 rounded-2xl p-6 relative overflow-hidden group">
                
                {{-- Decorative Background: Subtle Tech Pattern --}}
                <!-- <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16 transition-all duration-700 group-hover:bg-primary/10"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-accent/5 rounded-full blur-2xl -ml-12 -mb-12"></div> -->

                {{-- Header: Title + Live Status --}}
                <div class="relative z-10 flex justify-between items-start">
                    <div class="flex justify-between w-full">
                        <h3 class="text-md md:text-lg font-semibold text-canvas override tracking-tight">{{ __('user_dashboard.check_attendence') }}</h3>
                        <div class="p-3 rounded-xl bg-canvas/10 text-canvas override group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 14a4 4 0 108 0" /> 
                                <path d="M9 10h.01" />          
                                <path d="M15 10h.01" />         
                                <path d="M4 7V5a2 2 0 012-2h2" />   
                                <path d="M16 3h2a2 2 0 012 2v2" />  
                                <path d="M20 17v2a2 2 0 01-2 2h-2" /> 
                                <path d="M8 21H6a2 2 0 01-2-2v-2" />  
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    {{-- Center: The "Hero" Time Display --}}
                    <div class="relative z-10 flex flex-col justify-center">
                        @if ($workingHour)
                            <div class="space-y-2">
                                <div class="inline-flex items-center gap-2 text-2xl md:text-3xl font-semibold text-canvas override">
                                    <span>{{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->start_at)->format('H:i') }}</span>
                                    <span class="font-light">-</span>
                                    <span>{{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->end_at)->format('H:i') }}</span>
                                </div>
                                <p class="font-medium text-canvas/50 uppercase tracking-widest text-xs">{{ __('user_dashboard.working_hour') }}</p>
                            </div>
                        @else
                            <div class="text-center">
                                <p class="text-muted-400 text-sm md:text-base italic">{{ __('user_dashboard.working_hour_unavailable') }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Bottom: Action Grid --}}
                    <div class="relative z-10 mt-auto">
                        <div class="grid grid-cols-2 gap-3">
                            {{-- Check In Button --}}
                            <a href="{{ route('checkin.face.page', 'checkin') }}"
                            class="relative flex flex-col items-center justify-center gap-2 p-4 rounded-xl bg-canvas/10 text-canvas override transition-all duration-300 hover:bg-canvas/20 group/btn">
                                <div class="p-2 rounded-full bg-canvas/20 group-hover/btn:bg-canvas/40 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                </div>
                                <span class="font-semibold text-xs md:text-sm">{{ __('user_dashboard.check_in') }}</span>
                            </a>

                            {{-- Check Out Button --}}
                            <a href="{{ route('checkin.face.page', 'checkout') }}"
                            class="relative flex flex-col items-center justify-center gap-2 p-4 rounded-xl bg-canvas/10 text-canvas override transition-all duration-300 hover:bg-canvas/20 group/btn">
                                <div class="p-2 rounded-full bg-canvas/20 group-hover/btn:bg-canvas/40 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </div>
                                <span class="font-semibold text-xs md:text-sm">{{ __('user_dashboard.check_out') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="@4xl:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4 animate-fade-in-up [animation-delay:100ms]">
                @php
                    // We map these to your new palette
                    $statCards = [
                        [
                            'label' => __('user_dashboard.earnings_monthly'),
                            'value' => '$40,000',
                            'icon' => '<path d="M16 2v4M8 2v4M3 10h18" />',
                            'color' => 'text-primary', // Cyan
                            'bg' => 'bg-primary/10',
                            'hover' => 'hover:border-primary/50',
                        ],
                        [
                            'label' => __('user_dashboard.earnings_annual'),
                            'value' => '$215,000',
                            'icon' => '<path d="M12 1v22M17 5H9a3 3 0 000 6h6a3 3 0 010 6H7" />',
                            'color' => 'text-primary', // Cyan
                            'bg' => 'bg-primary/10',
                            'hover' => 'hover:border-primary/50',
                        ],
                        [
                            'label' => __('user_dashboard.tasks'),
                            'value' => '50%',
                            'icon' => '<path d="M8 7h8M4 3h16v18H4z" /><path d="m9 14 2 2 4-5" />',
                            'color' => 'text-secondary', // Blue
                            'bg' => 'bg-secondary/10',
                            'hover' => 'hover:border-secondary/30',
                        ],
                        [
                            'label' => __('user_dashboard.pending_requests'),
                            'value' => '18',
                            'icon' => '<rect x="3" y="4" width="18" height="16" rx="2" /><path d="M7 8h10" />',
                            'color' => 'text-secondary', // Blue
                            'bg' => 'bg-secondary/10',
                            'hover' => 'hover:border-secondary/30',
                        ],
                    ];
                @endphp

                @foreach($statCards as $card)
                <div class="bg-white rounded-2xl p-6 border border-muted-300  {{ $card['hover'] }} transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-xs md:text-sm">{{ $card['label'] }}</p>
                            <p class="text-2xl md:text-3xl font-semibold text-main tracking-tight">{{ $card['value'] }}</p>
                        </div>
                        <div class="p-3 rounded-xl {{ $card['bg'] }} {{ $card['color'] }} group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                {!! $card['icon'] !!}
                            </svg>
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- NEW PERMISSION-BASED CARDS FOR USERS --}}

                {{-- Card 1: Create Task --}}
                <!-- @can('create task')
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-success/30 hover:shadow-success/10 transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-sm">Create Task</p>
                            <p class="text-2xl font-semibold text-main tracking-tight">New Task</p>
                        </div>
                        <div class="p-3 rounded-xl bg-success/10 text-success group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('tasks.create') }}" class="mt-4 flex justify-center items-center gap-2 w-full bg-success hover:bg-success-hover text-white rounded-lg py-2 font-medium transition-colors text-sm">
                        Create New Task
                    </a>
                </div>
                @endcan

                {{-- Card 2: Edit Task --}}
                @can('edit task')
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-warning/30 hover:shadow-warning/10 transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-sm">Edit Tasks</p>
                            <p class="text-2xl font-semibold text-main tracking-tight">Modify Tasks</p>
                        </div>
                        <div class="p-3 rounded-xl bg-warning/10 text-warning group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="mt-4 flex justify-center items-center gap-2 w-full bg-warning hover:bg-warning-hover text-white rounded-lg py-2 font-medium transition-colors text-sm">
                        Edit Tasks
                    </a>
                </div>
                @endcan

                {{-- Card 3: Delete Task --}}
                @can('delete task')
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-danger/30 hover:shadow-danger/10 transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-sm">Delete Tasks</p>
                            <p class="text-2xl font-semibold text-main tracking-tight">Remove Tasks</p>
                        </div>
                        <div class="p-3 rounded-xl bg-danger/10 text-danger group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="mt-4 flex justify-center items-center gap-2 w-full bg-danger hover:bg-danger-hover text-white rounded-lg py-2 font-medium transition-colors text-sm">
                        Manage Tasks
                    </a>
                </div>
                @endcan

                {{-- Card 4: View All Tasks --}}
                @can('view all tasks')
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-info/30 hover:shadow-info/10 transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-sm">All Tasks</p>
                            <p class="text-2xl font-semibold text-main tracking-tight">View All</p>
                        </div>
                        <div class="p-3 rounded-xl bg-info/10 text-info group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="mt-4 flex justify-center items-center gap-2 w-full bg-info hover:bg-info-hover text-white rounded-lg py-2 font-medium transition-colors text-sm">
                        View All Tasks
                    </a>
                </div>
                @endcan

                {{-- Card 5: Staff Dashboard View --}}
                @can('staff.dashboard.view')
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-purple-500/30 hover:shadow-purple-500/10 transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-sm">Staff Dashboard</p>
                            <p class="text-2xl font-semibold text-main tracking-tight">Staff View</p>
                        </div>
                        <div class="p-3 rounded-xl bg-purple-500/10 text-purple-600 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('staff.dashboard') }}" class="mt-4 flex justify-center items-center gap-2 w-full bg-purple-500 hover:bg-purple-600 text-white rounded-lg py-2 font-medium transition-colors text-sm">
                        Go to Staff Dashboard
                    </a>
                </div>
                @endcan

                {{-- Card 6: Manage Team --}}
                @can('manage team')
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-indigo-500/30 hover:shadow-indigo-500/10 transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-sm">Team Management</p>
                            <p class="text-2xl font-semibold text-main tracking-tight">Team Tools</p>
                        </div>
                        <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-600 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('team.overview') }}" class="mt-4 flex justify-center items-center gap-2 w-full bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg py-2 font-medium transition-colors text-sm">
                        Manage Team
                    </a>
                </div>
                @endcan -->

            </div>
        </div>

        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up [animation-delay:200ms]">
            @if ($teamLeader)
                <div class="@4xl:col-span-7 grid grid-cols-1 container @2xl:grid-cols-2 @4xl:grid-cols-5 gap-6">
                    <div class="@2xl:col-span-1 @4xl:col-span-2 bg-white border border-muted-300 hover:border-primary/30 transition-colors rounded-2xl p-6 flex flex-col items-center text-center">
                        <div class="relative my-auto">
                            <div class="absolute inset-0 bg-primary/20 rounded-full blur-lg opacity-50"></div>
                            <img src="/img/undraw_profile_2.svg" alt="leader_avatar" class="relative w-24 h-24 rounded-full ring ring-muted-200 ring-offset-8 object-cover">
                        </div>
                        
                        <div class="py-4">
                            <h4 class="text-md md:text-lg font-semibold text-main">{{ $teamLeader->name }}</h4>
                            <span class="inline-block mt-1 px-3 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full uppercase tracking-wide">
                                {{ __('user_dashboard.team_leader') }}
                            </span>
                            <a href="mailto:{{ $teamLeader->email }}" class="mt-3 text-muted-500 text-xs md:text-sm hover:text-primary transition-colors flex items-center justify-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                {{ $teamLeader->email }}
                            </a>
                        </div>
                    </div>

                    <div class="@2xl:col-span-1 @4xl:col-span-3 bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 rounded-2xl p-6 flex-1">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.team_members') }}</h4>
                            <button id="open-team-member" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                                </svg>
                            </button>
                        </div>
                        
                        @if($teamMembers->isNotEmpty())
                            <ul class="flex flex-col gap-4">
                                @foreach($teamMembers->take(3) as $member)
                                    <li class="flex items-center gap-4 group">
                                        <div class="h-10 w-10 rounded-full bg-muted-100 text-muted-600 border border-muted-200 grid place-items-center font-semibold text-sm  transition-colors">
                                            {{ mb_substr($member->name ?? '', 0, 1) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs md:text-sm font-medium text-main truncate" title="{{ $member->name }}">
                                                {{ $member->name }}
                                            </p>
                                            <p class="text-xs text-muted-400 truncate">Member</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-6 text-muted-400 text-xs md:text-sm">
                                {{ __('user_dashboard.no_team_members') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="@4xl:col-span-5 bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 rounded-2xl p-6 h-max">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.assigned_projects') }}</h4>
                        <a href="{{ route('tasks.index') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                            </svg>
                        </a>
                    </div>

                    <div class="w-full">
                        <div class="grid grid-cols-12 gap-4 pb-3 border-b border-muted-200 text-xs font-semibold text-muted-400 uppercase tracking-wider">
                            <div class="col-span-8">{{ __('user_dashboard.tasks') }}</div>
                            <div class="col-span-4 text-right">{{ __('user_dashboard.task_status') }}</div>
                        </div>
                        
                        @if ($assignedTasks->isNotEmpty())
                            <ul class="flex flex-col divide-y divide-muted-100">
                                @foreach ($assignedTasks->take(3) as $task)
                                    @php
                                        // Mapping statuses to your specific palette
                                        $statusConfig = [
                                            // Pending -> Muted/Neutral
                                            'pending' => ['bg' => 'bg-muted-100', 'text' => 'text-muted-600', 'ring' => 'ring-muted-500/10'],
                                            // In Progress -> Secondary (Blue)
                                            'in_progress' => ['bg' => 'bg-secondary/10', 'text' => 'text-secondary', 'ring' => 'ring-secondary/20'],
                                            // Completed -> Accent (Cyan) 
                                            'completed' => ['bg' => 'bg-accent/10', 'text' => 'text-accent', 'ring' => 'ring-accent/20'],
                                        ];
                                        
                                        $currentStatus = $statusConfig[$task->status] ?? $statusConfig['pending'];
                                        // Check if task is inactive
                                        $isInactive = !$task->active; // Assuming 'active' is a boolean field
                                    @endphp
                                    <li class="grid grid-cols-12 gap-4 py-4 items-center hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                                        <div class="col-span-8 flex items-center gap-3">
                                            {{-- Small indicator dot --}}
                                            <div class="w-2 h-2 rounded-full {{ str_replace('bg-', 'bg-', $currentStatus['text']) }} opacity-50"></div>
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                @if($task->isUnread())
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50 flex-shrink-0 animate-pulse" title="New/Updated"></span>
                                                @endif
                                                <a href="{{ route('tasks.details', $task->id) }}" class="text-sm font-medium text-main truncate hover:text-primary hover:underline" title="{{ $task->title }}">{{ $task->title }}</a>
                                                @if($isInactive)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger/10 text-danger ring-1 ring-inset ring-danger/20 whitespace-nowrap">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-span-4 flex justify-end">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $currentStatus['bg'] }} {{ $currentStatus['text'] }} ring-1 ring-inset {{ $currentStatus['ring'] }}">
                                                {{ __('user_dashboard.status_' . $task->status) }}
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-10">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-muted-100 mb-3">
                                    <svg class="w-6 h-6 text-muted-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <p class="text-muted-500 text-xs md:text-sm">{{ __('user_dashboard.no_projects_assigned') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="@4xl:col-span-5 bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 rounded-2xl p-6 flex-1">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.team_members') }}</h4>
                        <button id="open-team-member" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                            </svg>
                        </button>
                    </div>
                    
                    @if($teamMembers->isNotEmpty())
                        <ul class="flex flex-col gap-4">
                            @foreach($teamMembers->take(3) as $member)
                                <li class="flex items-center gap-4 group">
                                    <div class="h-10 w-10 rounded-full bg-muted-100 text-muted-600 border border-muted-200 grid place-items-center font-semibold text-xs md:text-sm transition-colors">
                                        {{ mb_substr($member->name ?? '', 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs md:text-sm font-medium text-main truncate" title="{{ $member->name }}">
                                            {{ $member->name }}
                                        </p>
                                        <p class="text-xs text-muted-400 truncate">Member</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-6 text-muted-400 text-xs md:text-sm">
                            {{ __('user_dashboard.no_team_members') }}
                        </div>
                    @endif
                </div>

                <div class="@4xl:col-span-7 bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 rounded-2xl p-6 h-max">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.assigned_projects') }}</h4>
                        <a href="{{ route('tasks.index') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                            </svg>
                        </a>
                    </div>

                    <div class="w-full">
                        <div class="grid grid-cols-12 gap-4 pb-3 border-b border-muted-200 text-xs font-semibold text-muted-400 uppercase tracking-wider">
                            <div class="col-span-8">{{ __('user_dashboard.tasks') }}</div>
                            <div class="col-span-4 text-right">{{ __('user_dashboard.task_status') }}</div>
                        </div>
                        
                        @if ($assignedTasks->isNotEmpty())
                            <ul class="flex flex-col divide-y divide-muted-100">
                                @foreach ($assignedTasks->take(3) as $task)
                                    @php
                                        // Mapping statuses to your specific palette
                                        $statusConfig = [
                                            // Pending -> Muted/Neutral
                                            'pending' => ['bg' => 'bg-muted-100', 'text' => 'text-muted-600', 'ring' => 'ring-muted-500/10'],
                                            // In Progress -> Secondary (Blue)
                                            'in_progress' => ['bg' => 'bg-secondary/10', 'text' => 'text-secondary', 'ring' => 'ring-secondary/20'],
                                            // Completed -> Accent (Cyan) 
                                            'completed' => ['bg' => 'bg-accent/10', 'text' => 'text-accent', 'ring' => 'ring-accent/20'],
                                        ];
                                        
                                        $currentStatus = $statusConfig[$task->status] ?? $statusConfig['pending'];
                                        // Check if task is inactive
                                        $isInactive = !$task->active; // Assuming 'active' is a boolean field
                                    @endphp
                                    <li class="grid grid-cols-12 gap-4 py-4 items-center hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                                        <div class="col-span-8 flex items-center gap-3">
                                            {{-- Small indicator dot --}}
                                            <div class="w-2 h-2 rounded-full {{ str_replace('bg-', 'bg-', $currentStatus['text']) }} opacity-50"></div>
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                @if($task->isUnread())
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50 flex-shrink-0 animate-pulse" title="New/Updated"></span>
                                                @endif
                                                <a href="{{ route('tasks.details', $task->id) }}" class="text-sm font-medium text-main truncate hover:text-primary hover:underline" title="{{ $task->title }}">{{ $task->title }}</a>
                                                @if($isInactive)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger/10 text-danger ring-1 ring-inset ring-danger/20 whitespace-nowrap">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-span-4 flex justify-end">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $currentStatus['bg'] }} {{ $currentStatus['text'] }} ring-1 ring-inset {{ $currentStatus['ring'] }}">
                                                {{ __('user_dashboard.status_' . $task->status) }}
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-10">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-muted-100 mb-3">
                                    <svg class="w-6 h-6 text-muted-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <p class="text-muted-500 text-xs md:text-sm">{{ __('user_dashboard.no_projects_assigned') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- SECTION: Admin Permissions Container --}}
        @if(auth()->user()->can('admin.dashboard.view') || 
            auth()->user()->can('admin.users.view') || 
            auth()->user()->can('admin.users.create') ||
            auth()->user()->can('admin.users.edit') ||
            auth()->user()->can('admin.users.delete') ||
            auth()->user()->can('admin.roles.view') || 
            auth()->user()->can('admin.roles.edit') ||
            auth()->user()->can('admin.campaigns.view') ||
            auth()->user()->can('admin.campaigns.edit') ||
            auth()->user()->can('admin.email_templates.view') ||
            auth()->user()->can('admin.activity_logs.view') ||
            auth()->user()->can('admin.attendance.view'))
        <div class="">
            <!-- <h3 class="text-2xl font-semibold text-main mb-6">{{ __('user_dashboard.admin_section') ?? 'Admin Management' }}</h3> -->
            <div class="grid grid-cols-1 @2xl:grid-cols-2 @4xl:grid-cols-3 gap-6 w-full animate-fade-in-up [animation-delay:150ms]">
                
                {{-- Admin Dashboard View --}}
                <!-- @can('admin.dashboard.view')
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-blue-500/30 hover:shadow-blue-500/10 transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between mb-4">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-sm">Dashboard</p>
                            <p class="text-md md:text-lg font-semibold text-main tracking-tight">Admin Overview</p>
                        </div>
                        <div class="p-3 rounded-xl bg-blue-500/10 text-blue-600 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('admin.dashboard') }}" class="flex justify-center items-center gap-2 w-full bg-blue-500 hover:bg-blue-600 text-white rounded-lg py-2 font-medium transition-colors text-sm">
                        Dashboard
                    </a>
                </div>
                @endcan -->

                {{-- Admin Users Management --}}
                <!-- @if(auth()->user()->can('admin.users.view') || auth()->user()->can('admin.users.create') || auth()->user()->can('admin.users.edit') || auth()->user()->can('admin.users.delete'))
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-green-500/30 hover:shadow-green-500/10 transition-all duration-300 group">
                    <div class="flex items-start justify-between">
                        <p class="text-md md:text-lg font-semibold text-main tracking-tight">User Management</p>
                        <a href="{{ route('admin.users.index') }}" class="p-3 rounded-xl bg-green-500/10 text-green-600 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                @endif -->

                {{-- Admin Roles & Permissions --}}
                <!-- @if(auth()->user()->can('admin.roles.view') || auth()->user()->can('admin.roles.edit'))
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-purple-500/30 hover:shadow-purple-500/10 transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between mb-4">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-sm">Roles</p>
                            <p class="text-md md:text-lg font-semibold text-main tracking-tight">Role & Permission</p>
                        </div>
                        <div class="p-3 rounded-xl bg-purple-500/10 text-purple-600 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('admin.permissions') }}" class="flex justify-center items-center gap-2 w-full bg-purple-500 hover:bg-purple-600 text-white rounded-lg py-2 font-medium transition-colors text-sm">
                        Manage Roles
                    </a>
                </div>
                @endif -->

                {{-- Admin Campaigns --}}
                @if(auth()->user()->can('admin.campaigns.view') || auth()->user()->can('admin.campaigns.edit'))
                <div class=" bg-white rounded-2xl p-6 border border-muted-300 hover:border-primary/30 transition-all duration-300 flex flex-col gap-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-md md:text-lg font-semibold text-main">{{ __('admin_dashboard.campaign_management') }}</h3>

                        <a href="{{ route('campaigns.index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                            </svg>
                        </a>
                    </div>

                    <div class="flex flex-col gap-6">
                        <div>
                            <h4 class="text-xs font-semibold text-muted-400 uppercase tracking-wider mb-4">{{ __('admin_dashboard.campaign_scheduled') }}</h4>
                            <ul class="space-y-4">
                                @forelse($upcomingCampaigns ?? [] as $camp)
                                    <li class="pl-4 border-l-2 border-primary/30 relative">
                                        <div class="absolute -left-[5px] w-2 h-2 rounded-full bg-primary"></div>
                                        <p class="text-xs font-semibold text-main uppercase tracking-wide">{{ $camp->scheduled_at->format('M d, H:i') }}</p>
                                        <p class="text-xs md:text-sm text-muted-600 mt-0.5">{{ $camp->name }}</p>
                                        <span class="inline-block mt-2 px-2 py-0.5 bg-primary/10 text-primary text-[10px] rounded-full font-semibold">Scheduled</span>
                                    </li>
                                @empty
                                    <li class="text-xs md:text-sm text-muted-400">{{ __('admin_dashboard.campaign_no_scheduled') }}</li>
                                @endforelse
                            </ul>
                        </div>
                        <hr class="border-muted-200">
                        <div>
                            <h4 class="text-xs font-semibold text-muted-400 uppercase tracking-wider mb-4">{{ __('admin_dashboard.campaign_sent') }}</h4>
                            <ul class="space-y-4">
                                @forelse($sentCampaigns ?? [] as $camp)
                                    <li class="flex justify-between items-center group">
                                        <div>
                                            <p class="text-xs md:text-sm font-semibold text-main group-hover:text-primary transition-colors">{{ $camp->name }}</p>
                                            <span class="inline-block mt-1 px-2 py-0.5 bg-accent/10 text-accent text-[10px] rounded-full font-semibold">Sent</span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-md md:text-lg font-semibold text-main">{{ number_format($camp->sent_count) }}</p>
                                            <p class="text-[10px] text-muted-400">{{ __('admin_dashboard.campaign_users_reached') }}</p>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-xs md:text-sm text-muted-400">{{ __('admin_dashboard.campaign_no_sent') }}</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Admin Email Templates --}}
                @can('admin.email_templates.view')
                <div class=" flex flex-col h-full">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-md md:text-lg font-semibold text-main">{{ __('admin_dashboard.email_templates') }}</h3>
                        <a href="{{ route('email-templates.index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>

                    {{-- Template List --}}
                    <div class="flex flex-col gap-4 flex-1">
                        @foreach($emailTemplates->take(4) as $template)
                            <div class="group flex items-center gap-4 p-4 rounded-2xl bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 cursor-pointer">
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
                                    <div class="absolute inset-0 flex items-center justify-center text-sm font-semibold text-main">
                                        {{ mb_substr($template->name ?? '', 0, 1) }}
                                    </div>
                                </div>

                                {{-- Text Content --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm md:text-base font-semibold text-main group-hover:text-primary transition-colors">{{ $template->name }}</h4>
                                    <p class="text-xs text-muted-500 mt-1 truncate">{{ $template->subject }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endcan

                {{-- Admin Activity Logs --}}
                @can('admin.activity_logs.view')
                <!-- <div class=" bg-white rounded-2xl p-6 border border-muted-300 hover:border-primary/30 transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-md md:text-lg font-semibold text-main">{{ __('admin_dashboard.recent_activities') }}</h3>
                        <a href="{{ route('admin.activity.logs') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
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
                                                <span class="font-semibold text-primary">{{ $log->user->name ?? 'User' }}</span>
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
                </div> -->
                
                @endcan

                {{-- Admin Attendance --}}
                <!-- @can('admin.attendance.view')
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-cyan-500/30 hover:shadow-cyan-500/10 transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between mb-4">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-sm">Attendance</p>
                            <p class="text-md md:text-lg font-semibold text-main tracking-tight">Check-ins</p>
                        </div>
                        <div class="p-3 rounded-xl bg-cyan-500/10 text-cyan-600 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <a href="{{ route('users.checkin_index') }}" class="flex justify-center items-center gap-2 w-full bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg py-2 font-medium transition-colors text-sm">
                        Attendance
                    </a>
                </div>
                @endcan -->

            </div>
        </div>
        @endif
    </div>
    @else
        <div class="flex items-center justify-center min-h-[400px]">
            <div class="text-center">
                <div class="inline-block p-4 rounded-full bg-danger/10 text-danger mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h4 class="text-xl font-semibold text-main">{{ __('user_dashboard.no_permission') }}</h4>
                <p class="text-muted-500 mt-2">You do not have the required role to view this dashboard.</p>
            </div>
        </div>
    @endrole

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
let actionType = null;
let stream = null;
let detecting = false;

document.getElementById('checkInBtn').onclick = () => startFaceCheck('checkin');
document.getElementById('checkOutBtn').onclick = () => startFaceCheck('checkout');

$('#faceModal').on('hidden.bs.modal', () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    detecting = false;
});

async function startFaceCheck(type) {
    actionType = type;
    $('#faceModal').modal('show');

    const video = document.getElementById('video');
    document.getElementById('status').textContent = 'Initializing camera...';

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: "user" }
        });

        video.srcObject = stream;

        video.onloadedmetadata = async () => {
            document.getElementById('status').textContent = 'Loading face detection models...';
            await loadModels();
            document.getElementById('status').textContent = 'Detecting face...';
            detecting = true;
            detectFace();
        };
    } catch (error) {
        document.getElementById('status').textContent = 'Camera access denied or unavailable';
    }
}

async function loadModels() {
    await faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights/');
}

function detectFace() {
    if (!detecting) return;

    const video = document.getElementById('video');

    faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).then(detection => {
        if (detection) {
            const box = detection.box;
            const centerX = box.x + box.width / 2;
            const centerY = box.y + box.height / 2;
            const videoCenterX = 160;
            const videoCenterY = 160;
            const distance = Math.sqrt((centerX - videoCenterX) ** 2 + (centerY - videoCenterY) ** 2);

            if (distance < 100) {
                document.getElementById('status').textContent = 'Face aligned! Capturing...';
                detecting = false;
                setTimeout(captureFace, 500); // small delay
            } else {
                document.getElementById('status').textContent = 'Align your face inside the circle';
            }
        } else {
            document.getElementById('status').textContent = 'No face detected';
        }

        requestAnimationFrame(detectFace);
    }).catch(() => {
        requestAnimationFrame(detectFace);
    });
}

function captureFace() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');

    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(blob => {
        sendFace(blob);
    }, 'image/jpeg', 0.9);
}

function sendFace(blob) {
    const form = new FormData();
    form.append('face_image', blob);
    form.append('action', actionType);
    form.append('_token', '{{ csrf_token() }}');

    fetch('/face/verify', {
        method: 'POST',
        body: form
    })
    .then(r => r.json())
    .then(res => {
        $('#faceModal').modal('hide');
        alert(res.message);
        if (res.status) {
            location.reload();
        }
    })
    .catch(() => {
        $('#faceModal').modal('hide');
        alert('Verification failed');
    });
}
</script>
@endpush