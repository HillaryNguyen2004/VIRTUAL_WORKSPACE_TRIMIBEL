@extends('layout_dashboard')
@section('title', $pageTitle)

@section('content')
    @php
        $highlightTitle = function (string $title) use ($searchQuery) {
            $safeTitle = e($title);
            $tokens = preg_split('/\s+/', trim((string) $searchQuery)) ?: [];

            foreach ($tokens as $token) {
                $token = trim((string) $token);
                if (mb_strlen($token) < 2) {
                    continue;
                }

                $safeToken = e($token);
                $safeTitle = preg_replace(
                    '/' . preg_quote($safeToken, '/') . '/i',
                    '<mark class="rounded bg-warning/30 px-0.5 text-main">$0</mark>',
                    $safeTitle
                ) ?? $safeTitle;
            }

            return new \Illuminate\Support\HtmlString($safeTitle);
        };
    @endphp

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

        @include('online-docs.partials.home-nav', ['currentType' => $type])

        <div class="bg-white rounded-xl p-5 border border-muted-200">
            <form method="GET" action="{{ url()->current() }}" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <input
                    type="text"
                    name="q"
                    value="{{ $searchQuery ?? '' }}"
                    class="rounded-xl border border-muted-200 px-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="{{ __('online_docs.type_search_placeholder') }}"
                />
                <button type="submit" class="px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                    {{ __('online_docs.search_action') }}
                </button>
                @if(!empty($searchQuery))
                    <a href="{{ url()->current() }}" class="px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-600 hover:bg-muted-50 text-center">
                        {{ __('online_docs.clear_search') }}
                    </a>
                @endif
            </form>

            @if(!empty($searchQuery))
                <div class="mb-4 rounded-lg border border-primary/20 bg-primary/5 px-3 py-2 text-xs text-primary">
                    {{ __('online_docs.type_search_results', ['query' => $searchQuery, 'count' => $ownedDocuments->count() + $sharedDocuments->count()]) }}
                </div>
            @endif

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
                        <button type="submit" class="px-5 py-2 rounded-xl bg-primary text-white font-medium hover:bg-primary-hover transition-colors">
                            {{ __('online_docs.create_doc') }}
                        </button>
                    </div>
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
                    <button type="submit" class="px-5 py-2 rounded-xl bg-primary text-white font-medium hover:bg-primary-hover transition-colors">
                        {{ $type === 'excel' ? __('online_docs.create_excel') : __('online_docs.create_powerpoint') }}
                    </button>
                </form>
                @error('title')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl p-5 border border-muted-200">
                <h3 class="text-lg font-semibold text-main mb-4">{{ __('online_docs.owned_docs') }}</h3>
                @forelse($ownedDocuments as $document)
                    <div class="flex items-center justify-between py-2 border-b border-muted-100 last:border-b-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-main truncate" title="{{ $document->title }}">{!! $highlightTitle($document->title) !!}</p>
                            <p class="text-xs text-muted-400">{{ __('online_docs.owner') }}: {{ $document->owner->name ?? $document->owner->email ?? '—' }}</p>
                            @if(!empty($searchQuery) && isset($document->search_score))
                                <p class="text-[11px] text-muted-500">{{ __('online_docs.relevance_score') }}: {{ number_format((float) $document->search_score, 2) }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('online-docs.docs.show', $document) }}" class="text-sm text-primary hover:text-primary-hover font-medium">
                                {{ __('online_docs.open') }}
                            </a>
                            @if(Gate::check('update', $document) || Gate::check('delete', $document))
                                <div class="relative" data-menu>
                                    <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                            <circle cx="4" cy="10" r="1.5" />
                                            <circle cx="10" cy="10" r="1.5" />
                                            <circle cx="16" cy="10" r="1.5" />
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
                                            <form method="POST" action="{{ route('online-docs.docs.rename', $document) }}" class="flex flex-col gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input
                                                    type="text"
                                                    name="title"
                                                    required
                                                    value="{{ $document->title }}"
                                                    class="rounded-lg border border-muted-200 px-2 py-1 text-xs"
                                                />
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
                    <p class="text-sm text-muted-400">{{ __('online_docs.empty_owned') }}</p>
                @endforelse
            </div>

            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5">
                <h3 class="text-lg font-semibold text-main mb-4">{{ __('online_docs.shared_docs') }}</h3>
                @forelse($sharedDocuments as $document)
                    <div class="flex items-center justify-between py-2 border-b border-muted-100 last:border-b-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-main truncate" title="{{ $document->title }}">{!! $highlightTitle($document->title) !!}</p>
                            <p class="text-xs text-muted-400">{{ __('online_docs.owner') }}: {{ $document->owner->name ?? $document->owner->email ?? '—' }}</p>
                            @if(!empty($searchQuery) && isset($document->search_score))
                                <p class="text-[11px] text-muted-500">{{ __('online_docs.relevance_score') }}: {{ number_format((float) $document->search_score, 2) }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('online-docs.docs.show', $document) }}" class="text-sm text-primary hover:text-primary-hover font-medium">
                                {{ __('online_docs.open') }}
                            </a>
                            @if(Gate::check('update', $document) || Gate::check('delete', $document))
                                <div class="relative" data-menu>
                                    <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                            <circle cx="4" cy="10" r="1.5" />
                                            <circle cx="10" cy="10" r="1.5" />
                                            <circle cx="16" cy="10" r="1.5" />
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
                                            <form method="POST" action="{{ route('online-docs.docs.rename', $document) }}" class="flex flex-col gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input
                                                    type="text"
                                                    name="title"
                                                    required
                                                    value="{{ $document->title }}"
                                                    class="rounded-lg border border-muted-200 px-2 py-1 text-xs"
                                                />
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
                    <p class="text-sm text-muted-400">{{ __('online_docs.empty_shared') }}</p>
                @endforelse
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
            if (!trigger || !panel) {
                return;
            }

            trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = !panel.classList.contains('hidden');
                closeAllMenus();
                panel.classList.toggle('hidden', isOpen);
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-menu]')) {
                closeAllMenus();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAllMenus();
            }
        });
    });
</script>
@endpush
