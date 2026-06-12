@extends('layout_dashboard')
@section('title', __('video_chat.meeting_details'))

@section('content')
<div class="flex flex-col gap-4 w-full text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

    {{-- HEADER --}}
    <div class="flex flex-wrap gap-4 mb-6 items-center lg:justify-between">

        {{-- TITLE & DESCRIPTION --}}
        <div class="flex items-center gap-4">
            <x-back-btn />
            <div>
                <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">
                    {{ __('video_chat.meeting_details') }}
                </h1>
                <p class="text-muted-500 text-sm md:text-base mt-1 flex flex-wrap items-center gap-3">
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ $meeting->start_time->format('M d, Y') }}
                    </span>
                    <span class="text-muted-300">|</span>
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $meeting->start_time->format('h:i A') }} - {{ $meeting->end_time ? $meeting->end_time->format('h:i A') : __('video_chat.ongoing') }} 
                        @if($meeting->end_time)
                            ({{ $meeting->start_time->diffInMinutes($meeting->end_time) }}m)
                        @endif
                    </span>
                    <span class="text-muted-300">|</span>
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        ID: {{ $meeting->meeting_id }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    {{-- FUNCTIONAL TABS NAVIGATION --}}
    <div class="max-w-[1200px] justify-start w-full mx-auto">
        <div class="flex flex-wrap items-center w-fit text-sm font-medium text-muted-500 bg-white p-1 border border-muted-300 rounded-2xl" id="tabs-container">
            
            {{-- Summary Tab (Active by default) --}}
            <button type="button" data-target="summary" class="tab-button flex items-center gap-2 transition-all duration-300 rounded-xl bg-primary/10 px-5 py-2.5 text-primary">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09l2.846.813-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                </svg>
                {{ __('video_chat.summary_tab') }}
            </button>

            {{-- Divider 1 (Hidden initially because Summary is active) --}}
            <div id="divider-1" class="h-4 w-px bg-muted-300 opacity-0 transition-all duration-300"></div>

            {{-- Transcript Tab --}}
            <button type="button" data-target="transcript" class="tab-button flex items-center gap-2 transition-all duration-300 px-5 py-2.5 hover:text-main">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                {{ __('video_chat.transcript_tab') }}
            </button>

            {{-- Divider 2 (Visible initially because neither Transcript nor Recording are active) --}}
            <div id="divider-2" class="h-4 w-px bg-muted-300 opacity-100 transition-all duration-300"></div>

            {{-- Recording Tab --}}
            <button type="button" data-target="recording" class="tab-button flex items-center gap-2 transition-all duration-300 px-5 py-2.5 hover:text-main">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                {{ __('video_chat.recording_tab') }}
            </button>

            {{-- Divider 3 (Visible initially because neither Recording nor Notes are active) --}}
            <div id="divider-3" class="h-4 w-px bg-muted-300 opacity-100 transition-all duration-300"></div>

            {{-- Notes Tab --}}
            <button type="button" data-target="notes" class="tab-button flex items-center gap-2 transition-all duration-300 px-5 py-2.5 hover:text-main">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                {{ __('video_chat.notes_tab') }}
            </button>
        </div>
    </div>



    {{-- TAB CONTENTS --}}
    <div id="tab-content-container @container" class="max-w-[1200px] mx-auto w-full">
        
        {{-- SUMMARY CONTENT (Default Active) --}}
        <div id="content-summary" class="tab-content grid grid-cols-1 @xl:grid-cols-2 @5xl:grid-cols-4 gap-4">
            {{-- Overview (Full Width) --}}
            <div class="@xl:col-span-2 @5xl:col-span-4 p-6 flex flex-col gap-4 bg-primary-gradient rounded-2xl shadow-lg shadow-primary/10 animate-fade-in-up">
                <h4 class="flex items-center gap-2 text-lg font-semibold  border-b border-canvas/20 text-canvas pb-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-canvas/10 ">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </div>
                    {{ __('video_chat.overview_title') }}
                </h4>
                <p class="text-sm text-canvas leading-relaxed">
                    {{ $meeting->notes ?? __('video_chat.overview_default') }}
                </p>
            </div>

            {{-- Project Progress --}}
            <x-white-card-container class="p-6 flex-col gap-4 hover:border-primary/50 animate-fade-in-up [animation-delay:100ms]">
                <h4 class="flex items-center gap-2 text-lg font-semibold text-main border-b border-muted-100 pb-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    {{ __('video_chat.project_progress_title') }}
                </h4>
                <ul class="text-sm text-muted-500 space-y-3 list-disc pl-5">
                    <li>Backend development progressing well, with significant contributions from Jane Smith.</li>
                    <li>Frontend dashboard redesign nearing completion, ready for testing.</li>
                    <li>Positive feedback received on UI designs by Sarah Lee.</li>
                </ul>
            </x-white-card-container>

            {{-- Challenges Faced --}}
            <x-white-card-container class="p-6 flex-col gap-4 hover:border-primary/50 animate-fade-in-up [animation-delay:100ms]">
                <h4 class="flex items-center gap-2 text-lg font-semibold text-main border-b border-muted-100 pb-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    {{ __('video_chat.challenges_faced_title') }}
                </h4>
                <ul class="text-sm text-muted-500 space-y-3 list-disc pl-5">
                    <li>Difficulty integrating a third-party API for geolocation services.</li>
                    <li>Discussion on potential solutions and collaborative efforts to overcome this obstacle.</li>
                </ul>
            </x-white-card-container>

            {{-- Action Items --}}
            <x-white-card-container class="p-6 flex-col gap-4 hover:border-primary/50 animate-fade-in-up [animation-delay:100ms]">
                <h4 class="flex items-center gap-2 text-lg font-semibold text-main border-b border-muted-100 pb-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    {{ __('video_chat.action_items_title') }}
                </h4>
                <div class="space-y-4">
                    @forelse($meeting->attendees as $attendee)
                        <div class="flex items-center gap-3">
                            <x-user-avatar :user="$attendee" size="h-8 w-8" ringClass="" withRing="false" />
                            <div class="text-sm text-muted-500">
                                <span class="font-medium text-main">{{ $attendee->name }}</span>: {{ __('video_chat.action_items_review_backend') }}
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-muted-500">{{ __('video_chat.action_items_empty') }}</div>
                    @endforelse
                </div>
            </x-white-card-container>

            {{-- Next Steps --}}
            <x-white-card-container class="p-6 flex-col gap-4 hover:border-primary/50 animate-fade-in-up [animation-delay:100ms]">
                <h4 class="flex items-center gap-2 text-lg font-semibold text-main border-b border-muted-100 pb-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 lucide lucide-chevrons-right-icon lucide-chevrons-right">
                            <path d="m6 17 5-5-5-5"/>
                            <path d="m13 17 5-5-5-5"/>
                        </svg>
                    </div>
                    {{ __('video_chat.next_steps_title') }}
                </h4>
                <ul class="text-sm text-muted-500 space-y-3 list-disc pl-5">
                    <li>Mid-week check-in scheduled to review progress and address any emerging issues.</li>
                    <li>Clear roadmap established for the week ahead, ensuring continued collaboration.</li>
                </ul>
            </x-white-card-container>

            {{-- Attendees List (Full Width) --}}
            <x-white-card-container color="secondary/50" class="@xl:col-span-2 @5xl:col-span-4 overflow-hidden flex-col mt-2 animate-fade-in-up [animation-delay:150ms]">
                <div class="flex items-center justify-between border-b border-muted-200 px-6 py-4">
                    <div>
                        <h4 class="text-lg font-semibold text-main">{{ __('video_chat.attendees_title') }} ({{ $meeting->attendees_count }})</h4>
                        <p class="text-sm text-muted-500">{{ __('video_chat.attendees_desc') }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-muted-50 text-xs uppercase tracking-wider text-muted-500">
                            <tr>
                                <th class="px-6 py-4 text-left font-semibold">{{ __('video_chat.attendees_participant_col') }}</th>
                                <th class="px-6 py-4 text-left font-semibold">{{ __('video_chat.attendees_joined_col') }}</th>
                                <th class="px-6 py-4 text-right font-semibold">{{ __('video_chat.attendees_status_col') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-muted-100 text-sm">
                            @forelse($meeting->attendees as $attendee)
                                <tr class="hover:bg-muted-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <x-user-avatar :user="$attendee" size="h-10 w-10" ringClass="" withRing="false" />
                                            <div>
                                                <div class="font-semibold text-main">{{ $attendee->name }}</div>
                                                <div class="text-xs text-muted-500">{{ $attendee->role ?? __('video_chat.attendees_team_member') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-muted-500">
                                        {{ $attendee->joined_at ? $attendee->joined_at->format('h:i A') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if($attendee->joined_at)
                                            <span class="inline-flex items-center rounded-full bg-success/10 px-3 py-1 text-xs font-semibold text-success">
                                                {{ __('video_chat.attendees_status_present') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-muted-100 px-3 py-1 text-xs font-semibold text-muted-500">
                                                {{ __('video_chat.attendees_status_invited') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-12 text-center text-muted-500">
                                        {{ __('video_chat.attendees_empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-white-card-container>
        </div>

        {{-- TRANSCRIPT CONTENT --}}
        <div id="content-transcript" class="tab-content hidden">
            <x-white-card-container class="p-0 flex flex-col overflow-hidden animate-fade-in-up">
                
                {{-- Transcript Header & Search --}}
                <div class="flex items-center justify-between border-b border-muted-200 px-6 py-4 bg-white sticky top-0 z-10">
                    <div>
                        <h4 class="text-lg font-semibold text-main">{{ __('video_chat.transcript_title') }}</h4>
                        <p class="text-sm text-muted-500">{{ __('video_chat.transcript_desc') }}</p>
                    </div>
                    
                    {{-- Optional Search/Filter for Transcript --}}
                    <x-form.input
                        type="text" 
                        placeholder="{{ __('video_chat.transcript_search_placeholder') }}" 
                        class=""
                    />
                </div>

                {{-- Transcript List --}}
                <div class="p-6 flex flex-col space-y-6 max-h-[600px] overflow-y-auto">
                    
                    @forelse(($meeting->transcriptions ?? collect()) as $transcript)
                        <div class="group flex gap-4 transition-colors p-2 -mx-2 rounded-xl hover:bg-muted-50">
                            
                            {{-- Avatar (Direct Eloquent Relationship) --}}
                            @if($transcript->user)
                                <x-user-avatar :user="$transcript->user" size="h-10 w-10" ringClass="" />
                            @else
                                {{-- Fallback for unregistered guests --}}
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary font-bold shadow-sm">
                                    ?
                                </div>
                            @endif
                            
                            {{-- Content Wrapper --}}
                            <div class="flex-1 min-w-0">
                                {{-- Name & Timestamp Row --}}
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-main text-sm">
                                            {{ $transcript->user ? $transcript->user->name : 'Unknown Guest' }}
                                        </span>
                                    </div>
                                    
                                    {{-- Timestamp & Copy Button --}}
                                    <div class="flex items-center gap-3 text-xs font-medium text-muted-400">
                                        <button class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 text-primary hover:text-primary-hover copy-transcript-btn" data-text="{{ $transcript->text }}">
                                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            {{ __('video_chat.transcript_copy_btn') }}
                                        </button>
                                        {{ $transcript->created_at ? $transcript->created_at->format('h:i A') : '' }}
                                    </div>
                                </div>
                                
                                {{-- Spoken Text --}}
                                <p class="text-sm text-muted-600 leading-relaxed pr-8">
                                    {{ $transcript->text }}
                                </p>
                            </div>
                        </div>
                    @empty
                        {{-- Empty State --}}
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted-100 text-muted-400 mb-4">
                                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                </svg>
                            </div>
                            <p class="text-muted-500">{{ __('video_chat.transcript_empty') }}</p>
                        </div>
                    @endforelse

                </div>
            </x-white-card-container>
        </div>

        {{-- NOTES CONTENT --}}
        <div id="content-notes" class="tab-content hidden">
            <x-white-card-container class="p-0 flex flex-col overflow-hidden animate-fade-in-up">

                {{-- Notes Header & Status --}}
                <div class="flex items-center justify-between border-b border-muted-200 px-6 py-4 bg-white sticky top-0 z-10">
                    <div>
                        <h4 class="text-lg font-semibold text-main">{{ __('video_chat.notes_title') }}</h4>
                        <p class="text-sm text-muted-500">{{ __('video_chat.notes_desc') }}</p>
                    </div>
                    
                    {{-- Optional Search/Filter for Transcript --}}
                    @if($meeting->notes)
                        <div class="rounded-full bg-accent/10 px-3 py-1.5 text-sm font-medium text-accent border border-accent/50">
                            {{ __('video_chat.notes_saved_label') }}
                        </div>
                    @else
                        <div class="rounded-full bg-muted-100 px-3 py-1.5 text-sm font-medium text-muted-500 border border-muted-300">
                            {{ __('video_chat.no_notes') }}
                        </div>
                    @endif
                </div>


                @if($meeting->notes)
                    <div class="rounded-3xl p-6 text-sm leading-relaxed text-muted-700 whitespace-pre-line">
                        {{ $meeting->notes }}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center rounded-3xl p-10 text-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted-100 text-muted-400">
                            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <div>
                            <h5 class="text-lg font-semibold text-main">{{ __('video_chat.notes_empty_title') }}</h5>
                            <p class="text-sm text-muted-500 max-w-md mx-auto">{{ __('video_chat.notes_empty_desc') }}</p>
                        </div>
                    </div>
                @endif
            </x-white-card-container>
        </div>

        {{-- RECORDING CONTENT --}}
        <div id="content-recording" class="tab-content hidden">
            <x-white-card-container class="p-0 flex flex-col overflow-hidden animate-fade-in-up">

                {{-- Recording Header --}}
                <div class="flex items-center justify-between border-b border-muted-200 px-6 py-4 bg-white sticky top-0 z-10">
                    <div>
                        <h4 class="text-lg font-semibold text-main">{{ __('video_chat.recording_title') }}</h4>
                        <p class="text-sm text-muted-500">{{ __('video_chat.recording_desc') }}</p>
                    </div>
                    @if($recordingUrl)
                        <a href="{{ $recordingUrl }}" download target="_blank"
                           class="flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-sm font-medium text-primary border border-primary/30 hover:bg-primary/20 transition-colors">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            {{ __('video_chat.recording_download_btn') }}
                        </a>
                    @endif
                </div>

                @if($recordingUrl)
                    <div class="p-6">
                        <video
                            controls
                            class="w-full rounded-2xl bg-black shadow-lg max-h-[600px]"
                            src="{{ $recordingUrl }}"
                            preload="metadata"
                        >
                            {{ __('video_chat.recording_unsupported') }}
                        </video>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center rounded-3xl p-10 text-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted-100 text-muted-400">
                            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <div>
                            <h5 class="text-lg font-semibold text-main">{{ __('video_chat.recording_empty_title') }}</h5>
                            <p class="text-sm text-muted-500 max-w-md mx-auto">{{ __('video_chat.recording_empty_desc') }}</p>
                        </div>
                    </div>
                @endif

            </x-white-card-container>
        </div>

    </div>
</div>

{{-- Vanilla JavaScript for Tabs & Dynamic Dividers --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        // Divider elements
        const divider1 = document.getElementById('divider-1');
        const divider2 = document.getElementById('divider-2');
        const divider3 = document.getElementById('divider-3');

        const activeClasses = ['rounded-xl', 'bg-primary/10', 'px-5', 'py-2.5', 'text-primary'];
        const inactiveClasses = ['px-5', 'py-2.5', 'hover:text-main'];

        // A divider is visible when neither of its adjacent tabs is active.
        // Summary | [div-1] | Transcript | [div-2] | Recording | [div-3] | Notes
        const dividerVisibility = {
            summary:    [false, true,  true],
            transcript: [false, false, true],
            recording:  [true,  false, false],
            notes:      [true,  true,  false],
        };

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const target = button.getAttribute('data-target');

                // 1. Reset all tabs to inactive state
                tabButtons.forEach(btn => {
                    btn.classList.remove(...activeClasses);
                    btn.classList.add(...inactiveClasses);
                });

                // 2. Set clicked tab to active state
                button.classList.remove(...inactiveClasses);
                button.classList.add(...activeClasses);

                // 3. Handle Dynamic Dividers
                const [d1, d2, d3] = dividerVisibility[target] ?? [false, false, false];
                [divider1, divider2, divider3].forEach((div, i) => {
                    const show = [d1, d2, d3][i];
                    div.classList.toggle('opacity-100', show);
                    div.classList.toggle('opacity-0', !show);
                });

                // 4. Hide all content panels
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                    if (content.id === 'content-summary') {
                        content.classList.remove('grid');
                    }
                });

                // 5. Show the targeted content panel
                const targetContent = document.getElementById(`content-${target}`);
                targetContent.classList.remove('hidden');

                if (target === 'summary') {
                    targetContent.classList.add('grid');
                }
            });
        });

        // Copy transcript button functionality
        const copyButtons = document.querySelectorAll('.copy-transcript-btn');
        copyButtons.forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const text = button.getAttribute('data-text');
                
                try {
                    await navigator.clipboard.writeText(text);
                    
                    // Visual feedback
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>{{ __('video_chat.transcript_copied_btn') }}';
                    button.classList.add('text-success');
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('text-success');
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy:', err);
                }
            });
        });
    });
</script>
@endsection