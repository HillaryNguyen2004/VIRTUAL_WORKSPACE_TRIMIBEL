@extends('layout_dashboard')
@section('title', __('app.online_documents'))

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('online_docs.title') }}</h2>
                <p class="text-muted-500 text-sm mt-1">{{ __('online_docs.subtitle') }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-600 hover:bg-muted-50">
                {{ __('online_docs.back_home') }}
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="{{ route('online-docs.docs') }}" class="bg-white rounded-2xl p-5 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/40 hover:shadow-primary/10 transition">
                <p class="text-sm font-semibold text-main">{{ __('online_docs.docs_label') }}</p>
                <p class="text-xs text-muted-500">{{ __('online_docs.docs_page_subtitle') }}</p>
            </a>
            <a href="{{ route('online-docs.excel') }}" class="bg-white rounded-2xl p-5 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/40 hover:shadow-primary/10 transition">
                <p class="text-sm font-semibold text-main">{{ __('online_docs.excel_label') }}</p>
                <p class="text-xs text-muted-500">{{ __('online_docs.excel_page_subtitle') }}</p>
            </a>
            <a href="{{ route('online-docs.powerpoint') }}" class="bg-white rounded-2xl p-5 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/40 hover:shadow-primary/10 transition">
                <p class="text-sm font-semibold text-main">{{ __('online_docs.powerpoint_label') }}</p>
                <p class="text-xs text-muted-500">{{ __('online_docs.powerpoint_page_subtitle') }}</p>
            </a>
        </div>
    </div>
@endsection
