@extends('layout_dashboard')
@section('title', __('video_chat.title'))

@section('content')
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        
        <div class="mb-10 ">
            <div class="md:flex md:items-center md:justify-between mb-6 ">
                <div class="flex-1 min-w-0">
                    <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">
                        {{ __('video_chat.title') }}
                    </h1>
                    <p class="text-muted-500 text-sm md:text-base mt-1">
                        {{ __('video_chat.subtitle') }}
                    </p>
                </div>
            </div>

            <div class="relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-10 mb-6 bg-panel-left-gradient border-muted-200 shadow-xl shadow-secondary/20 rounded-2xl p-6 md:p-10 lg:p-12 animate-fade-in-up">

                <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>

                {{-- Main Content (left side) --}}
                <div class="flex flex-col flex-1 items-start gap-10 justify-between w-full md:min-w-0 z-10">    
                    <div class="text-left">
                        <h1 class="text-2xl md:text-3xl font-semibold text-canvas override mb-2">
                            {{ __('video_chat.smart_meeting_heading') }}
                        </h1>
                        <p class="text-sm md:text-base text-muted-100 max-w-lg">
                            {{ __('video_chat.smart_meeting_desc') }}
                        </p>
                    </div>

                    <button type="button" id="btnOpenSmartModal"
                        class="flex flex-row w-auto items-center gap-2 px-6 py-3 rounded-xl bg-canvas text-primary shadow-canvas/5 shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-canvas/10 hover:scale-105 active:scale-95 group/btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-wand-sparkles-icon lucide-wand-sparkles">
                            <path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"/>
                            <path d="m14 7 3 3"/>
                            <path d="M5 6v4"/>
                            <path d="M19 14v4"/>
                            <path d="M10 2v2"/>
                            <path d="M7 8H3"/>
                            <path d="M21 16h-4"/>
                            <path d="M11 3H9"/>
                        </svg>
                        <span class="font-semibold text-xs md:text-sm">{{ __('video_chat.smart_meeting_cta') }}</span>
                    </button>
                </div>

                {{-- Decorative Elements (right side) --}}
                <div class="hidden md:flex flex-1 justify-end items-center relative z-10 pointer-events-none">
                    <div class="relative w-48 h-48">
                        <div class="absolute right-4 top-4 w-32 h-32 bg-white/20 rounded-3xl backdrop-blur-md border border-white/30 rotate-12 shadow-2xl shadow-main/10 flex items-center justify-center transition-transform duration-700 hover:rotate-6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-white/90">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" x2="16" y1="2" y2="6"/>
                                <line x1="8" x2="8" y1="2" y2="6"/>
                                <line x1="3" x2="21" y1="10" y2="10"/>
                                <path d="M8 14h.01"/>
                                <path d="M12 14h.01"/>
                                <path d="M16 14h.01"/>
                                <path d="M8 18h.01"/>
                                <path d="M12 18h.01"/>
                                <path d="M16 18h.01"/>
                            </svg>
                        </div>
                        
                        <div class="absolute -left-2 bottom-8 w-16 h-16 bg-white/10 rounded-2xl backdrop-blur-sm border border-white/20 shadow-lg shadow-main/10 flex items-center justify-center -rotate-12">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white/80">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex flex-col md:grid md:grid-cols-5 gap-2 w-full max-w-3xl mx-auto p-2 border border-muted-300 rounded-2xl shadow-md shadow-muted-50 animate-fade-in-up [animation-delay:100ms]">
                {{-- Join Meeting --}}
                <form method="post" action="{{ route('validateMeeting') }}" class="w-full md:col-span-2">
                    {{ csrf_field() }}
                    <div class="flex items-stretch w-full rounded-xl border border-muted-300 bg-canvas overflow-hidden hover:border-primary/50  focus-within:ring-1 focus-within:ring-primary/20 focus-within:border-primary focus-within:hover:border-primary transition-all">
                        
                        <div class="relative flex-grow flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-muted-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                    <path d="M18 21a8 8 0 0 0-16 0"/>
                                    <circle cx="10" cy="8" r="5"/>
                                    <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/>
                                </svg>
                            </div>
                            
                            <input type="text" name="meetingId" id="meetingId" 
                                    class="w-full h-full pl-11 pr-4 py-3 bg-transparent text-sm text-main placeholder-muted-400 focus:outline-none focus:ring-0 border-none" 
                                    placeholder="{{ __('video_chat.placeholder') }}" required>
                        </div>
                        
                        <button type="submit" 
                                class="flex-shrink-0 whitespace-nowrap flex items-center justify-center px-4 py-2 text-sm md:text-base font-medium text-muted-600 bg-muted-100 hover:bg-muted-200 transition-colors focus:outline-none border-l border-muted-300">
                            {{ __('video_chat.join_button') }}
                        </button>
                        
                    </div>
                </form>

                <div class="flex flex-row gap-2 w-full md:col-span-3">

                    {{-- Instant Meeting --}}
                    <form method="post" action="{{ route('createMeeting') }}" class="w-full">
                        {{ csrf_field() }}
                        <button type="submit" class="group flex items-center justify-center gap-2 rounded-xl w-full h-full p-2 text-md md:text-base font-medium text-muted-600 bg-canvas border border-muted-300 hover:border-primary/50 hover:bg-primary/5 hover:text-primary transition-all focus:ring-1 focus:ring-primary/30 focus:border-primary active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-square-plus-icon lucide-square-plus"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>
                            {{ __('video_chat.instant_button') }}
                        </button>
                    </form>

                    {{-- Scheduled Meeting --}}
                    <button type="button" id="btnOpenScheduleModal" class="group flex items-center justify-center gap-2 rounded-xl w-full h-full p-2 text-md md:text-base font-medium text-muted-600 bg-canvas border border-muted-300 hover:border-primary/50 hover:bg-primary/5 hover:text-primary transition-all focus:ring-1 focus:ring-primary/30 focus:border-primary active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-calendar-plus-icon lucide-calendar-plus">
                            <path d="M16 19h6"/>
                            <path d="M16 2v4"/>
                            <path d="M19 16v6"/>
                            <path d="M21 12.598V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8.5"/>
                            <path d="M3 10h18"/>
                            <path d="M8 2v4"/>
                        </svg>
                        {{ __('video_chat.scheduled_button') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- History Section --}}
        <div class="flex items-center justify-between mb-6 animate-fade-in-up [animation-delay:150ms]">
            <h4 class="text-md md:text-lg font-semibold text-main">{{ __('video_chat.recent_meetings_title') }}</h4>
            
            <a href="{{ route('meetings.history') }}" class="text-xs md:text-sm text-primary font-medium hover:underline transition-colors">
                {{ __('video_chat.view_all_history') }}
            </a>
        </div>

        <div class="grid grid-cols-1 @2xl:grid-cols-2 @5xl:grid-cols-3 gap-6 animate-fade-in-up [animation-delay:200ms]">
            @forelse($meetingHistory->take(3) as $meeting)
            <div class="bg-white rounded-2xl border border-muted-300 hover:border-primary/30 transition-all duration-300 flex flex-col h-full group">
                
                {{-- Card Header --}}
                <div class="px-6 py-5 border-b border-muted-100 flex justify-between items-start">
                    <div>
                        <span class="block text-xs font-bold text-muted-400 uppercase tracking-wider">
                            {{ $meeting->start_time->format('M d, Y') }}
                        </span>
                        <span class="block text-lg font-bold text-main mt-1 group-hover:text-primary transition-colors">
                            {{ $meeting->start_time->format('h:i A') }}
                        </span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent/10 text-accent ring-1 ring-inset ring-accent/20">
                        {{ __('video_chat.status_completed') }}
                    </span>
                </div>

                {{-- Card Body --}}
                <div class="px-6 py-5 flex-grow">
                    {{-- Attendees --}}
                    <div class="flex -space-x-3 overflow-hidden mb-4 pl-1">
                        @foreach($meeting->attendees->take(3) as $attendee)
                            <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white object-cover" 
                                src="{{ $attendee->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($attendee->name) }}" 
                                alt="{{ $attendee->name }}"
                                title="{{ $attendee->name }}">
                        @endforeach
                        @if($meeting->attendees_count > 3)
                            <span class=" items-center text-center justify-center h-8 w-8 rounded-full ring-2 ring-white bg-muted-100 text-xs font-medium text-muted-500">
                                +{{ $meeting->attendees_count - 3 }}
                            </span>
                        @endif
                    </div>
                    
                    {{-- Notes --}}
                    <div class="text-sm text-muted-500 line-clamp-3">
                        <span class="font-medium text-main">{{ __('video_chat.label_notes') }}</span> 
                        {{ $meeting->notes ?? __('video_chat.no_notes') }}
                    </div>
                </div>

                {{-- Card Footer --}}
                <div class="bg-muted-50/50 px-6 py-4 border-t border-muted-100 rounded-b-2xl">
                    <a href="{{ route('meetings.details', $meeting->id) }}" class="text-sm font-semibold text-primary hover:text-primary-hover flex items-center justify-center gap-2 transition-colors">
                        {{ __('video_chat.view_details') }}
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-full py-16 flex flex-col items-center justify-center bg-white border-2 border-dashed border-muted-200 rounded-2xl">
                <div class="p-4 rounded-full bg-muted-50 text-muted-400 mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </div>
                <span class="block text-sm font-medium text-muted-500">
                    {{ __('video_chat.empty_history') }}
                </span>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Schedule Meeting Modal --}}
    <div id="scheduleMeetingModal" class="fixed inset-0 bg-main/40 z-50 hidden flex items-center justify-center transition-opacity">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl animate-fade-in-up">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-muted-200">
                <h3 class="text-lg font-bold text-main">{{ __('video_chat.scheduled_button') }}</h3>
                <button id="btnCloseScheduleModal" class="text-muted-400 hover:text-primary transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="space-y-4">
                {{-- Title --}}
                <x-form.input
                    label="{{ __('video_chat.schedule_meeting_title') }}"
                    id="schTitle"
                    name="schTitle"
                    type="text"
                    placeholder="{{ __('video_chat.schedule_meeting_title_placeholder') }}"
                    class="mt-1"
                    :isRequired="true"
                />
                
                {{-- Date --}}
                <x-form.input
                    label="{{ __('video_chat.schedule_meeting_date') }}"
                    id="schDate"
                    name="schDate"
                    type="date"
                    class="mt-1"
                    :isRequired="true"
                />
                
                {{-- Time --}}
                <div class="flex gap-4 mt-1">
                    <x-form.input
                        label="{{ __('video_chat.schedule_meeting_start_time') }}"
                        id="schStart"
                        name="schStart"
                        type="time"
                        class="w-full"
                        :isRequired="true"
                    />
                    <x-form.input
                        label="{{ __('video_chat.schedule_meeting_end_time') }}"
                        id="schEnd"
                        name="schEnd"
                        type="time"
                        class="w-full"
                        :isRequired="true"
                    />
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4">
                <button id="btnSaveMeeting" class="px-5 py-2.5 text-sm font-bold bg-primary text-white rounded-xl shadow-lg shadow-primary/20 hover:bg-primary-hover active:scale-95 transition-all flex items-center gap-2">
                    <span>{{ __('app.btn_create') }}</span>
                    {{-- Loading Spinner (Hidden by default) --}}
                    <svg id="schSpinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Smart Meeting Modal --}}
    <div id="smartMeetingModal" class="fixed inset-0 bg-main/40 z-50 hidden flex items-center justify-center transition-opacity">
        <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl flex flex-col max-h-[90vh] animate-fade-in-up">
            <div class="flex justify-between items-center mb-5 pb-3 border-b border-muted-200">
                <div class="flex flex-col gap-1">
                    <h3 class="text-lg font-bold text-main">{{ __('video_chat.smart_meeting_heading') }}</h3>
                    <p class="text-xs text-muted-500">{{ __('video_chat.smart_meeting_subheading') }}</p>
                </div>
                <button id="btnCloseSmartModal" class="text-muted-400 hover:text-primary transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="space-y-4 custom-scrollbar" id="smartStep1">             
                <x-form.input
                    label="{{ __('video_chat.smart_meeting_title') }}"
                    id="smartTitle"
                    name="smartTitle"
                    type="text"
                    placeholder="{{ __('video_chat.smart_meeting_title_placeholder') }}"
                    class="mt-1"
                    :isRequired="true"
                />

                <x-form.input
                    label="{{ __('video_chat.smart_meeting_duration') }}"
                    id="smartDuration"
                    name="smartDuration"
                    type="number"
                    min="15"
                    step="5"
                    placeholder="{{ __('video_chat.smart_meeting_duration_placeholder') }}"
                    class="mt-1"
                    :isRequired="true"
                />

                {{-- Attendees Section --}}
                <div>
                    <label class="block text-sm font-semibold text-main mb-2">{{ __('video_chat.smart_meeting_attendees') }}</label>
                    
                    {{-- Search Input --}}
                    <div class="relative w-full group mb-2">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-muted-400 group-focus-within:text-primary transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="smartUserSearch" 
                            class="block w-full pl-10 pr-3 py-2 border border-muted-300 rounded-xl leading-5 bg-white placeholder-muted-400 focus:outline-none focus:ring-1 focus:ring-primary/20 focus:border-primary sm:text-sm transition-shadow" 
                            placeholder="{{ __('video_chat.smart_meeting_attendees_placeholder') }}">
                    </div>

                    {{-- Selected Users Badge Area --}}
                    <div id="smartSelectedUsers" class="flex flex-wrap gap-2 empty:hidden mb-2 transition-all"></div>
                    
                    {{-- Search Results --}}
                    <div id="smartUserSearchResults" class="max-h-40 overflow-y-auto custom-scrollbar border border-muted-300 rounded-xl bg-white">
                        @foreach(\App\Models\User::where('id', '!=', auth()->id())->get() as $user)
                            <div class="smart-user-option p-3 hover:bg-muted-50 cursor-pointer flex items-center gap-3 text-sm border-b border-muted-100 last:border-0 transition-colors"
                                data-id="{{ $user->id }}" 
                                data-name="{{ $user->name }}" 
                                data-search="{{ strtolower($user->name . ' ' . $user->email) }}">
                                
                                {{-- Dynamic Avatar Logic --}}
                                @php
                                    // Look for photo on the user model directly
                                    $photoData = $user->avatar_url ?? $user->profile_photo ?? $user->avatar ?? null;
                                    $userName = $user->name ?? 'U';
                                    $userId = $user->id ?? 0;
                                    $initial = strtoupper(mb_substr($userName, 0, 1));
                                    
                                    $colors = ['bg-primary/10 text-primary', 'bg-secondary/10 text-secondary', 'bg-accent/20 text-accent'];
                                    $colorClass = $colors[$userId % count($colors)];
                                @endphp

                                @if($photoData)
                                    <img src="{{ str_starts_with($photoData, 'http') ? $photoData : asset('storage/' . $photoData) }}" 
                                        alt="{{ $userName }}" 
                                        class="h-8 w-8 rounded-full object-cover ring-2 ring-white flex-shrink-0">
                                @else
                                    <div class="h-8 w-8 rounded-full {{ $colorClass }} ring-2 ring-white grid place-items-center font-bold text-sm flex-shrink-0">
                                        {{ $initial }}
                                    </div>
                                @endif
                                
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-main leading-tight truncate">{{ $user->name }}</p>
                                    <p class="text-xs text-muted-500 truncate">{{ $user->email }}</p>
                                </div>
                                
                                {{-- Add Icon --}}
                                <div class="text-primary opacity-0 group-hover:opacity-100 transition-opacity pr-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </div>
                            </div>
                        @endforeach
                        
                        <div id="smartUserNoResults" class="hidden p-4 text-center text-sm text-muted-400 italic">
                            No users found.
                        </div>
                    </div>
                    
                    <p class="text-xs text-muted-400 mt-2">{{ __('video_chat.smart_meeting_attendees_tooltip') }}</p>
                </div>
            </div>

            {{-- Step 2: Slot Results (Hidden initially) --}}
            <div id="smartStep2" class="hidden space-y-4 flex-1 overflow-y-auto custom-scrollbar">
                <h4 class="text-sm font-semibold text-main">{{ __('video_chat.smart_meeting_slots') }}</h4>
                <div id="slotContainer" class="grid grid-cols-1 gap-2">
                    </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4 mt-4">
                <button id="btnFindSlots" class="px-5 py-2.5 text-sm font-bold bg-primary text-white rounded-xl shadow-lg shadow-primary/20 hover:bg-primary-hover active:scale-95 transition-all flex items-center gap-2">
                    {{ __('video_chat.smart_meeting_find_slots') }}
                </button>
                
                <button id="btnBookSmartMeeting" class="hidden px-5 py-2.5 text-sm font-bold bg-primary text-white rounded-xl shadow-lg shadow-primary/20 hover:bg-primary-hover active:scale-95 transition-all items-center gap-2">
                    {{ __('video_chat.smart_meeting_book') }}
                </button>
            </div>
        </div>
    </div>

    @vite(['resources/js/video-chat.js'])
@endsection