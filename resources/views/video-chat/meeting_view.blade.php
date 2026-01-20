@section('title', 'video_chat')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script src="https://cdn.metered.ca/sdk/video/1.4.5/sdk.min.js"></script>

        <script>
            window.METERED_DOMAIN = "{{ $METERED_DOMAIN }}";
            window.MEETING_ID = "{{ $MEETING_ID }}";

            function copyMeetingId() {
                const btn = document.getElementById('copyBtn');
                const existingIcon = btn.querySelector('svg');
                if (!existingIcon) return;

                navigator.clipboard.writeText(window.MEETING_ID).then(() => {
                    const originalContent = btn.innerHTML;
                    btn.innerHTML = '<span class="text-cyan-400 text-xs font-bold px-1">Copied!</span>';
                    setTimeout(() => { btn.innerHTML = originalContent; }, 2000);
                });
            }

            // Example helper to toggle video visibility (You can use this in your app.js)
            function toggleVideoVisibility(videoId, isVisible) {
                const videoEl = document.getElementById(videoId);
                if (videoEl) {
                    if (isVisible) {
                        videoEl.classList.remove('hidden');
                    } else {
                        videoEl.classList.add('hidden');
                    }
                }
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

    </head>
    <body class="antialiased bg-gray-900 text-white">
        
        <div id='meetingView' class="flex fixed inset-0 p-4 gap-6">

            <div id="activeSpeakerContainer" class="flex-1 rounded-2xl bg-black border border-gray-700 shadow-2xl relative overflow-hidden group">
                
                <div class="absolute inset-0 z-0 flex items-center justify-center bg-gray-800">
                    <div class="flex flex-col items-center gap-3 opacity-50">
                        <div class="p-4 rounded-full bg-gray-700">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9A2.25 2.25 0 0 0 4.5 18.75Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3l18 18" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-400 tracking-wide">CAMERA OFF</span>
                    </div>
                </div>

                <video id="activeSpeakerVideo" src="" autoplay class="relative z-10 object-contain w-full h-full"></video>
                
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 transition-all duration-300 opacity-0 group-hover:opacity-100 focus-within:opacity-100">
                    <div class="flex items-center justify-center gap-4 bg-gray-900/60 backdrop-blur-md border border-white/10 shadow-xl rounded-2xl p-3">
                        <button id='toggleMicrophone' class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-200 shadow-lg bg-gray-700 text-white hover:bg-gray-600 border border-white/5">
                            <svg class="w-5 h-5 mic-on" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" >
                                <path strokeLinecap="round" strokeLinejoin="round"  d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                            <svg class="w-5 h-5 mic-off hidden text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                            </svg>
                        </button>
                        <button id='toggleCamera' class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-200 shadow-lg bg-gray-700 text-white hover:bg-gray-600 border border-white/5">
                            <svg class="w-5 h-5 cam-on" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>           
                            <svg class="w-5 h-5 cam-off hidden text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9A2.25 2.25 0 0 0 4.5 18.75Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                            </svg>
                        </button>
                        <button id='toggleScreen' class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-200 shadow-lg bg-gray-700 text-white hover:bg-gray-600 border border-white/5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </button>
                        <div class="w-px h-8 bg-white/10 mx-1"></div>
                        <button id='leaveMeeting' class="h-12 px-6 rounded-xl flex items-center justify-center transition-all duration-200 shadow-lg bg-red-600 hover:bg-red-700 text-white gap-2 font-medium text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Leave
                        </button>
                    </div>
                </div>
                
                <div id="activeSpeakerUsername" class="hidden absolute h-10 px-4 bottom-4 left-4 bg-gray-900/60 backdrop-blur-md border border-white/10 rounded-xl text-white text-sm font-medium flex items-center shadow-lg z-20">
                </div>
            </div>  

            <div id="remoteParticipantContainer" class="flex flex-col gap-6 w-80 flex-shrink-0 overflow-y-auto">
                
                <div class="bg-gray-800 rounded-2xl p-5 border border-gray-700 shadow-lg relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-cyan-500/10 rounded-full blur-xl opacity-50"></div>
                    <div class="relative z-10">
                        <label class="block text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">
                            Meeting ID
                        </label>
                        <div class="flex items-center justify-between gap-2 p-3 bg-gray-900 rounded-xl border border-gray-700">
                            <span class="text-gray-100 font-mono font-bold text-lg truncate tracking-tight" title="{{ $MEETING_ID }}">
                                {{ $MEETING_ID }}
                            </span>
                            <button id="copyBtn" onclick="copyMeetingId()" class="text-gray-400 hover:text-cyan-400 transition-colors p-2 rounded-lg hover:bg-gray-800" title="Copy Meeting ID">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-3 leading-relaxed">
                            Share this ID to invite others.
                        </p>
                    </div>
                </div>

                <div id="localParticipantContainer" class="w-full aspect-video rounded-2xl bg-black border border-gray-700 shadow-lg relative overflow-hidden group">
                    
                    <div class="absolute inset-0 z-0 flex items-center justify-center bg-gray-800">
                        <div class="p-3 rounded-full bg-gray-700">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9A2.25 2.25 0 0 0 4.5 18.75Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                            </svg>
                        </div>
                    </div>

                    <video id="localVideoTag" src="" autoplay class="relative z-10 object-cover w-full h-full -scale-x-100 opacity-90 group-hover:opacity-100 transition-opacity"></video>
                    
                    <div id="localUsername" class="absolute h-8 px-3 bottom-2 left-2 bg-gray-900/60 backdrop-blur-md border border-white/10 rounded-lg text-white text-xs font-bold flex items-center shadow-md z-20">
                        You
                    </div>
                </div>

                <div class="bg-gray-800 rounded-2xl p-5 border border-gray-700 shadow-lg relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-purple-500/10 rounded-full blur-xl opacity-50"></div>
                    <div class="relative z-10">
                        <label class="block text-xs text-gray-400 font-bold uppercase tracking-wider mb-3">
                            Participants ({{ $meetingAttendees->count() }})
                        </label>
                        <div class="space-y-3">
                            @forelse($meetingAttendees as $attendee)
                                <div class="flex items-center gap-3 bg-gray-900/60 border border-gray-700 rounded-xl px-3 py-2">
                                    <img class="h-8 w-8 rounded-full ring-2 ring-gray-800 object-cover" 
                                         src="{{ $attendee->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($attendee->name) }}" 
                                         alt="{{ $attendee->name }}">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-100 truncate">{{ $attendee->name }}</div>
                                        @if($attendee->joined_at)
                                            <div class="text-xs text-gray-500">Joined {{ $attendee->joined_at->format('h:i A') }}</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-gray-500">No participants yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-2xl p-5 border border-gray-700 shadow-lg relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-emerald-500/10 rounded-full blur-xl opacity-50"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-xs text-gray-400 font-bold uppercase tracking-wider">
                                Chat (ephemeral)
                            </label>
                            <span id="meetingChatStatus" class="text-[10px] text-gray-500">Active</span>
                        </div>

                        <div id="meetingChatMessages" class="h-56 overflow-y-auto space-y-3 pr-1">
                            <div class="text-xs text-gray-500">Chat messages will disappear after the meeting ends.</div>
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            <input id="meetingChatInput" type="text" placeholder="Type a message..." class="flex-1 bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/40" />
                            <button id="meetingChatSend" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Send</button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <div id="leaveMeetingView" class="hidden min-h-screen flex items-center justify-center bg-gray-900">
             <div class="bg-gray-800 rounded-2xl p-8 border border-gray-700 shadow-2xl text-center max-w-md w-full mx-4">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-500/10 text-red-500 mb-6">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-4">
                    {{ __('video_chat.left_meeting') }}
                </h2>
                <a href="/dashboard" class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-gray-700 text-gray-300 font-medium hover:bg-gray-600 hover:text-white transition-colors w-full">
                    {{ __('video_chat.return_to_dashboard') }}
                </a>
            </div>
        </div>
    </body>
</html>