<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ Auth::id() }}">

    <title>@yield('title') - {{ __('app.title') }}</title>

    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <!-- <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet"> -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/dashboard.css'])
    <!-- Bootstrap core JavaScript-->
    @vite(['public/vendor/jquery/jquery.min.js'])
    @vite(['public/vendor/bootstrap/js/bootstrap.bundle.min.js'])
    @vite(['public/vendor/jquery-easing/jquery.easing.min.js'])
    @vite(['public/js/sb-admin-2.min.js'])

    <!-- Dashboard layout -->
    @vite([
        'resources/js/dashboard_layout/switch_lang.js',
        'resources/js/dashboard_layout/dropdown_profile.js',
        'resources/js/dashboard_layout/toggle_sidebar.js',
        'resources/js/dashboard_layout/dropdown_notification.js',
        'resources/js/dashboard_layout/scroll_to_top.js',
        'resources/js/chat_bot.js'
    ])

    <!-- User dashboard -->
    @vite(['resources/js/user_dashboard/check_in_out_api.js'])
</head>

@stack('scripts')

{{-- Added 'bg-canvas' and 'text-main' to apply global theme defaults --}}
<body id="page-top" class="flex flex-row bg-canvas/50 text-main font-nunito antialiased">
    
    <div class="flex flex-row fixed xl:static h-screen w-screen xl:w-fit z-[-1] xl:z-40" id="rounded-sidebar">
        <!--  Updated Sidebar: bg-white, border-muted-200 -->
        <nav class="flex flex-col gap-[79px] -translate-x-full xl:translate-x-0 bg-white w-fit h-screen border-r border-muted-200 p-5 z-40 transition duration-300"
            id="sidebar">
            
            <!-- Logo Area -->
            <div class="w-full text-center md:text-left font-bold text-2xl text-primary tracking-tight">
                Logo
            </div>

            <ul class="flex flex-col gap-2 justify-start sm:w-[220px]">
                <li>
                    @php
                        use Illuminate\Support\Facades\Route;

                        $dashRoute = 'user.dashboard';
                        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
                            $dashRoute = 'admin.dashboard';
                        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
                            $dashRoute = 'staff.dashboard';
                        }
                    @endphp
                    <!--Note: Assuming x-nav-link handles active classes. 
                         Ideally, active state should allow 'bg-primary/5 text-primary'-->
                    <x-nav-link href="{{ route($dashRoute) }}" :active="request()->routeIs(['*.dashboard'])"
                        class="flex items-center gap-4 px-4 py-3 hover:bg-muted-50 rounded-xl cursor-pointer transition-colors group {{ request()->routeIs(['*.dashboard']) ? 'text-primary bg-primary/5' : 'text-muted-500' }}">
                        <svg viewBox="0 0 128 128" class="h-5 w-5 transition-colors {{ request()->routeIs(['*.dashboard']) ? 'text-primary' : 'text-muted-400 group-hover:text-primary' }}" fill="none" stroke="currentColor" stroke-width="12"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="8" y="8" width="112" height="112" rx="14" />
                            <path d="M72 20V108M72 64H108" />
                        </svg>
                        <span class="hidden md:inline font-medium">{{ __('app.dashboard') }}</span>
                    </x-nav-link>
                </li>
                <li>
                    <x-nav-link href="{{ route('chat.index') }}" :active="request()->routeIs('chat.index')"
                        class="flex items-center gap-4 px-4 py-3 hover:bg-muted-50 rounded-xl cursor-pointer transition-colors group {{ request()->routeIs('chat.index') ? 'text-primary bg-primary/5' : 'text-muted-500' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 transition-colors {{ request()->routeIs('chat.index') ? 'fill-primary' : 'fill-muted-400 group-hover:fill-primary' }}">
                            <path
                                d="M115.9 448.9C83.3 408.6 64 358.4 64 304C64 171.5 178.6 64 320 64C461.4 64 576 171.5 576 304C576 436.5 461.4 544 320 544C283.5 544 248.8 536.8 217.4 524L101 573.9C97.3 575.5 93.5 576 89.5 576C75.4 576 64 564.6 64 550.5C64 546.2 65.1 542 67.1 538.3L115.9 448.9zM153.2 418.7C165.4 433.8 167.3 454.8 158 471.9L140 505L198.5 479.9C210.3 474.8 223.7 474.7 235.6 479.6C261.3 490.1 289.8 496 319.9 496C437.7 496 527.9 407.2 527.9 304C527.9 200.8 437.8 112 320 112C202.2 112 112 200.8 112 304C112 346.8 127.1 386.4 153.2 418.7z" />
                        </svg>
                        <span class="hidden md:inline font-medium">{{ __('app.chat_box') }}</span>
                    </x-nav-link>
                </li>
                <li>
                    <x-nav-link href="{{ route('meeting') }}" :active="request()->routeIs('meeting*')"
                        class="flex items-center gap-4 px-4 py-3 hover:bg-muted-50 rounded-xl cursor-pointer transition-colors group {{ request()->routeIs('meeting*') ? 'text-primary bg-primary/5' : 'text-muted-500' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 transition-colors {{ request()->routeIs('meeting*') ? 'text-primary' : 'text-muted-400 group-hover:text-primary' }}" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14v-4zM5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span class="hidden md:inline font-medium">{{ __('app.video_chat') }}</span>
                    </x-nav-link>
                </li>
                <li>
                    <x-nav-link href="{{ route('team-progress') }}" :active="request()->routeIs('team-progress')"
                        class="flex items-center gap-4 px-4 py-3 hover:bg-muted-50 rounded-xl cursor-pointer transition-colors group {{ request()->routeIs('team-progress') ? 'text-primary bg-primary/5' : 'text-muted-500' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-colors {{ request()->routeIs('team-progress') ? 'text-primary' : 'text-muted-400 group-hover:text-primary' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span class="hidden md:inline font-medium">Team Progress</span>
                    </x-nav-link>
                </li>
            </ul>
        </nav>
        <div class="hidden fixed bg-main/20 h-screen w-screen z-30" id="sidebar-bg-addition"></div>
    </div>

    <div class="flex flex-col w-full h-screen overflow-y-scroll">
        {{-- Updated Navbar: bg-white, border-b, shadow-sm, removed heavy shadow --}}
        <nav
            class="flex justify-between xl:justify-end pl-10 pr-10 xl:pr-[64px] py-3 bg-white border-muted-200 shadow-[0_4px_40px_0_rgba(206,197,242,0.2)] sticky top-0 z-20">
            <button class="flex items-center xl:hidden hover:bg-muted-50 rounded-full p-2 text-primary" id="sidebar-menu-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-current">
                    <path
                        d="M96 160C96 142.3 110.3 128 128 128L512 128C529.7 128 544 142.3 544 160C544 177.7 529.7 192 512 192L128 192C110.3 192 96 177.7 96 160zM96 320C96 302.3 110.3 288 128 288L512 288C529.7 288 544 302.3 544 320C544 337.7 529.7 352 512 352L128 352C110.3 352 96 337.7 96 320zM544 480C544 497.7 529.7 512 512 512L128 512C110.3 512 96 497.7 96 480C96 462.3 110.3 448 128 448L512 448C529.7 448 544 462.3 544 480z" />
                </svg>
            </button>
            <ul class="flex flex-row gap-5 items-center">
                <li>
                    @php $currentLocale = app()->getLocale(); @endphp
                    <div class="relative" id="langMenu">
                        <button id="langButton" type="button" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-md font-medium text-muted-600
                                hover:bg-muted-50 focus:outline-none transition-colors" aria-haspopup="menu" aria-expanded="false">
                            @if ($currentLocale === 'en')
                                <div class="flex items-center gap-1">
                                    <div>🇺🇸</div>
                                    </div>
                            @else
                                <div class="flex items-center gap-1">
                                    <div>🇻🇳</div>
                                    </div>
                            @endif

                            <svg class="h-4 w-4 text-muted-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" />
                            </svg>
                        </button>

                        <div id="langList" class="absolute left-1/2 -translate-x-1/2 z-20 mt-2 w-36 origin-top rounded-xl bg-white shadow-lg ring-1 ring-muted-200 border border-muted-200
                                hidden" role="menu" aria-labelledby="langButton">
                            <a href="{{ route('lang.switch', 'en') }}"
                                class="block px-4 py-2 text-sm text-muted-700 hover:bg-muted-50 rounded-t-xl"
                                role="menuitem">🇺🇸 {{ __('app.lang_english') }}</a>
                            <a href="{{ route('lang.switch', 'vi') }}"
                                class="block px-4 py-2 text-sm text-muted-700 hover:bg-muted-50 rounded-b-xl"
                                role="menuitem">🇻🇳 {{ __('app.lang_vietnamese') }}</a>
                        </div>
                    </div>
                </li>
                <li>
                    <div class="relative" id="notificationMenu">
                        <button id="notificationBtn" class="flex items-center px-1 py-1 rounded-full hover:bg-muted-50 transition-colors"
                            type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="notificationPanel">
                            {{-- Changed fill color to muted-400 --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                class="w-[26px] h-[26px] fill-muted-400 hover:fill-primary transition-colors">
                                <path
                                    d="M320 64C302.3 64 288 78.3 288 96L288 99.2C215 114 160 178.6 160 256L160 277.7C160 325.8 143.6 372.5 113.6 410.1L103.8 422.3C98.7 428.6 96 436.4 96 444.5C96 464.1 111.9 480 131.5 480L508.4 480C528 480 543.9 464.1 543.9 444.5C543.9 436.4 541.2 428.6 536.1 422.3L526.3 410.1C496.4 372.5 480 325.8 480 277.7L480 256C480 178.6 425 114 352 99.2L352 96C352 78.3 337.7 64 320 64zM258 528C265.1 555.6 290.2 576 320 576C349.8 576 374.9 555.6 382 528L258 528z" />
                            </svg>
                            {{-- Changed Alert badge to danger color --}}
                            <span id="notificationBadge"
                                class="flex justify-center items-center absolute -top-0.5 -right-0.5 w-5 h-5 rounded-full bg-danger text-[9px] font-semibold text-white">
                            </span>
                        </button>

                        <div id="notificationPanel" class="absolute left-1/2 -translate-x-1/2 z-20 mt-2 w-60 md:w-64 lg:w-96 origin-top rounded-xl bg-white shadow-lg ring-1 ring-muted-200 border border-muted-200
                                hidden" role="menu" aria-labelledby="notificationBtn">
                            <div class="flex flex-wrap items-center justify-between gap-2 py-3 px-4 w-full">
                                <h6 class="font-medium text-main">Notifications</h6>
                                <button id="markAllRead" class="text-sm text-primary hover:text-primary-hover font-medium">Mark all as read</button>
                            </div>

                            <div class="border-t border-muted-200"></div>

                            <div id="alertsList" class="h-fit max-h-80 overflow-scroll my-2">
                                {{-- notifications will be injected here --}}
                            </div>

                            <p id="emptyState" class="hidden px-4 py-6 text-center text-sm text-muted-400">
                                No new notifications
                            </p>

                            <div class="border-t border-muted-200"></div>

                            <div class="flex items-center justify-center py-3 px-4">
                                <button class="w-full rounded-md py-2 text-sm text-secondary hover:bg-secondary/10 transition-colors">Show all
                                    notifications</button>
                            </div>
                        </div>
                    </div>
                </li>
                <li>
                    <div class="w-px h-5 bg-muted-300"></div>
                </li>
                <li class="relative list-none" id="userMenu">
                    <button id="userButton" type="button" class="inline-flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-muted-50 transition-colors
                            focus:outline-none" aria-haspopup="menu" aria-expanded="false">
                        <span class="hidden lg:inline text-sm text-muted-600 font-medium">{{ Auth::user()->username }}</span>
                        <img class="h-8 w-8 rounded-full object-cover ring-2 ring-white border border-muted-200 shadow-sm"
                            src="{{ getUserAvatar(Auth::user()) }}" alt="{{ Auth::user()->name }} avatar">
                        <svg class="h-4 w-4 text-muted-400" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" />
                        </svg>
                    </button>

                    <div id="userList"
                        class="absolute right-0 z-20 mt-2 w-48 h-fit origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-muted-200 border border-muted-200 hidden"
                        role="menu" aria-labelledby="userButton">
                        <a href="{{ route('profile') }}"
                            class="flex items-center gap-3 px-4 py-2 text-sm text-muted-700 hover:bg-muted-50 hover:text-primary rounded-t-xl transition-colors"
                            role="menuitem">
                            <svg class="h-4 w-4 text-muted-400 group-hover:text-primary" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z" />
                            </svg>
                            {{ __('app.profile') }}
                        </a>

                        <a href="{{ route('settings') }}"
                            class="flex items-center gap-3 px-4 py-2 text-sm text-muted-700 hover:bg-muted-50 hover:text-primary transition-colors"
                            role="menuitem">
                            <svg class="h-4 w-4 text-muted-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M11.3 1.046a1 1 0 00-2.6 0l-.25.986a7.977 7.977 0 00-1.86 1.074l-.97-.35a1 1 0 00-1.26 1.26l.35.97A7.98 7.98 0 002.032 8H1a1 1 0 000 2h1.032a7.98 7.98 0 001.684 3.014l-.35.97a1 1 0 001.26 1.26l.97-.35A7.977 7.977 0 008.45 16.97l.25.986a1 1 0 002.6 0l.25-.986a7.977 7.977 0 001.86-1.074l.97.35a1 1 0 001.26-1.26l-.35-.97A7.98 7.98 0 0016.968 10H18a1 1 0 100-2h-1.032a7.98 7.98 0 00-1.684-3.014l.35-.97a1 1 0 00-1.26-1.26l-.97.35A7.977 7.977 0 0011.55 2.03l-.25-.986zM10 13a3 3 0 110-6 3 3 0 010 6z" />
                            </svg>
                            {{ __('app.settings') }}
                        </a>

                        <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm text-muted-700 hover:bg-muted-50 hover:text-primary transition-colors"
                            role="menuitem">
                            <svg class="h-4 w-4 text-muted-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M5 4a2 2 0 00-2 2v9a1 1 0 001.447.894L8 14.618l3.553 1.276A1 1 0 0013 15V6a2 2 0 00-2-2H5z" />
                            </svg>
                            {{ __('app.activity_log') }}
                        </a>

                        <div class="my-1 border-t border-muted-200"></div>

                        <form action="{{ route('logout') }}" method="POST" class="px-2 py-2">
                            @csrf
                            <button type="submit"
                                class="w-full text-center px-2 py-2 text-sm text-danger hover:bg-danger/10 rounded-lg font-medium transition-colors">
                                {{ __('app.logout') }}
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>
        <div class="flex justify-center w-full w-max-[1200px] px-4 md:px-8 lg:px-16 xl:px-24 py-8">
            @yield('content')
        </div>
    </div>
    {{-- alert toast --}}
    <div id="alerts" class="flex flex-col gap-2 items-end fixed top-5 right-5 z-[60]"></div>

    {{-- scroll to top + chatbot button --}}
    <div class="fixed bottom-5 right-5 flex flex-col items-end gap-3 z-50">
        <div class="flex flex-col items-end gap-2">
            {{-- Chatbot chat box --}}
            <div id="chatbot-chatbox" class="hidden w-[550px] h-[650px] mr-5 bg-white rounded-bl-2xl rounded-tl-2xl rounded-tr-2xl shadow-2xl border flex-col transition-all duration-300 ease-out 
                transform opacity-0 translate-y-4 scale-95 pointer-events-none invisible">
                {{-- Header --}}
                <div
                    class="flex items-center justify-between gap-3 bg-gradient-to-tl from-[#F1EFFC] to-[#5D3FD3] h-20 rounded-t-2xl px-6 shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center justify-center rounded-full p-2 border bg-white">
                            <img src="{{ asset('img/bot.png') }}" alt="" class="w-8 h-8">
                        </div>
                        <div>
                            <p class="text-lg text-white font-semibold">Bot Bot</p>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-green-500 border"></div>
                                <p class="text-sm text-white">Online now</p>
                            </div>
                        </div>
                    </div>
                    <button id="close-bot-btn"
                        class="rounded-full p-1 hover:bg-[#F1EFFC] fill-gray-100 hover:fill-[#5D3FD3] cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6">
                            <path
                                d="M96 320C96 302.3 110.3 288 128 288L512 288C529.7 288 544 302.3 544 320C544 337.7 529.7 352 512 352L128 352C110.3 352 96 337.7 96 320z" />
                        </svg>
                    </button>
                </div>

                {{-- Chat (scrollable) --}}
                <div id="chat-section" class="flex-1 min-h-0 overflow-y-auto px-3 py-2 space-y-3">
                    {{-- bot message --}}
                    <div class="flex items-end gap-2">
                        <div class="flex items-center justify-center rounded-full p-2 border bg-white">
                            <img src="{{ asset('img/bot.png') }}" alt="" class="w-6 h-6">
                        </div>
                        <div class="max-w-96 shadow-lg rounded-2xl px-3 py-2 border border-gray-300">
                            Hi, how can I help you?
                        </div>
                    </div>
                    <!-- <div id="chatbot-typing-template">
                        <div class="flex items-center gap-2">
                            <div class="flex items-center justify-center rounded-full p-2 border bg-white">
                                <img src="{{ asset('img/bot.png') }}" alt="Bot" class="w-6 h-6">
                            </div>
                            <div class="max-w-72 shadow-lg rounded-2xl px-3 h-8 border border-gray-300">
                                <div class="flex items-center justify-center gap-1 h-full w-full">
                                    <span class="chat-typing-dot"></span>
                                    <span class="chat-typing-dot"></span>
                                    <span class="chat-typing-dot"></span>
                                </div>
                            </div>
                        </div>
                    </div> -->
                </div>

                {{-- send section --}}
                <div class="h-20 px-3 py-2 border-t border-gray-300 shrink-0 flex items-center gap-2">
                    <input id="chatbot-input" type="text" placeholder="Type a message…"
                        class="flex-1 rounded-xl px-3 py-2 border border-gray-300 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3]" />

                    <button id="chatbot-send-btn" type="button"
                        class="p-2 rounded-full bg-[#5D3FD3] fill-white hover:opacity-95">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5">
                            <path
                                d="M568.4 37.7C578.2 34.2 589 36.7 596.4 44C603.8 51.3 606.2 62.2 602.7 72L424.7 568.9C419.7 582.8 406.6 592 391.9 592C377.7 592 364.9 583.4 359.6 570.3L295.4 412.3C290.9 401.3 292.9 388.7 300.6 379.7L395.1 267.3C400.2 261.2 399.8 252.3 394.2 246.7C388.6 241.1 379.6 240.7 373.6 245.8L261.2 340.1C252.1 347.7 239.6 349.7 228.6 345.3L70.1 280.8C57 275.5 48.4 262.7 48.4 248.5C48.4 233.8 57.6 220.7 71.5 215.7L568.4 37.7z" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Chatbot button --}}
            <button id="chatbot-btn" title="Chat Bot"
                class="w-11 h-11 md:w-12 md:h-12 flex items-center justify-center rounded-full bg-[#5D3FD3] hover:opacity-95 transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 md:w-6 md:h-6 fill-white">
                    <path
                        d="M352 64C352 46.3 337.7 32 320 32C302.3 32 288 46.3 288 64L288 128L192 128C139 128 96 171 96 224L96 448C96 501 139 544 192 544L448 544C501 544 544 501 544 448L544 224C544 171 501 128 448 128L352 128L352 64zM160 432C160 418.7 170.7 408 184 408L216 408C229.3 408 240 418.7 240 432C240 445.3 229.3 456 216 456L184 456C170.7 456 160 445.3 160 432zM280 432C280 418.7 290.7 408 304 408L336 408C349.3 408 360 418.7 360 432C360 445.3 349.3 456 336 456L304 456C290.7 456 280 445.3 280 432zM400 432C400 418.7 410.7 408 424 408L456 408C469.3 408 480 418.7 480 432C480 445.3 469.3 456 456 456L424 456C410.7 456 400 445.3 400 432zM224 240C250.5 240 272 261.5 272 288C272 314.5 250.5 336 224 336C197.5 336 176 314.5 176 288C176 261.5 197.5 240 224 240zM368 288C368 261.5 389.5 240 416 240C442.5 240 464 261.5 464 288C464 314.5 442.5 336 416 336C389.5 336 368 314.5 368 288zM64 288C64 270.3 49.7 256 32 256C14.3 256 0 270.3 0 288L0 384C0 401.7 14.3 416 32 416C49.7 416 64 401.7 64 384L64 288zM608 256C590.3 256 576 270.3 576 288L576 384C576 401.7 590.3 416 608 416C625.7 416 640 401.7 640 384L640 288C640 270.3 625.7 256 608 256z" />
                </svg>
            </button>
        </div>

        {{-- Scroll to top button --}}
        <button id="scroll-to-top-btn" title="{{ __('app.scroll_to_top') }}"
            class="w-11 h-11 md:w-12 md:h-12 flex items-center justify-center rounded-full bg-[#5D3FD3] hover:opacity-95 transition">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 md:w-6 md:h-6 fill-white">
                <path
                    d="M342.6 81.4C330.1 68.9 309.8 68.9 297.3 81.4L137.3 241.4C124.8 253.9 124.8 274.2 137.3 286.7C149.8 299.2 170.1 299.2 182.6 286.7L288 181.3L288 552C288 569.7 302.3 584 320 584C337.7 584 352 569.7 352 552L352 181.3L457.4 286.7C469.9 299.2 490.2 299.2 502.7 286.7C515.2 274.2 515.2 253.9 502.7 241.4L342.7 81.4z" />
            </svg>
        </button>
    </div>

    {{-- team member dialog --}}
    <x-user.team-member-dialog :teamMembers="$teamMembers ?? collect()" />
    {{-- task dialog --}}
    <x-user.task-dialog :assignedTasks="$assignedTasks ?? collect()" />
    {{-- request day off dialog --}}
    <x-user.request-dayoff-dialog />

    @isset($user)
        @include('users.update', ['users' => $users ?? collect()])
    @endisset
    <!-- <script>


</script> -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        window.CHAT_LANG = "{{ app()->getLocale() }}"; // "en" or "vi"
    </script>

</body>

</html>