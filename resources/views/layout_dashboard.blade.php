<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ __('app.title') }}</title>

    <!-- Custom fonts for this template-->
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <!-- <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet"> -->
    @vite(['resources/css/app.css'])
    <!-- Bootstrap core JavaScript-->
    @vite(['public/vendor/jquery/jquery.min.js'])
    @vite(['public/vendor/bootstrap/js/bootstrap.bundle.min.js'])
    <!-- Core plugin JavaScript-->
    @vite(['public/vendor/jquery-easing/jquery.easing.min.js'])
    <!-- Custom scripts for all pages-->
    @vite(['public/js/sb-admin-2.min.js'])

    <!-- Dashboard layout -->
    @vite(['resources/utils/dashboard_layout/switch_lang.js'])
    @vite(['resources/utils/dashboard_layout/dropdown_profile.js'])
    @vite(['resources/utils/dashboard_layout/toggle_sidebar.js'])

    <!-- User dashboard -->
    @vite(['resources/utils/user_dashboard/check_in_out_api.js'])
</head>

@stack('scripts')

<body id="page-top" class="flex flex-row">
    <!-- Side bar -->
    <div class="flex flex-row fixed xl:static h-screen w-screen xl:w-fit z-[-1] xl:z-40" id="rounded-sidebar">
        <nav class="flex flex-col gap-[79px] -translate-x-full xl:translate-x-0 bg-[#FDFDFF] w-fit h-screen border-r border-[#F1EFFC] p-5 z-40 transition duration-300"
            id="sidebar">
            <div class="w-full text-center md:text-left">Logo</div>
            <ul class="flex flex-col gap-[18px] justify-start sm:w-[220px]">
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
                    <x-nav-link href="{{ route($dashRoute) }}" :active="request()->routeIs(['*.dashboard'])">
                        <svg viewBox="0 0 128 128" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="12"
                            stroke-linecap="round" stroke-linejoin="round">
                            <!-- outer rounded square -->
                            <rect x="8" y="8" width="112" height="112" rx="14" />
                            <!-- inner dividers: left big panel + right stacked panels -->
                            <path d="M72 20V108M72 64H108" />
                        </svg>
                        <span class="hidden md:inline">{{ __('app.dashboard') }}</span>
                    </x-nav-link>
                </li>
                <li>
                    <x-nav-link href="{{ route('chat.index') }}" :active="request()->routeIs('chat.index')"
                        class="flex items-center gap-4 px-4 py-4 hover:bg-gray-100 rounded-xl cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5">
                            <path
                                d="M115.9 448.9C83.3 408.6 64 358.4 64 304C64 171.5 178.6 64 320 64C461.4 64 576 171.5 576 304C576 436.5 461.4 544 320 544C283.5 544 248.8 536.8 217.4 524L101 573.9C97.3 575.5 93.5 576 89.5 576C75.4 576 64 564.6 64 550.5C64 546.2 65.1 542 67.1 538.3L115.9 448.9zM153.2 418.7C165.4 433.8 167.3 454.8 158 471.9L140 505L198.5 479.9C210.3 474.8 223.7 474.7 235.6 479.6C261.3 490.1 289.8 496 319.9 496C437.7 496 527.9 407.2 527.9 304C527.9 200.8 437.8 112 320 112C202.2 112 112 200.8 112 304C112 346.8 127.1 386.4 153.2 418.7z" />
                        </svg>
                        <span class="hidden md:inline">{{ __('app.chat_box') }}</span>
                    </x-nav-link>
                </li>
            </ul>
        </nav>
        <div class="hidden fixed bg-black/20 h-screen w-screen z-30" id="sidebar-bg-addition"></div>
    </div>
    <!-- Main content -->
    <div class="flex flex-col w-full h-screen overflow-y-scroll">
        <!-- Top bar -->
        <nav
            class="flex justify-between xl:justify-end pl-10 pr-10 xl:pr-[64px] py-3 shadow-[0_4px_40px_0_rgba(206,197,242,0.2)]">
            <button class="flex items-center xl:hidden hover:bg-[#F1EFFC] rounded-full p-2" id="sidebar-menu-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-[#5D3FD3]">
                    <path
                        d="M96 160C96 142.3 110.3 128 128 128L512 128C529.7 128 544 142.3 544 160C544 177.7 529.7 192 512 192L128 192C110.3 192 96 177.7 96 160zM96 320C96 302.3 110.3 288 128 288L512 288C529.7 288 544 302.3 544 320C544 337.7 529.7 352 512 352L128 352C110.3 352 96 337.7 96 320zM544 480C544 497.7 529.7 512 512 512L128 512C110.3 512 96 497.7 96 480C96 462.3 110.3 448 128 448L512 448C529.7 448 544 462.3 544 480z" />
                </svg>
            </button>
            <ul class="flex flex-row gap-5 items-center">
                <li>
                    @php $currentLocale = app()->getLocale(); @endphp
                    <div class="relative" id="langMenu">
                        <button id="langButton" type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700
                                hover:bg-gray-100 focus:outline-none" aria-haspopup="menu" aria-expanded="false">
                            @if ($currentLocale === 'en')
                                <div class="flex items-center gap-1">
                                    <div>🇺🇸</div>
                                    <span class="hidden lg:inline"> {{ __('app.lang_english') }}</span>
                                </div>
                            @else
                                <div class="flex items-center gap-1">
                                    <div>🇻🇳</div>
                                    <span class="hidden lg:inline"> {{ __('app.lang_vietnamese') }}</span>
                                </div>
                            @endif

                            <!-- chevron -->
                            <svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" />
                            </svg>
                        </button>

                        <div id="langList" class="absolute right-0 z-20 mt-2 w-36 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black/5
                                hidden" role="menu" aria-labelledby="langButton">
                            <a href="{{ route('lang.switch', 'en') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-xl"
                                role="menuitem">🇺🇸 {{ __('app.lang_english') }}</a>
                            <a href="{{ route('lang.switch', 'vi') }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-xl"
                                role="menuitem">🇻🇳 {{ __('app.lang_vietnamese') }}</a>
                        </div>
                    </div>
                </li>
                <li>
                    <button class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-[#D9D8DD]">
                            <path
                                d="M320 64C302.3 64 288 78.3 288 96L288 99.2C215 114 160 178.6 160 256L160 277.7C160 325.8 143.6 372.5 113.6 410.1L103.8 422.3C98.7 428.6 96 436.4 96 444.5C96 464.1 111.9 480 131.5 480L508.4 480C528 480 543.9 464.1 543.9 444.5C543.9 436.4 541.2 428.6 536.1 422.3L526.3 410.1C496.4 372.5 480 325.8 480 277.7L480 256C480 178.6 425 114 352 99.2L352 96C352 78.3 337.7 64 320 64zM258 528C265.1 555.6 290.2 576 320 576C349.8 576 374.9 555.6 382 528L258 528z" />
                        </svg>
                    </button>
                </li>
                <li>
                    <div class="w-[1px] h-5 bg-[#D9D8DD]"></div>
                </li>
                <li class="relative list-none" id="userMenu">
                    <button id="userButton" type="button" class="inline-flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-gray-100
                            focus:outline-none" aria-haspopup="menu" aria-expanded="false">
                        <span class="hidden lg:inline text-sm text-gray-500">{{ Auth::user()->username }}</span>
                        <img class="h-7 w-7 rounded-full object-cover ring-2 ring-white"
                            src="{{ getUserAvatar(Auth::user()) }}" alt="{{ Auth::user()->name }} avatar">
                        <svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" />
                        </svg>
                    </button>

                    <div id="userList"
                        class="absolute right-0 z-20 mt-2 w-[190px] h-fit origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black/5 hidden"
                        role="menu" aria-labelledby="userButton">
                        <a href="{{ route('profile') }}"
                            class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-xl"
                            role="menuitem">
                            <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z" />
                            </svg>
                            {{ __('app.profile') }}
                        </a>

                        <a href="{{ route('settings') }}"
                            class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            role="menuitem">
                            <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M11.3 1.046a1 1 0 00-2.6 0l-.25.986a7.977 7.977 0 00-1.86 1.074l-.97-.35a1 1 0 00-1.26 1.26l.35.97A7.98 7.98 0 002.032 8H1a1 1 0 000 2h1.032a7.98 7.98 0 001.684 3.014l-.35.97a1 1 0 001.26 1.26l.97-.35A7.977 7.977 0 008.45 16.97l.25.986a1 1 0 002.6 0l.25-.986a7.977 7.977 0 001.86-1.074l.97.35a1 1 0 001.26-1.26l-.35-.97A7.98 7.98 0 0016.968 10H18a1 1 0 100-2h-1.032a7.98 7.98 0 00-1.684-3.014l.35-.97a1 1 0 00-1.26-1.26l-.97.35A7.977 7.977 0 0011.55 2.03l-.25-.986zM10 13a3 3 0 110-6 3 3 0 010 6z" />
                            </svg>
                            {{ __('app.settings') }}
                        </a>

                        <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            role="menuitem">
                            <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M5 4a2 2 0 00-2 2v9a1 1 0 001.447.894L8 14.618l3.553 1.276A1 1 0 0013 15V6a2 2 0 00-2-2H5z" />
                            </svg>
                            {{ __('app.activity_log') }}
                        </a>

                        <div class="my-1 border-t border-gray-200"></div>

                        <form action="{{ route('logout') }}" method="POST" class="px-2 py-2">
                            @csrf
                            <button type="submit"
                                class="w-full text-center px-2 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                                {{ __('app.logout') }}
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>
        <!-- Content -->
        <div class="flex px-12 lg:px-20 py-8 justify-center w-full">
            @yield('content')
        </div>
    </div>
    <div id="alerts" class="flex flex-col gap-2 items-end fixed top-5 right-5 z-[60]"></div>
    <x-user.team-member-dialog :teamMembers="$teamMembers ?? collect()" />
    <x-user.task-dialog :assignedTasks="$assignedTasks ?? collect()" />
    <x-user.request-dayoff-dialog />

    <!-- <script>

function loadNotifications() {
    fetch('/notifications/unread')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('alertsList');
            const badge = document.querySelector('#alertsDropdown .badge-counter');

            list.innerHTML = ''; // clear first

            data.forEach(notification => {
                const item = `
                    <a class="dropdown-item d-flex align-items-center notification-item" 
                    href="#" data-id="${notification.id}">
                        <div class="mr-3">
                            <div class="icon-circle bg-primary">
                                <i class="fas fa-bell text-white"></i>
                            </div>
                        </div>
                        <div>
                            <div class="small text-gray-500">${notification.data.date}</div>
                            <span class="font-weight-bold">${notification.data.message}</span>
                        </div>
                    </a>
                `;
                list.insertAdjacentHTML('beforeend', item);
            });

            // Update badge count
            badge.textContent = data.length > 0 ? data.length : '';
        })
        .catch(err => console.error('Error loading notifications:', err));
}

// Run on page load
loadNotifications();

/**
 * Mark notification as read when clicked
 */
document.addEventListener('click', function(e) {
    if (e.target.closest('.notification-item')) {
        e.preventDefault();
        const item = e.target.closest('.notification-item');
        const id = item.getAttribute('data-id');

        fetch(`/notifications/read/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(() => {
            item.remove(); // remove from dropdown
            const badge = document.querySelector('#alertsDropdown .badge-counter');
            const current = parseInt(badge.textContent) || 0;
            badge.textContent = current > 1 ? current - 1 : '';
        });
    }
});

/**
 * Mark all notifications as read
 */
document.getElementById('markAllRead').addEventListener('click', function(e) {
    e.preventDefault();

    fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    }).then(() => {
        document.getElementById('alertsList').innerHTML = '';
        document.querySelector('#alertsDropdown .badge-counter').textContent = '';
    });
});


/**
 * Listen for new broadcast notifications
 */
Echo.private(`App.Models.User.{{ Auth::id() }}`)
    .notification((notification) => {
        const list = document.getElementById('alertsList');
        const badge = document.querySelector('#alertsDropdown .badge-counter');

        const item = `
            <a class="dropdown-item d-flex align-items-center notification-item" 
            href="#" data-id="${notification.id}">
                <div class="mr-3">
                    <div class="icon-circle bg-primary">
                        <i class="fas fa-bell text-white"></i>
                    </div>
                </div>
                <div>
                    <div class="small text-gray-500">${notification.date}</div>
                    <span class="font-weight-bold">${notification.message}</span>
                </div>
            </a>
        `;


        // Add new notification at the top
        list.insertAdjacentHTML('afterbegin', item);

        // Increment badge counter
        const current = parseInt(badge.textContent) || 0;
        badge.textContent = current + 1;
    });




</script> -->

</body>

</html>