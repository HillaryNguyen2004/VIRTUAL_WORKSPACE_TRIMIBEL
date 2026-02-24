@extends('layout_dashboard')
@section('title', __('app.online_documents'))

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div>
            <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('online_docs.title') }}</h2>
            <p class="text-muted-500 text-sm mt-1">{{ __('online_docs.subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-muted-200 shadow-lg shadow-main/5">
                <p class="text-sm font-semibold text-main">{{ __('online_docs.docs_label') }}</p>
                <p class="text-xs text-muted-500">{{ __('online_docs.title') }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-muted-200 shadow-lg shadow-main/5 opacity-60">
                <p class="text-sm font-semibold text-main">{{ __('online_docs.excel_label') }}</p>
                <p class="text-xs text-muted-500">{{ __('online_docs.coming_soon') }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-muted-200 shadow-lg shadow-main/5 opacity-60">
                <p class="text-sm font-semibold text-main">{{ __('online_docs.powerpoint_label') }}</p>
                <p class="text-xs text-muted-500">{{ __('online_docs.coming_soon') }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5">
            <form method="POST" action="{{ route('online-docs.docs.store') }}" class="flex flex-col gap-4">
                @csrf
                <div class="flex flex-col sm:flex-row gap-3">
                    <input
                        type="text"
                        name="title"
                        required
                        class="flex-1 rounded-xl border border-muted-200 px-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        placeholder="{{ __('online_docs.new_doc_title') }}"
                    />
                    <button type="submit" class="px-5 py-2 rounded-xl bg-secondary text-white font-medium hover:bg-secondary/90 transition-colors">
                        {{ __('online_docs.create_doc') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5">
                <h3 class="text-lg font-semibold text-main mb-4">{{ __('online_docs.owned_docs') }}</h3>
                @forelse($ownedDocuments as $document)
                    <div class="flex items-center justify-between py-2 border-b border-muted-100 last:border-b-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-main truncate" title="{{ $document->title }}">{{ $document->title }}</p>
                            <p class="text-xs text-muted-400">{{ __('online_docs.owner') }}: {{ $document->owner->name ?? $document->owner->email ?? '—' }}</p>
                        </div>
                        <a href="{{ route('online-docs.docs.show', $document) }}" class="text-sm text-primary hover:text-primary-hover font-medium">
                            {{ __('online_docs.open') }}
                        </a>
                    </div>
                @empty
                    <p class="text-sm text-muted-400">{{ __('online_docs.empty_owned') }}</p>
                @endforelse
            </div>

            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5">
                <h3 class="text-lg font-semibold text-main mb-4">{{ __('online_docs.shared_docs') }}</h3>
                @forelse($sharedDocuments as $document)
                    <div class="flex items-center justify-between py-2 border-b border-muted-100 last:border-b-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-main truncate" title="{{ $document->title }}">{{ $document->title }}</p>
                            <p class="text-xs text-muted-400">{{ __('online_docs.owner') }}: {{ $document->owner->name ?? $document->owner->email ?? '—' }}</p>
                        </div>
                        <a href="{{ route('online-docs.docs.show', $document) }}" class="text-sm text-primary hover:text-primary-hover font-medium">
                            {{ __('online_docs.open') }}
                        </a>
                    </div>
                @empty
                    <p class="text-sm text-muted-400">{{ __('online_docs.empty_shared') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
