@extends('layout_dashboard')
@section('title', __('video_chat.view_details'))

@section('content')
<div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
    <div class="flex items-center gap-4">
        <x-back-btn/>
        <div>
            <h2 class="text-2xl font-bold leading-7 text-main sm:text-3xl sm:truncate tracking-tight">
                {{ __('video_chat.view_details') }}
            </h2>
            <p class="mt-1 text-sm text-muted-500">
                {{ __('video_chat.recent_meetings_title') }}
            </p>
        </div>
        <!-- <a href="{{ route('meetings.history') }}" class="text-sm font-medium text-primary hover:text-primary-hover flex items-center gap-1 group transition-colors">
            {{ __('video_chat.view_all_history') }}
            <svg class="h-4 w-4 transform group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
        </a> -->
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-muted-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-muted-100 flex items-center justify-between">
            <div>
                <span class="block text-xs font-bold text-muted-400 uppercase tracking-wider">
                    {{ $meeting->start_time->format('M d, Y') }}
                </span>
                <span class="block text-lg font-bold text-main mt-1">
                    {{ $meeting->start_time->format('h:i A') }}
                </span>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent/10 text-accent ring-1 ring-inset ring-accent/20">
                {{ __('video_chat.status_completed') }}
            </span>
        </div>

        <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <h3 class="text-sm font-bold text-muted-400 uppercase tracking-wider mb-2">Meeting ID</h3>
                <div class="text-base font-semibold text-main break-all">{{ $meeting->meeting_id }}</div>

                <div class="mt-6">
                    <h3 class="text-sm font-bold text-muted-400 uppercase tracking-wider mb-2">{{ __('video_chat.label_notes') }}</h3>
                    <p class="text-sm text-muted-500 leading-relaxed">
                        {{ $meeting->notes ?? __('video_chat.no_notes') }}
                    </p>
                </div>

                <div class="mt-6">
                    <h3 class="text-sm font-bold text-muted-400 uppercase tracking-wider mb-2">Duration</h3>
                    <p class="text-sm text-muted-500">
                        @if($meeting->end_time)
                            {{ $meeting->start_time->diffForHumans($meeting->end_time, true) }}
                        @else
                            --
                        @endif
                    </p>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold text-muted-400 uppercase tracking-wider mb-3">Attendees ({{ $meeting->attendees_count }})</h3>
                <div class="space-y-3">
                    @forelse($meeting->attendees as $attendee)
                        <div class="flex items-center gap-3">
                            <img class="h-9 w-9 rounded-full ring-2 ring-white object-cover" 
                                 src="{{ $attendee->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($attendee->name) }}" 
                                 alt="{{ $attendee->name }}">
                            <div>
                                <div class="text-sm font-semibold text-main">{{ $attendee->name }}</div>
                                @if($attendee->joined_at)
                                    <div class="text-xs text-muted-400">Joined {{ $attendee->joined_at->format('h:i A') }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-muted-500">
                            {{ __('video_chat.empty_history') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
