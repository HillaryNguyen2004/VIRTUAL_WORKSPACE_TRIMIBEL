@extends('layout_dashboard')
@section('title', 'video_chat')
@section('content')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>

        <script src="https://cdn.metered.ca/sdk/video/1.4.5/sdk.min.js"></script>

        <script>
            window.METERED_DOMAIN = "{{ $METERED_DOMAIN }}";
            window.MEETING_ID = "{{ $MEETING_ID }}";
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

    </head>
    <body class="antialiased">
        
        {{-- Main Container matched to dashboard width --}}
        <div class="w-full max-w-7xl mx-auto text-main animate-fade-in-up">

            <div id="waitingArea" class="w-full">
                
                {{-- Header Section --}}
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center w-full mb-6">
                    <a href="/meeting" class="group flex items-center justify-center p-2 rounded-xl text-muted-400 hover:text-primary hover:bg-primary/10 transition-colors mr-2">
                        <svg class="w-6 h-6 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </a>
                    <div>
                        <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('video_chat.lobby_title') }}</h2>
                    </div>
                </div>
    
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 w-full">
                    
                    {{-- Video Preview Area --}}
                    <div class="lg:col-span-7 xl:col-span-8">
                        <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden bg-black shadow-lg shadow-main/5 border border-muted-200 group">
                            <video id='waitingAreaLocalVideo' class="w-full h-full object-cover -scale-x-100" autoplay muted></video>

                            <div class="absolute bottom-0 w-full p-6 flex justify-center gap-6 bg-gradient-to-t from-black/80 to-transparent pt-12 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <button id='waitingAreaToggleMicrophone' class="bg-white/10 backdrop-blur-md hover:bg-primary hover:text-white text-white border border-white/20 w-14 h-14 rounded-full flex items-center justify-center transition-all shadow-lg active:scale-95">
                                    <svg class="w-6 h-6 mic-on" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path strokeLinecap="round" strokeLinejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                    </svg>
                                    
                                    <svg class="w-6 h-6 mic-off hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                    </svg>
                                </button>

                                <button id='waitingAreaToggleCamera' class="bg-white/10 backdrop-blur-md hover:bg-primary hover:text-white text-white border border-white/20 w-14 h-14 rounded-full flex items-center justify-center transition-all shadow-lg active:scale-95">
                                    <svg class="w-6 h-6 cam-on" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>           

                                    <svg class="w-6 h-6 cam-off hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9A2.25 2.25 0 0 0 4.5 18.75Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Settings Panel --}}
                    <div class="lg:col-span-5 xl:col-span-4">
                        <div class= "h-full flex flex-col justify-center">
                            
                            <div class="text-center mb-6">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary/10 text-primary mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </div>
                                <h3 class="text-xl font-bold text-main">{{ __('video_chat.ready_to_join') }}</h3>
                            </div>
                            
                            <div class="flex flex-col space-y-5">
                                
                                <div>
                                    <label for="username" class="block mb-1.5 text-sm font-medium text-muted-600">{{ __('video_chat.name_label') }}</label>
                                    <input id="username" type="text" placeholder="{{ __('video_chat.name_placeholder') }}" 
                                        class="block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all" />
                                </div>

                                <div>
                                    <label for="cameraSelectBox" class="block mb-1.5 text-sm font-medium text-muted-600">{{ __('video_chat.camera_label') }}</label>
                                    <div class="relative w-full">
                                        <select id='cameraSelectBox' 
                                            class="block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent appearance-none pr-10 transition-all">
                                            </select>
                                        
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-muted-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
            
                                <div>
                                    <label for="microphoneSelectBox" class="block mb-1.5 text-sm font-medium text-muted-600">{{ __('video_chat.microphone_label') }}</label>
                                    <div class="relative w-full">
                                        <select id='microphoneSelectBox' 
                                            class="block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent appearance-none pr-10 transition-all">
                                            </select>
                                        
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-muted-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <button id='joinMeetingBtn' class="group flex items-center justify-center gap-2 w-full rounded-xl bg-primary px-6 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                                        {{ __('video_chat.join_button') }}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 

        <div id="leaveMeetingView" class="hidden min-h-[400px] flex items-center justify-center">
            <div class="bg-white rounded-2xl p-8 border border-muted-200 shadow-lg shadow-main/5 text-center max-w-md w-full mx-4">
                <div class="inline-block p-4 rounded-full bg-muted-100 text-muted-500 mb-4">
                     <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-main mb-2">
                    {{ __('video_chat.left_meeting') }}
                </h1>
                <p class="text-muted-500 text-sm mb-6">You have successfully left the meeting lobby.</p>
                <a href="/dashboard" class="text-primary hover:text-primary-hover font-medium text-sm transition-colors">Return to Dashboard</a>
            </div>
        </div>
    </body>
</html>
@endsection