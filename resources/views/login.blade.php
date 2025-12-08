@extends('layout_login')
@section('title', 'Login')
@section('content')
    @vite(['resources/js/toggle_pwd.js'])
    <!-- Left -->
    <div class="h-full w-full lg:w-1/2">
        <!-- Logo -->
        <div class="flex z-50 w-full justify-between">
            Logo
            <ul>
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
            </ul>
        </div>

        <!-- Content -->
        <div class="flex flex-col items-center justify-center w-full h-full pb-10 m-auto sm:w-96">

            <h1 class="text-xl md:text-3xl font-medium text-gray-900 text-center">{{ __('login.welcome_to_dashboard') }}</h1>
            <p class="text-sm md:text-base mt-2 text-center text-gray-500">{{ __('login.fill_the_form_below_to_login') }}</p>

            <!-- Google button -->
            <a href="{{ route('google.login') }}"
                class="mt-6 w-fit mx-auto flex items-center gap-2 rounded-xl bg-white hover:bg-[#f8f8f8] border border-gray-200 px-4 py-2 shadow-[4px_4px_20px_0_rgba(109,82,216,0.2)] transition">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="" class="h-4 w-4">
                <span class="text-sm font-medium text-gray-700">{{ __('login.sign_in_with_google') }}</span>
            </a>

            <form method="POST" action="{{ route('login') }}" class="space-y-4 w-full sm:w-96">
                @csrf
                <!-- Divider -->
                <div class="my-6 flex items-center gap-3 w-full">
                    <div class="h-px flex-1 bg-gray-200"></div>
                    <span class="text-xs text-gray-400">{{ __('login.or') }}</span>
                    <div class="h-px flex-1 bg-gray-200"></div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">{{ __('login.email') }}</label>
                    <input id="email" name="email" type="email" placeholder="{{ __('login.enter_email') }}"
                        class="text-sm md:text-base mt-1 block w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] text-[#5D3FD3] transition" />
                    @error('email')
                        <span class="text-red-400 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">{{ __('login.password') }}</label>
                    <div class="relative mt-1">
                        <input id="password" name="password" type="password" placeholder="{{ __('login.enter_password') }}"
                            class="block text-sm md:text-base w-full rounded-xl border border-gray-300 px-4 py-3 pr-12 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] text-[#5D3FD3] transition" />
                        <button type="button" id="togglePwd" class="absolute inset-y-0 right-3 p-3"
                            aria-label="Show password" aria-controls="password" aria-pressed="false">
                            <!-- open eye -->
                            <svg data-icon="eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                class="w-4 h-4 md:w-5 md:h-5 fill-gray-400">
                                <path
                                    d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z" />
                            </svg>

                            <!-- closed eye (slash) -->
                            <svg data-icon="eye-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                class="w-4 h-4 md:w-5 md:h-5 fill-gray-400 hidden">
                                <path
                                    d="M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L504.5 470.8C507.2 468.4 509.9 466 512.5 463.6C559.3 420.1 590.6 368.2 605.5 332.5C608.8 324.6 608.8 315.8 605.5 307.9C590.6 272.2 559.3 220.2 512.5 176.8C465.4 133.1 400.7 96.2 319.9 96.2C263.1 96.2 214.3 114.4 173.9 140.4L73 39.1zM236.5 202.7C260 185.9 288.9 176 320 176C399.5 176 464 240.5 464 320C464 351.1 454.1 379.9 437.3 403.5L402.6 368.8C415.3 347.4 419.6 321.1 412.7 295.1C399 243.9 346.3 213.5 295.1 227.2C286.5 229.5 278.4 232.9 271.1 237.2L236.4 202.5zM357.3 459.1C345.4 462.3 332.9 464 320 464C240.5 464 176 399.5 176 320C176 307.1 177.7 294.6 180.9 282.7L101.4 203.2C68.8 240 46.4 279 34.5 307.7C31.2 315.6 31.2 324.4 34.5 332.3C49.4 368 80.7 420 127.5 463.4C174.6 507.1 239.3 544 320.1 544C357.4 544 391.3 536.1 421.6 523.4L357.4 459.2z" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="text-red-400 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <a href="{{ route('password.request') }}" class="text-xs md:text-sm text-[#5D3FD3] hover:underline">
                        {{ __('login.forget_password') }}
                    </a>
                </div>

                <!-- Submit -->
                <button type="submit" id="submit-btn" class="w-full py-2 px-3 rounded-xl text-white font-medium bg-btn-login
                            shadow-[0_8px_24px_rgba(99,102,241,0.35)] hover:opacity-95 transition
                            inline-flex items-center justify-center gap-2 text-sm md:text-base
                            disabled:opacity-60 disabled:cursor-not-allowed" aria-live="polite">
                    <!-- spinner -->
                    <svg data-spinner class="hidden w-4 h-4 md:w-5 md:h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span data-label>{{ __('login.login') }}</span>
                </button>

                <!-- Route to signup -->
                <p class="text-center text-xs md:text-sm text-gray-500">
                    {{ __('login.dont_have_account') }}
                    <a href="{{ route('register') }}" class="text-[#5D3FD3] hover:underline font-medium">{{ __('login.sign_up_here') }}</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Right -->
    <div class="bg-panel-right-gradient rounded-3xl w-1/2 min-h-[70vh] lg:block hidden"></div>
@endsection