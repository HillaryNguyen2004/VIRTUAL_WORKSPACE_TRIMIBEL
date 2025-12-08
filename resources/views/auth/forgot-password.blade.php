@extends('layout_login')
@section('title', __('forgot_password.page_title'))
@section('content')
@vite(['resources/utils/toggle_pwd.js'])

<div class="h-full w-full lg:w-1/2">
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
                            </div>
                    @else
                        <div class="flex items-center gap-1">
                            <div>🇻🇳</div>
                            </div>
                    @endif

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

    <div class="flex flex-col items-center justify-center w-full h-full pb-10 m-auto sm:w-96">
        <h1 class="text-xl md:text-3xl font-medium text-gray-900 text-center">{{ __('forgot_password.title') }}</h1>

        @if (session('status'))
            <div class="alert alert-success text-center text-sm md:text-base text-gray-900 my-4">
                {{ session('status') }}
            </div>
        @else
            <p class="text-sm md:text-base my-2 text-center text-gray-500 w-full sm:w-96">
                {{ __('forgot_password.subtitle') }}
            </p>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6 w-full sm:w-96">
                @csrf
                <div class="mb-3">
                    <input id="email" name="email" type="email" placeholder="{{ __('forgot_password.enter_email') }}" required autofocus
                        class="form-control text-sm md:text-base mt-4 block w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] text-[#5D3FD3] transition" />
                    @error('email')
                        <span class="text-red-400 text-xs">{{ $message }}</span>
                    @enderror

                </div>
                <button type="submit" id="submit-btn" class="w-full py-2 px-3 rounded-xl text-white font-medium bg-btn-login
                            shadow-[0_8px_24px_rgba(99,102,241,0.35)] hover:opacity-95 transition
                            inline-flex items-center justify-center gap-2 text-sm md:text-base
                            disabled:opacity-60 disabled:cursor-not-allowed" aria-live="polite">

                    <svg data-spinner class="hidden w-4 h-4 md:w-5 md:h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span data-label>{{ __('forgot_password.send_reset_link') }}</span>
                </button>
            </form>

        @endif
        <div class="flex gap-2 text-center items-center text-xs md:text-sm mt-4 text-gray-500 hover:underline">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            <a href="{{ route('login') }}">{{ __('forgot_password.back_to_login') }}</a>
        </div>
    </div>
</div>

<div class="bg-panel-right-gradient rounded-3xl w-1/2 min-h-[70vh] lg:block hidden"></div>
@endsection