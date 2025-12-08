@extends('layout_dashboard')
@section('title', __('video_chat.title'))

@section('content')
<div class="flex flex-col gap-6 w-full max-w-7xl mx-auto text-main">
    
    <div class="mb-10 animate-fade-in-up">
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-main sm:text-3xl sm:truncate tracking-tight">
                    {{ __('video_chat.title') }}
                </h2>
                <p class="mt-1 text-sm text-muted-500">
                    {{ __('video_chat.subtitle') }}
                </p>
            </div>
        </div>

        <div class="bg-white border border-muted-200 shadow-sm rounded-2xl overflow-hidden relative">
            {{-- Decorative blurry blob --}}
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-secondary/10 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center relative z-10">
                    
                    {{-- Join Section --}}
                    <div class="flex flex-col gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-main">{{ __('video_chat.join_section_title') }}</h3>
                            <p class="text-sm text-muted-500">{{ __('video_chat.join_section_desc') }}</p>
                        </div>
                        
                        <form method="post" action="{{ route('validateMeeting') }}">
                            {{ csrf_field() }}
                            <div class="flex rounded-xl shadow-sm">
                                <div class="relative flex-grow focus-within:z-10">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        {{-- Icon --}}
                                        <svg class="h-5 w-5 text-muted-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                        </svg>
                                    </div>
                                    <input type="text" name="meetingId" id="meetingId" 
                                           class="focus:ring-2 focus:ring-primary/30 focus:border-primary block w-full rounded-l-xl pl-11 sm:text-sm bg-canvas border-muted-200 text-main placeholder-muted-400 py-3 transition-all" 
                                           placeholder="{{ __('video_chat.placeholder') }}" required>
                                </div>
                                <button type="submit" class="-ml-px relative inline-flex items-center space-x-2 px-6 py-3 border border-muted-200 text-sm font-medium rounded-r-xl text-muted-600 bg-muted-50 hover:bg-muted-100 focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary transition-colors">
                                    <span>{{ __('video_chat.join_button') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Create Section --}}
                    <div class="flex flex-col justify-center items-center md:items-start border-t md:border-t-0 md:border-l border-muted-200 md:pl-10 pt-8 md:pt-0">
                        <div class="mb-4 text-center md:text-left">
                            <h3 class="text-lg font-semibold text-main">{{ __('video_chat.create_section_title') }}</h3>
                            <p class="text-sm text-muted-500">{{ __('video_chat.create_section_desc') }}</p>
                        </div>
                        
                        <form method="post" action="{{ route('createMeeting') }}" class="w-full md:w-auto">
                            {{ csrf_field() }}
                            <button type="submit" class="group w-full md:w-auto inline-flex justify-center items-center px-8 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-primary/25 text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-4 focus:ring-primary/20 transition-all active:scale-95">
                                {{-- Icon --}}
                                <svg class="mr-2 -ml-1 h-5 w-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                {{ __('video_chat.new_button') }}
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- History Section --}}
    <div class="flex items-center justify-between mb-6 animate-fade-in-up [animation-delay:150ms]">
        <h3 class="text-xl font-bold text-main tracking-tight">{{ __('video_chat.recent_meetings_title') }}</h3>
        
        <a href="{{ route('meetings.history') }}" class="text-sm font-medium text-primary hover:text-primary-hover flex items-center gap-1 group transition-colors">
            {{ __('video_chat.view_all_history') }}
            <svg class="h-4 w-4 transform group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in-up [animation-delay:200ms]">
        @forelse($meetingHistory->take(4) as $meeting)
        <div class="bg-white rounded-2xl shadow-sm border border-muted-200 hover:shadow-md hover:border-primary/30 transition-all duration-300 flex flex-col h-full group">
            
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
                <a href="#" class="text-sm font-semibold text-primary hover:text-primary-hover flex items-center justify-center gap-2 transition-colors">
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
@endsection