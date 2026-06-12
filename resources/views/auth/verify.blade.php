@extends('layout_login')
@section('title', __('verification.page_title'))
@section('content')
    <div class="h-full w-full">
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

        <!-- Content -->
        <div class="flex flex-col items-center justify-center w-full h-full pb-10 m-auto">
            <h1 class="alert alert-info text-xl md:text-3xl font-medium text-gray-900 text-center">
                {{ __('verification.check_email_prompt') }}
            </h1>
            
            @if (session('resent'))
                <h1 class="alert alert-info text-xl md:text-3xl font-medium text-gray-900 text-center mt-4">
                    {{ __('verification.success_alert') }}
                </h1>
            @endif


            
            <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <div>{{ __('verification.did_not_receive') }},</div>
                <button type="submit" class="flex gap-2 mt-4 text-center items-center text-xs md:text-sm hover:underline text-gray-500">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    {{ __('verification.request_another') }}
                </button>
            </form>

            
        </div>
    </div>
@endsection
<!-- @extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('verification.page_title') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('verification.success_alert') }}
                        </div>
                    @endif

                    {{ __('verification.check_email_prompt') }}
                    {{ __('verification.did_not_receive') }},
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('verification.request_another') }}</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection -->