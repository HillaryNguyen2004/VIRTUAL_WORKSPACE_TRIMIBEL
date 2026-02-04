


@extends('layout_login')
@section('title', __('register.page_title'))
@section('content')
    @vite(['resources/js/validate_pwd.js'])
    @vite(['resources/js/toggle_pwd.js'])
    @vite(['resources/js/toggle_confirm_pwd.js'])
    <!-- Left -->
    <div class="bg-panel-left-gradient rounded-3xl w-1/2 min-h-[70vh] lg:block hidden"></div>

    <!-- Right -->
    <div class="h-full w-full lg:w-1/2">
        <!-- Logo -->
        <div class="flex z-50 w-full justify-between">
            Logo
            <ul><li>
                @php $currentLocale = app()->getLocale(); @endphp
                <div class="relative" id="langMenu">
                    <button id="langButton" type="button" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-md font-medium text-gray-700
                            hover:bg-gray-100 focus:outline-none" aria-haspopup="menu" aria-expanded="false">
                        @if ($currentLocale === 'en')
                            <div class="flex items-center gap-1">
                                <div>🇺🇸</div>
                                <!-- <span class="hidden lg:inline"> {{ __('app.lang_english') }}</span> -->
                            </div>
                        @else
                            <div class="flex items-center gap-1">
                                <div>🇻🇳</div>
                                <!-- <span class="hidden lg:inline"> {{ __('app.lang_vietnamese') }}</span> -->
                            </div>
                        @endif

                        <!-- chevron -->
                        <svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" />
                        </svg>
                    </button>

                    <div id="langList" class="absolute right-0 z-20 mt-2 w-36 origin-top rounded-xl bg-white shadow-lg ring-1 ring-black/5
                            hidden" role="menu" aria-labelledby="langButton">
                        <a href="{{ route('lang.switch', 'en') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-xl"
                            role="menuitem">🇺🇸 {{ __('app.lang_english') }}</a>
                        <a href="{{ route('lang.switch', 'vi') }}"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-xl"
                            role="menuitem">🇻🇳 {{ __('app.lang_vietnamese') }}</a>
                    </div>
                </div>
            </li></ul>
        </div>

        <!-- Content -->
        <div class="flex flex-col items-center justify-center w-full h-full pb-10 m-auto sm:w-[440px]">

            <h1 class="text-3xl font-medium text-gray-900 text-center">{{ __('register.create_new_account') }}</h1>
            <p class="mt-2 text-center text-gray-500">{{ __('register.subheading') }}</p>

            <!-- @if (session('status'))
                        <div class="mt-6 rounded-lg bg-green-50 text-green-700 px-4 py-3">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="mt-6 rounded-lg bg-red-50 text-red-700 px-4 py-3">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif -->

            <form method="POST" action="{{ route('register.post') }}"
                class="flex flex-col items-center justify-center mt-5 space-y-6 w-full ">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                    <!-- First name -->
                    <div>
                        <x-form.input
                            label="register.first_name"
                            id="first_name"
                            name="first_name"
                            type="text"
                            placeholder="{{ __('register.enter_first_name') }}"
                            :isRequired="true"
                        />
                    </div>

                    <!-- Last name -->
                    <div>
                        <x-form.input
                            label="register.last_name"
                            id="last_name"
                            name="last_name"
                            type="text"
                            placeholder="{{ __('register.enter_last_name') }}"
                        />
                    </div>

                    <!-- Email -->
                    <div class="col-span-2">
                        <x-form.input
                            label="register.email"
                            id="email"
                            name="email"
                            type="email"
                            placeholder="{{ __('register.enter_email') }}"
                            :isRequired="true"
                        />
                    </div>

                    <!-- Password -->
                    <div class="col-span-2">
                        <x-form.password
                            label="register.password"
                            id="password"
                            name="password"
                            placeholder="{{ __('register.enter_password') }}"
                            btnId="togglePwd"
                            :isRequired="true"
                        />
                        <ul id="validate-pwd" class="mt-2 ml-2 space-y-1 text-[12px] hidden">
                            <li id="at-least-8-words" class="text-gray-300 flex items-center gap-2">
                                <svg data-icon="ok" viewBox="0 0 1024 1024" class="w-2 h-2 md:w-2.5 md:h-2.5" fill="currentColor">
                                    <path 
                                        d="M864 240.5l-512 512-224-224-112 112 336 336 624-624z" />
                                </svg>
                                {{ __('register.at_least_8_characters') }}
                            </li>
                            <li id="at-least-1-spe-char" class="text-gray-300 flex items-center gap-2">
                                <svg data-icon="ok" viewBox="0 0 1024 1024" class="w-2 h-2 md:w-2.5 md:h-2.5" fill="currentColor">
                                    <path 
                                        d="M864 240.5l-512 512-224-224-112 112 336 336 624-624z" />
                                </svg>
                                {{ __('register.at_least_1_special_character') }}
                            </li>
                            <li id="at-least-1-number" class="text-gray-300 flex items-center gap-2">
                                <svg data-icon="ok" viewBox="0 0 1024 1024" class="w-2 h-2 md:w-2.5 md:h-2.5" fill="currentColor">
                                    <path 
                                        d="M864 240.5l-512 512-224-224-112 112 336 336 624-624z" />
                                </svg>
                                {{ __('register.at_least_1_number') }}
                            </li>
                        </ul>
                    </div>

                    <!-- Confirm password -->
                    <div class="col-span-2">
                        <x-form.password
                            label="register.confirm_password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="{{ __('register.confirm_password') }}"
                            btnId="toggleConfirmPwd"
                            :isRequired="true"
                        />
                        <p id="pwd-match" class="mt-1 ml-2 text-[12px] text-gray-300 hidden">{{ __('register.password_match') }}</p>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" id="submit-btn" class="w-full py-2 px-3 rounded-xl text-white font-medium bg-btn-login
                                shadow-[0_8px_24px_rgba(99,102,241,0.35)] hover:opacity-95 transition
                                inline-flex items-center justify-center gap-2 text-sm md:text-base
                                disabled:opacity-60 disabled:cursor-not-allowed" aria-live="polite">
                    <!-- spinner (hidden by default) -->
                    <svg data-spinner class="hidden w-4 h-4 md:h-5 md:w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span data-label>{{ __('register.signup') }}</span>
                </button>

                <!-- Route to login -->
                <p class="text-center text-xs md:text-sm text-gray-500">
                    {{ __('register.already_have_account') }}
                    <a href="{{ route('login') }}" class="text-[#5D3FD3] hover:underline font-medium">
                        {{ __('register.login_here') }}
                    </a>
                </p>

                <!-- Divider -->
                <div class="my-6 flex items-center gap-3 w-full md:w-80 lg:w-[450px]">
                    <div class="h-px flex-1 bg-gray-200"></div>
                    <span class="text-xs text-gray-400">{{ __('register.or') }}</span>
                    <div class="h-px flex-1 bg-gray-200"></div>
                </div>

                <!-- Google button -->
                <a href="{{ route('google.login') }}"
                    class="mt-6 w-fit mx-auto flex items-center gap-2 rounded-xl bg-white hover:bg-[#f8f8f8] border border-gray-200 px-4 py-2 shadow-[4px_4px_20px_0_rgba(109,82,216,0.2)] transition">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="" class="h-4 w-4">
                    <span class="text-xs md:text-sm font-medium text-gray-700">{{ __('register.register_with_google') }}</span>
                </a>
            </form>
        </div>
    </div>
@endsection