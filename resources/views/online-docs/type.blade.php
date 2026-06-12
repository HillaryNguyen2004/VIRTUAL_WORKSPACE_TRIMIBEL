@extends('layout_dashboard')
@section('title', $pageTitle)

@section('content')
    @php
        $highlightTitle = function (string $title) use ($searchQuery) {
            $safeTitle = e($title);
            $tokens = preg_split('/\s+/', trim((string) $searchQuery)) ?: [];
            $tokens = array_values(array_unique(array_filter($tokens, static function (string $token): bool {
                return mb_strlen(trim($token)) >= 2;
            })));

            if ($tokens === []) {
                return new \Illuminate\Support\HtmlString($safeTitle);
            }

            $escapedTokens = array_map(static fn (string $token): string => preg_quote(e($token), '/'), $tokens);
            $pattern = '/(' . implode('|', $escapedTokens) . ')/i';

            $parts = preg_split('/(<[^>]+>)/', $safeTitle, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
            foreach ($parts as $index => $part) {
                if ($part === '' || $part[0] === '<') {
                    continue;
                }

                $parts[$index] = preg_replace(
                    $pattern,
                    '<mark class="rounded bg-warning/30 px-0.5 text-main">$1</mark>',
                    $part
                ) ?? $part;
            }

            return new \Illuminate\Support\HtmlString(implode('', $parts));
        };
    @endphp

    @php
        $typeIcon = match($type) {
            'excel'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M3 3h18a0 0 0 010 18H3a0 0 0 010-18z"/>',
            'powerpoint' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 12h5a3 3 0 000-6H7v6zm0 0v6m0-6h10"/>',
            default      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        };
        $typeColor = match($type) {
            'excel'      => 'text-green-600 bg-green-50',
            'powerpoint' => 'text-orange-600 bg-orange-50',
            default      => 'text-primary bg-primary/10',
        };
    @endphp

    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ $pageTitle }}</h2>
                <p class="text-muted-500 text-sm mt-1">{{ $pageSubtitle }}</p>
            </div>
            <a href="{{ route('online-docs.home') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('online_docs.back_all') }}
            </a>
        </div>

        @include('online-docs.partials.home-nav', ['currentType' => $type])

        {{-- Search + Create card --}}
        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-5 flex flex-col gap-4">
            {{-- Search row --}}
            <form method="GET" action="{{ url()->current() }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input
                        type="text"
                        name="q"
                        value="{{ $searchQuery ?? '' }}"
                        class="w-full rounded-xl border border-muted-200 pl-9 pr-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        placeholder="{{ __('online_docs.type_search_placeholder') }}"
                    />
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-hover transition-colors shadow-sm shadow-primary/20">
                    {{ __('online_docs.search_action') }}
                </button>
                @if(!empty($searchQuery))
                    <a href="{{ url()->current() }}" class="px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-600 hover:bg-muted-50 transition-colors text-center">
                        {{ __('online_docs.clear_search') }}
                    </a>
                @endif
            </form>

            @if(!empty($searchQuery))
                <div class="rounded-xl border border-primary/20 bg-primary/5 px-4 py-2.5 text-xs text-primary flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                    </svg>
                    {{ __('online_docs.type_search_results', ['query' => $searchQuery, 'count' => $ownedDocuments->count() + $sharedDocuments->count()]) }}
                </div>
            @endif

            {{-- Create form --}}
            <div class="border-t border-muted-100 pt-4">
                @if($type === 'docs')
                    <form method="POST" action="{{ route($createRouteName) }}" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input
                            type="text"
                            name="title"
                            required
                            class="flex-1 rounded-xl border border-muted-200 px-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                            placeholder="{{ __('online_docs.new_doc_title') }}"
                        />
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-primary text-white font-medium hover:bg-primary-hover transition-colors shadow-sm shadow-primary/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ __('online_docs.create_doc') }}
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route($createRouteName) }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        @csrf
                        <input
                            type="text"
                            name="title"
                            required
                            value="{{ old('title') }}"
                            class="flex-1 rounded-xl border border-muted-200 px-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                            placeholder="{{ __('online_docs.new_doc_title') }}"
                        />
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-primary text-white font-medium hover:bg-primary-hover transition-colors shadow-sm shadow-primary/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $type === 'excel' ? __('online_docs.create_excel') : __('online_docs.create_powerpoint') }}
                        </button>
                    </form>
                    @error('title')
                        <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        </div>

        {{-- Document lists --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Owned docs --}}
            <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 flex flex-col">
                <div class="px-5 py-4 border-b border-muted-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-semibold text-main">{{ __('online_docs.owned_docs') }}</h3>
                    </div>
                    <span class="text-xs text-muted-400">{{ $ownedDocuments->count() }}</span>
                </div>
                <div class="divide-y divide-muted-100 flex-1">
                    @forelse($ownedDocuments as $document)
                        <div class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-muted-50/60 transition-colors last:rounded-b-2xl">
                            <div class="min-w-0 flex items-center gap-3">
                                <span class="shrink-0 inline-flex h-8 w-8 items-center justify-center rounded-lg {{ $typeColor }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        {!! $typeIcon !!}
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-main truncate" title="{{ $document->title }}">{!! $highlightTitle($document->title) !!}</p>
                                    <p class="text-xs text-muted-400 mt-0.5">{{ $document->owner->name ?? $document->owner->email ?? '—' }}</p>
                                    @if(!empty($searchQuery) && isset($document->search_score))
                                        <p class="text-[11px] text-muted-400">{{ __('online_docs.relevance_score') }}: {{ number_format((float) $document->search_score, 2) }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('online-docs.docs.show', $document) }}" class="px-3 py-1.5 rounded-lg bg-primary/5 text-primary text-xs font-medium hover:bg-primary/10 transition-colors">
                                    {{ __('online_docs.open') }}
                                </a>
                                @if(Gate::check('update', $document) || Gate::check('delete', $document))
                                    <div class="relative" data-menu>
                                        <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-muted-200 text-muted-500 hover:bg-muted-100 transition-colors" aria-label="{{ __('online_docs.more_actions') }}">
                                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                <circle cx="4" cy="10" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="16" cy="10" r="1.5"/>
                                            </svg>
                                        </button>
                                        <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
                                            <form method="POST" action="{{ route('online-docs.links.store', $document) }}" class="flex">
                                                @csrf
                                                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-xs text-primary hover:bg-muted-50">
                                                    {{ __('online_docs.add_to_storage') }}
                                                </button>
                                            </form>
                                            @can('update', $document)
                                                <form method="POST" action="{{ route('online-docs.docs.rename', $document) }}" class="mt-1 flex flex-col gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="title" required value="{{ $document->title }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs"/>
                                                    <button type="submit" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200">
                                                        {{ __('online_docs.rename') }}
                                                    </button>
                                                </form>
                                            @endcan
                                            @can('delete', $document)
                                                <form method="POST" action="{{ route('online-docs.docs.delete', $document) }}" class="mt-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="w-full px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20">
                                                        {{ __('online_docs.delete') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-2 px-5 py-10 text-center">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-muted-100 text-muted-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </span>
                            <p class="text-sm text-muted-400">{{ __('online_docs.empty_owned') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Shared docs --}}
            <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 flex flex-col">
                <div class="px-5 py-4 border-b border-muted-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-muted-100 text-muted-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-semibold text-main">{{ __('online_docs.shared_docs') }}</h3>
                    </div>
                    <span class="text-xs text-muted-400">{{ $sharedDocuments->count() }}</span>
                </div>
                <div class="divide-y divide-muted-100 flex-1">
                    @forelse($sharedDocuments as $document)
                        <div class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-muted-50/60 transition-colors last:rounded-b-2xl">
                            <div class="min-w-0 flex items-center gap-3">
                                <span class="shrink-0 inline-flex h-8 w-8 items-center justify-center rounded-lg {{ $typeColor }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        {!! $typeIcon !!}
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-main truncate" title="{{ $document->title }}">{!! $highlightTitle($document->title) !!}</p>
                                    <p class="text-xs text-muted-400 mt-0.5">{{ $document->owner->name ?? $document->owner->email ?? '—' }}</p>
                                    @if(!empty($searchQuery) && isset($document->search_score))
                                        <p class="text-[11px] text-muted-400">{{ __('online_docs.relevance_score') }}: {{ number_format((float) $document->search_score, 2) }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('online-docs.docs.show', $document) }}" class="px-3 py-1.5 rounded-lg bg-primary/5 text-primary text-xs font-medium hover:bg-primary/10 transition-colors">
                                    {{ __('online_docs.open') }}
                                </a>
                                @if(Gate::check('update', $document) || Gate::check('delete', $document))
                                    <div class="relative" data-menu>
                                        <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-muted-200 text-muted-500 hover:bg-muted-100 transition-colors" aria-label="{{ __('online_docs.more_actions') }}">
                                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                <circle cx="4" cy="10" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="16" cy="10" r="1.5"/>
                                            </svg>
                                        </button>
                                        <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
                                            <form method="POST" action="{{ route('online-docs.links.store', $document) }}" class="flex">
                                                @csrf
                                                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-xs text-primary hover:bg-muted-50">
                                                    {{ __('online_docs.add_to_storage') }}
                                                </button>
                                            </form>
                                            @can('update', $document)
                                                <form method="POST" action="{{ route('online-docs.docs.rename', $document) }}" class="mt-1 flex flex-col gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="title" required value="{{ $document->title }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs"/>
                                                    <button type="submit" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200">
                                                        {{ __('online_docs.rename') }}
                                                    </button>
                                                </form>
                                            @endcan
                                            @can('delete', $document)
                                                <form method="POST" action="{{ route('online-docs.docs.delete', $document) }}" class="mt-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="w-full px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20">
                                                        {{ __('online_docs.delete') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-2 px-5 py-10 text-center">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-muted-100 text-muted-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                            </span>
                            <p class="text-sm text-muted-400">{{ __('online_docs.empty_shared') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menus = Array.from(document.querySelectorAll('[data-menu]'));

        const closeAllMenus = () => {
            menus.forEach((menu) => {
                const panel = menu.querySelector('[data-menu-panel]');
                panel?.classList.add('hidden');
            });
        };

        menus.forEach((menu) => {
            const trigger = menu.querySelector('[data-menu-trigger]');
            const panel = menu.querySelector('[data-menu-panel]');
            if (!trigger || !panel) return;

            trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = !panel.classList.contains('hidden');
                closeAllMenus();
                panel.classList.toggle('hidden', isOpen);
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-menu]')) closeAllMenus();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAllMenus();
        });
    });
</script>
@endpush
