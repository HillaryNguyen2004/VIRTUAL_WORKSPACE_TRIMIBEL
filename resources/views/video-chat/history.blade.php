@extends('layout_dashboard')
@section('title', __('video_chat.recent_meetings_title'))

@section('content')
<div class="flex flex-col gap-6 w-full text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
    <div class="flex items-center gap-4">
        <x-back-btn :route="'meeting'" />
        <div>
            <h2 class="text-2xl font-bold leading-7 text-main sm:text-3xl sm:truncate tracking-tight">
                {{ __('video_chat.recent_meetings_title') }}
            </h2>
            <p class="mt-1 text-sm text-muted-500">
                {{ __('video_chat.view_all_history') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 @2xl:grid-cols-2 @5xl:grid-cols-3 gap-6 max-w-[1200px] w-full mx-auto animation-fade-in-up">
        @forelse($meetingHistory as $meeting)
        <x-white-card-container class="flex-col gap-4 hover:border-primary/50 group">
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
                        <x-user-avatar :user="$attendee" size="h-8 w-8" />
                    @endforeach
                    @if($meeting->attendees_count > 3)
                        <span class="flex items-center justify-center h-8 w-8 rounded-full ring-2 ring-white bg-muted-100 text-xs font-medium text-muted-500">
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
        </x-white-card-container>
        @empty
        <div class="col-span-full py-16 flex flex-col items-center justify-center  border-2 border-dashed border-muted-200 rounded-2xl">
            <div class="p-4 rounded-full bg-muted-100 text-muted-400 mb-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            </div>
            <span class="block text-sm font-medium text-muted-500">
                {{ __('video_chat.empty_history') }}
            </span>
        </div>
        @endforelse
    </div>
</div>
@endsection
