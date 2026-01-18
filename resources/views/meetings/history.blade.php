@extends('layout_dashboard')
@section('title', __('video_chat.recent_meetings_title'))

@section('content')
<div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold leading-7 text-main sm:text-3xl sm:truncate tracking-tight">
                {{ __('video_chat.recent_meetings_title') }}
            </h2>
            <p class="mt-1 text-sm text-muted-500">
                {{ __('video_chat.view_all_history') }}
            </p>
        </div>
        <a href="{{ route('meeting') }}" class="text-sm font-medium text-primary hover:text-primary-hover flex items-center gap-1 group transition-colors">
            {{ __('video_chat.title') }}
            <svg class="h-4 w-4 transform group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
        </a>
    </div>

    <div class="grid grid-cols-1 @2xl:grid-cols-2 @5xl:grid-cols-3 gap-6">
        @forelse($meetingHistory as $meeting)
        <div class="bg-white rounded-2xl shadow-sm border border-muted-200 hover:shadow-md hover:border-primary/30 transition-all duration-300 flex flex-col h-full group">
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

            <div class="px-6 py-5 flex-grow">
                <div class="flex -space-x-3 overflow-hidden mb-4 pl-1">
                    @foreach($meeting->attendees->take(3) as $attendee)
                        <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white object-cover" 
                             src="{{ $attendee->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($attendee->name) }}" 
                             alt="{{ $attendee->name }}"
                             title="{{ $attendee->name }}">
                    @endforeach
                    @if($meeting->attendees_count > 3)
                        <span class="items-center text-center justify-center h-8 w-8 rounded-full ring-2 ring-white bg-muted-100 text-xs font-medium text-muted-500">
                            +{{ $meeting->attendees_count - 3 }}
                        </span>
                    @endif
                </div>

                <div class="text-sm text-muted-500 line-clamp-3">
                    <span class="font-medium text-main">{{ __('video_chat.label_notes') }}</span> 
                    {{ $meeting->notes ?? __('video_chat.no_notes') }}
                </div>
            </div>

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
@endsection
