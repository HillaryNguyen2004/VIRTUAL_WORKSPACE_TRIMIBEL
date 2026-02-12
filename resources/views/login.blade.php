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

            <h1 class="text-xl md:text-3xl font-medium text-main text-center">{{ __('login.welcome_to_dashboard') }}</h1>
            <p class="text-sm md:text-base mt-2 text-center text-muted-400">{{ __('login.fill_the_form_below_to_login') }}</p>

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
                    <div class="h-px flex-1 bg-muted-200"></div>
                    <span class="text-xs text-muted-400">{{ __('login.or') }}</span>
                    <div class="h-px flex-1 bg-muted-200"></div>
                </div>

                <!-- Email -->
                <x-form.input
                    label="login.email"
                    id="email"
                    name="email"
                    type="email"
                    placeholder="{{ __('login.enter_email') }}"
                    class="mt-1"
                    :isRequired="true"
                />

                <!-- Password -->
                <x-form.password
                    label="login.password"
                    id="password"
                    name="password"
                    placeholder="{{ __('login.enter_password') }}"
                    btnId="togglePwd"
                    class="mt-1"
                    :isRequired="true"
                />

                <div class="flex justify-end">
                    <a href="{{ route('password.request') }}" class="text-xs md:text-sm text-primary font-medium hover:underline">
                        {{ __('login.forget_password') }}
                    </a>
                </div>

                <!-- Submit -->
                <button type="submit" id="submit-btn" class="w-full py-2 px-3 rounded-xl text-white font-medium bg-primary-gradient
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
                <p class="text-center text-xs md:text-sm text-muted-400">
                    {{ __('login.dont_have_account') }}
                    <a href="{{ route('register') }}" class="text-primary hover:underline font-medium">{{ __('login.sign_up_here') }}</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Right -->
    <div class="bg-panel-right-gradient rounded-3xl w-1/2 min-h-[70vh] lg:block hidden"></div>
@endsection