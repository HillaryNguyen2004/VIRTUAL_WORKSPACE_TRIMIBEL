@extends('layout_dashboard')
@section('title', $pageTitle)

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ $pageTitle }}</h2>
                <p class="text-muted-500 text-sm mt-1">{{ $pageSubtitle }}</p>
            </div>
            <a href="{{ route('online-docs.home') }}" class="px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-600 hover:bg-muted-50">
                {{ __('online_docs.back_all') }}
            </a>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5">
            @if($type === 'docs')
                <form method="POST" action="{{ route($createRouteName) }}" class="flex flex-col gap-4">
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
            @else
                <form method="POST" action="{{ route($createRouteName) }}">
                    @csrf
                    <button type="submit" class="px-5 py-2 rounded-xl bg-secondary text-white font-medium hover:bg-secondary/90 transition-colors">
                        {{ $type === 'excel' ? __('online_docs.create_excel') : __('online_docs.create_powerpoint') }}
                    </button>
                </form>
            @endif
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
