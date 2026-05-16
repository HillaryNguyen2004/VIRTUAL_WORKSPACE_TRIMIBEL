@section('title', 'video_chat')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script src="https://cdn.metered.ca/sdk/video/1.4.5/sdk.min.js"></script>

        <script>
            window.METERED_DOMAIN = "{{ $METERED_DOMAIN }}";
            window.MEETING_ID = "{{ $MEETING_ID }}";
            window.CURRENT_USER_NAME = @json($currentUserName ?? null);

            function copyMeetingId() {
                const btn = document.getElementById('copyBtn');
                const existingIcon = btn.querySelector('svg');
                if (!existingIcon) return;

                navigator.clipboard.writeText(window.MEETING_ID).then(() => {
                    const originalContent = btn.innerHTML;
                    btn.innerHTML = '<span class="text-accent text-xs font-bold px-1">Copied!</span>';
                    setTimeout(() => { btn.innerHTML = originalContent; }, 2000);
                });
            }

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

            async function saveMeetingNotes() {
                const button = document.getElementById('saveMeetingNotesBtn');
                const status = document.getElementById('notesStatus');
                const textarea = document.getElementById('meetingNotesTextarea');
                const notes = textarea.value.trim();

                button.disabled = true;
                button.textContent = 'Saving...';
                status.textContent = '';

                try {
                    const response = await fetch(`/meeting/${window.MEETING_ID}/notes`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({ notes }),
                    });

                    const payload = await response.json();
                    if (response.ok && payload.success) {
                        status.textContent = 'Saved';
                        setTimeout(() => { status.textContent = ''; }, 2500);
                    } else {
                        status.textContent = 'Failed to save';
                        console.error('Save notes failed', payload);
                    }
                } catch (error) {
                    status.textContent = 'Unable to save';
                    console.error('Save notes error', error);
                } finally {
                    button.disabled = false;
                    button.textContent = 'Save';
                }
            }

            // Tab navigation handler
            document.addEventListener('DOMContentLoaded', function() {
                const tabButtons = document.querySelectorAll('.tab-button');
                const tabContents = document.querySelectorAll('.tab-content');
                const divider1 = document.getElementById('divider-1');
                const divider2 = document.getElementById('divider-2');

                const activeClasses = ['rounded-xl', 'bg-secondary/20', 'text-secondary-light'];
                const inactiveClasses = ['hover:text-gray-300'];

                tabButtons.forEach((button) => {
                    button.addEventListener('click', function() {
                        const target = this.getAttribute('data-target');

                        // Reset all buttons to inactive state
                        tabButtons.forEach(btn => {
                            btn.classList.remove(...activeClasses);
                            btn.classList.add(...inactiveClasses);
                        });

                        // Set clicked button to active state
                        this.classList.remove(...inactiveClasses);
                        this.classList.add(...activeClasses);

                        // Handle Dynamic Dividers
                        if (divider1) {
                            divider1.classList.remove('opacity-100');
                            divider1.classList.add('opacity-0');
                        }
                        if (divider2) {
                            divider2.classList.remove('opacity-100');
                            divider2.classList.add('opacity-0');
                        }

                        if (target === 'participants') {
                            if (divider2) {
                                divider2.classList.remove('opacity-0');
                                divider2.classList.add('opacity-100');
                            }
                        } else if (target === 'notes') {
                            if (divider1) {
                                divider1.classList.remove('opacity-0');
                                divider1.classList.add('opacity-100');
                            }
                        }

                        // Hide all content tabs
                        tabContents.forEach(content => content.classList.add('hidden'));

                        // Show selected content
                        const selectedContent = document.getElementById(`content-${target}`);
                        if(selectedContent) {
                            selectedContent.classList.remove('hidden');
                            // Ensure flex display is maintained when unhiding
                            selectedContent.classList.add('flex'); 
                        }
                    });
                });
            });
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

    </head>
    <body class="antialiased bg-gray-900 text-white">
        
        <div id='meetingView' class="flex flex-col md:flex-row fixed inset-0 p-2 md:p-4 gap-2 md:gap-6 overflow-hidden">

            {{-- Main Speaker Area --}}
            <div id="activeSpeakerContainer" class="flex-none h-[40vh] md:h-auto md:flex-1 rounded-lg md:rounded-2xl bg-black border border-gray-700 shadow-2xl relative overflow-hidden group flex flex-col justify-center min-h-0">
                
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
                
                <div id="caption-box" class="absolute bottom-16 md:bottom-20 left-1/2 -translate-x-1/2 
                bg-white/10 backdrop-blur-md border border-white/20 
                px-3 md:px-6 py-2 md:py-3 rounded-lg md:rounded-2xl shadow-xl text-white text-xs md:text-lg font-medium 
                transition-all duration-300 opacity-0 max-w-xs md:max-w-md z-20">
                </div>
                
                {{-- Meeting Controls --}}
                <div class="absolute bottom-2 md:bottom-6 left-1/2 -translate-x-1/2 z-30 transition-all duration-300 opacity-100 md:opacity-0 md:group-hover:opacity-100 focus-within:opacity-100 w-[95%] md:w-auto">
                    <div class="flex items-center justify-center gap-2 md:gap-4 bg-gray-900/80 md:bg-gray-900/60 backdrop-blur-md border border-white/10 shadow-xl rounded-xl md:rounded-2xl p-2 md:p-3 overflow-x-auto hide-scrollbar">
                        <button id='toggleMicrophone' class="flex-shrink-0 w-10 md:w-12 h-10 md:h-12 rounded-lg md:rounded-xl flex items-center justify-center transition-all duration-200 shadow-lg bg-gray-700 text-white hover:bg-gray-600 border border-white/5">
                            <svg class="w-4 md:w-5 h-4 md:h-5 mic-on" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" >
                                <path strokeLinecap="round" strokeLinejoin="round"  d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                            <svg class="w-4 md:w-5 h-4 md:h-5 mic-off hidden text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                            </svg>
                        </button>
                        <button id='toggleCamera' class="flex-shrink-0 w-10 md:w-12 h-10 md:h-12 rounded-lg md:rounded-xl flex items-center justify-center transition-all duration-200 shadow-lg bg-gray-700 text-white hover:bg-gray-600 border border-white/5">
                            <svg class="w-4 md:w-5 h-4 md:h-5 cam-on" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>           
                            <svg class="w-4 md:w-5 h-4 md:h-5 cam-off hidden text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9A2.25 2.25 0 0 0 4.5 18.75Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                            </svg>
                        </button>
                        <button id='toggleScreen' class="flex-shrink-0 w-10 md:w-12 h-10 md:h-12 rounded-lg md:rounded-xl flex items-center justify-center transition-all duration-200 shadow-lg bg-gray-700 text-white hover:bg-gray-600 border border-white/5">
                            <svg class="w-4 md:w-5 h-4 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </button>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button id='toggleRecording' class="w-10 md:w-12 h-10 md:h-12 rounded-lg md:rounded-xl flex items-center justify-center transition-all duration-200 shadow-lg bg-gray-700 text-white hover:bg-gray-600 border border-white/5" title="Record meeting">
                                <span class="inline-flex items-center justify-center w-2 md:w-3 h-2 md:h-3 rounded-full bg-red-500 shadow"></span>
                            </button>
                            <span id="recordingTimer" class="text-[10px] md:text-xs font-semibold text-red-400 hidden">00:00</span>
                        </div>
                        <div class="w-px h-6 md:h-8 bg-white/10 mx-1 hidden md:block"></div>
                        <button id='leaveMeeting' class="flex-shrink-0 h-10 md:h-12 px-4 md:px-6 rounded-lg md:rounded-xl flex items-center justify-center transition-all duration-200 shadow-lg bg-red-600 hover:bg-red-700 text-white gap-2 font-medium text-xs md:text-sm">
                            <svg class="w-4 md:w-5 h-4 md:h-5 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Leave
                        </button>
                    </div>
                </div>
                
                <div id="activeSpeakerUsername" class="hidden absolute h-8 md:h-10 px-2 md:px-4 bottom-14 md:bottom-4 left-2 md:left-4 bg-gray-900/60 backdrop-blur-md border border-white/10 rounded-lg md:rounded-xl text-white text-xs md:text-sm font-medium flex items-center shadow-lg z-20">
                </div>
            </div>  

            {{-- Sidebar Area (Scrollable flex container) --}}
            <div id="remoteParticipantContainer" class="flex flex-col gap-4 md:gap-6 w-full md:w-80 flex-1 md:flex-none md:h-full overflow-y-auto overflow-x-hidden min-h-0 pb-12 md:pb-0 pr-1">
                
                {{-- Meeting ID Panel (Don't shrink) --}}
                <div class="flex-none bg-gray-800 rounded-2xl p-4 border border-gray-700 shadow-lg relative">
                    <div class="relative z-10">
                        <label class="block text-xs text-gray-400 font-bold uppercase tracking-wider mb-3">
                            Meeting ID
                        </label>
                        <div class="flex items-center justify-between gap-2 p-1 md:p-2 bg-gray-900 rounded-xl border border-gray-700">
                            <span class="pl-1 text-gray-100 font-mono font-bold text-sm md:text-lg truncate tracking-tight" title="{{ $MEETING_ID }}">
                                {{ $MEETING_ID }}
                            </span>
                            <button id="copyBtn" onclick="copyMeetingId()" class="flex text-gray-400 hover:text-accent transition-colors p-2 rounded-lg hover:bg-gray-800 shrink-0" title="Copy Meeting ID">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Self Participant Video (Don't shrink) --}}
                <div id="localParticipantContainer" class="flex-none w-full aspect-[4/3] sm:aspect-video md:aspect-[4/3] rounded-lg md:rounded-2xl bg-black border border-gray-700 shadow-lg relative group overflow-hidden">
                    
                    <div class="absolute inset-0 z-0 flex items-center justify-center bg-gray-800">
                        <div class="p-3 rounded-full bg-gray-700">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9A2.25 2.25 0 0 0 4.5 18.75Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                            </svg>
                        </div>
                    </div>

                    <video id="localVideoTag" src="" autoplay class="relative z-10 object-cover w-full h-full -scale-x-100 opacity-100 transition-opacity"></video>
                    
                    <div id="localUsername" class="absolute h-8 px-3 bottom-2 left-2 bg-gray-900/60 backdrop-blur-md border border-white/10 rounded-lg text-white text-xs font-bold flex items-center shadow-md z-20">
                        You
                    </div>
                </div>

                {{-- Tabs Navigation (Don't shrink) --}}
                <div class="flex-none flex w-full items-center text-xs sm:text-sm font-medium text-gray-400 bg-gray-800 p-1 border border-gray-700 rounded-2xl">
                    
                    {{-- Participants Tab --}}
                    <button type="button" data-target="participants" class="tab-button flex-1 flex items-center justify-center gap-1 sm:gap-2 transition-all duration-300 rounded-xl bg-secondary/20 px-3 py-2 text-secondary-light">
                        <svg class="h-4 sm:h-5 w-4 sm:w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/>
                        </svg>
                        <span class="hidden sm:inline">People</span>
                    </button>

                    {{-- Divider 1 --}}
                    <div id="divider-1" class="h-4 w-px bg-gray-500 opacity-0 transition-all duration-300"></div>

                    {{-- Chat Tab --}}
                    <button type="button" data-target="chat" class="tab-button flex-1 flex items-center justify-center gap-1 sm:gap-2 transition-all duration-300 px-1 py-2 hover:text-gray-300">
                        <svg class="h-4 sm:h-5 w-4 sm:w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                        Chat
                    </button>

                    {{-- Divider 2 --}}
                    <div id="divider-2" class="h-4 w-px bg-gray-500 opacity-100 transition-all duration-300"></div>

                    {{-- Notes Tab --}}
                    <button type="button" data-target="notes" class="tab-button flex-1 flex items-center justify-center gap-1 sm:gap-2 transition-all duration-300 px-1 py-2 hover:text-gray-300">
                        <svg class="h-4 sm:h-5 w-4 sm:w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Notes
                    </button>
                </div>

                {{-- Tab Contents Container (The flex-1 min-h-0 is crucial here) --}}
                <div class="flex flex-col flex-1 min-h-0 min-h-[300px] md:min-h-[250px] gap-3 md:gap-6">
                    
                    {{-- Participants Content --}}
                    <div id="content-participants" class="tab-content flex-1 min-h-0 bg-gray-800 rounded-2xl p-4 border border-gray-700 shadow-lg relative overflow-hidden flex flex-col">
                        <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-blue-500/10 rounded-full blur-xl opacity-50 pointer-events-none"></div>
                        <div class="relative z-10 flex flex-col h-full min-h-0">
                            <h4 class="flex-none text-sm font-semibold text-gray-300 mb-3 uppercase tracking-wide">
                                Participants ({{ $meetingAttendees->count() }})
                            </h4>
                            <div class="space-y-3 flex-1 overflow-y-auto pr-1 min-h-0 custom-scrollbar">
                                @forelse($meetingAttendees as $attendee)
                                    <div class="flex items-center gap-3 bg-gray-900/60 border border-gray-700 rounded-xl px-3 py-2 hover:border-gray-600 transition-colors shrink-0">
                                        <x-user-avatar :user="$attendee" size="h-8 w-8" ringClass="ring-2 ring-gray-800" />
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-gray-100 truncate">{{ $attendee->name }}</div>
                                            @if($attendee->joined_at)
                                                <div class="text-xs text-gray-500">{{ $attendee->joined_at->format('h:i A') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-sm text-gray-500">No participants yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Chat Content --}}
                    <div id="content-chat" class="tab-content hidden flex-1 min-h-0 bg-gray-800 rounded-2xl p-4 border border-gray-700 shadow-lg relative overflow-hidden flex-col">
                        <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-emerald-500/10 rounded-full blur-xl opacity-50 pointer-events-none"></div>
                        <div class="relative z-10 flex flex-col h-full min-h-0">
                            <h4 class="flex-none text-sm font-semibold text-gray-300 mb-3 uppercase tracking-wide">
                                In-Meeting Chat
                            </h4>
                            <div id="chatPanel" class="flex-1 overflow-y-auto space-y-3 pr-2 mb-4 min-h-0 custom-scrollbar">
                                <div class="text-xs text-gray-500">Chat messages will disappear after the meeting ends.</div>
                            </div>
                            <div class="flex-none flex items-center gap-2 mt-auto shrink-0">
                                <input id="meetingChatInput" type="text" placeholder="Type a message..." class="flex-1 bg-gray-900 border border-gray-700 rounded-xl px-3 py-2 text-sm text-gray-100 placeholder-gray-500 outline-none focus:border-success focus:ring-1 focus:ring-success/40 transition-all min-w-0" />
                                <button id="meetingChatSend" class="px-4 py-2 rounded-xl bg-success hover:bg-success-hover text-white text-sm font-semibold transition-all shrink-0">Send</button>
                            </div>
                        </div>
                    </div>

                    {{-- Notes Content --}}
                    <div id="content-notes" class="tab-content hidden flex-1 min-h-0 bg-gray-800 rounded-2xl p-4 border border-gray-700 shadow-lg relative overflow-hidden flex-col">
                        <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-yellow-500/10 rounded-full blur-xl opacity-50 pointer-events-none"></div>
                        <div class="relative z-10 flex flex-col h-full min-h-0">
                            <h4 class="flex-none text-sm font-semibold text-gray-300 mb-3 uppercase tracking-wide">
                                Notes
                            </h4>
                            <div class="flex flex-col flex-1 gap-4 min-h-0">
                                <textarea id="meetingNotesTextarea" placeholder="Write your personal notes here..." class="flex-1 min-h-0 w-full resize-none rounded-2xl bg-gray-900 border border-gray-700 text-sm text-gray-100 p-4 outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-400/40 transition-all">{{ $meetingNotes }}</textarea>

                                <div class="flex-none flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shrink-0">
                                    <div class="text-[10px] sm:text-xs text-gray-400 leading-relaxed max-w-md hidden sm:block">
                                        Notes are saved to your meeting history and will appear on the meeting details page.
                                    </div>
                                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end shrink-0">
                                        <span id="notesStatus" class="text-xs text-emerald-400"></span>
                                        <button id="saveMeetingNotesBtn" onclick="saveMeetingNotes()" class="inline-flex items-center justify-center rounded-xl bg-yellow-500 px-6 py-2 text-sm font-semibold text-black transition hover:bg-yellow-400 disabled:opacity-60 w-full sm:w-auto" type="button">
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="leaveMeetingView" class="hidden min-h-screen flex items-center justify-center bg-gray-900 p-4">
             <div class="bg-gray-800 rounded-xl md:rounded-2xl p-6 md:p-8 border border-gray-700 shadow-2xl text-center max-w-md w-full mx-4">
                <div class="inline-flex items-center justify-center w-12 md:w-16 h-12 md:h-16 rounded-full bg-red-500/10 text-red-500 mb-4 md:mb-6">
                    <svg class="w-6 md:w-8 h-6 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </div>
                <h2 class="text-xl md:text-2xl font-bold text-white mb-3 md:mb-4">
                    {{ __('video_chat.left_meeting') }}
                </h2>
                <a href="/dashboard" class="inline-flex items-center justify-center px-4 md:px-6 py-2 md:py-3 rounded-lg md:rounded-xl bg-gray-700 text-gray-300 font-medium hover:bg-gray-600 hover:text-white transition-colors w-full text-sm md:text-base">
                    {{ __('video_chat.return_to_dashboard') }}
                </a>
            </div>
        </div>
    </body>
</html>