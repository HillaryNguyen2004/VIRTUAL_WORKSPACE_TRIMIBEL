@extends('layout_dashboard')
@section('title', __('app.online_documents'))

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('online_docs.title') }}</h2>
                <p class="text-muted-500 text-sm mt-1">{{ __('online_docs.subtitle') }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                {{ __('online_docs.back_home') }}
            </a>
        </div>

        @include('online-docs.partials.home-nav', ['currentType' => null])

        @php
            $typeLabels = [
                'docs' => __('online_docs.docs_label'),
                'excel' => __('online_docs.excel_label'),
                'powerpoint' => __('online_docs.powerpoint_label'),
            ];
            $isStorageEmpty = $folders->isEmpty() && $files->isEmpty() && $links->isEmpty();
            $highlightText = function (string $text, string $query) {
                $safe = e($text);
                $tokens = preg_split('/\s+/', trim($query)) ?: [];
                $tokens = array_values(array_unique(array_filter($tokens, static function (string $token): bool {
                    return mb_strlen(trim($token)) >= 2;
                })));

                if ($tokens === []) {
                    return new \Illuminate\Support\HtmlString($safe);
                }

                $escapedTokens = array_map(static fn (string $token): string => preg_quote(e($token), '/'), $tokens);
                $pattern = '/(' . implode('|', $escapedTokens) . ')/i';

                $parts = preg_split('/(<[^>]+>)/', $safe, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
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

        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-5">
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                    </svg>
                </span>
                <h3 class="text-sm font-semibold text-main">{{ __('online_docs.global_search_title') }}</h3>
            </div>
            <p class="text-xs text-muted-400 mb-4">{{ __('online_docs.global_search_hint') }}</p>

            <form method="GET" action="{{ route('online-docs.home') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input
                        type="text"
                        name="doc_query"
                        value="{{ $globalSearchQuery ?? '' }}"
                        class="w-full rounded-xl border border-muted-200 pl-9 pr-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        placeholder="{{ __('online_docs.global_search_placeholder') }}"
                    />
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-hover transition-colors shadow-sm shadow-primary/20">
                    {{ __('online_docs.search_action') }}
                </button>
                @if(!empty($globalSearchQuery))
                    <a href="{{ route('online-docs.home') }}" class="px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-600 hover:bg-muted-50 transition-colors text-center">
                        {{ __('online_docs.clear_search') }}
                    </a>
                @endif
            </form>

            @if(!empty($globalSearchQuery))
                <div class="mt-3 rounded-xl border border-primary/20 bg-primary/5 px-4 py-2.5 text-xs text-primary flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                    </svg>
                    {{ __('online_docs.global_search_results', ['query' => $globalSearchQuery, 'count' => $globalSearchResults->count() + $personalSearchResults->count()]) }}
                </div>

                <div class="mt-4 flex flex-col gap-3">
                    @forelse($globalSearchResults as $document)
                        <div class="rounded-xl border border-muted-100 px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-main truncate" title="{{ $document->title }}">{!! $highlightText($document->title, (string) $globalSearchQuery) !!}</p>
                                    <p class="text-xs text-muted-400 mt-0.5">
                                        {{ $typeLabels[$document->type] ?? $document->type }}
                                        · {{ __('online_docs.owner') }}: {{ $document->owner->name ?? $document->owner->email ?? '—' }}
                                        · {{ __('online_docs.relevance_score') }}: {{ number_format((float) ($document->search_score ?? 0), 2) }}
                                    </p>
                                    @if(!empty($document->search_match_page) && !empty($document->search_match_line))
                                        <p class="text-[11px] text-muted-500 mt-1">
                                            @if($document->search_match_is_indexed)
                                                {{ __('online_docs.match_position_indexed', ['page' => $document->search_match_page, 'line' => $document->search_match_line]) }}
                                            @else
                                                {{ __('online_docs.best_match_position', ['page' => $document->search_match_page, 'line' => $document->search_match_line]) }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                <a href="{{ route('online-docs.docs.show', $document) }}" class="text-xs font-medium text-primary hover:text-primary-hover shrink-0">
                                    {{ __('online_docs.open') }}
                                </a>
                            </div>

                            @if(!empty($document->search_match_snippet))
                                <p class="mt-2 text-xs text-muted-500 line-clamp-2">
                                    {!! $highlightText((string) $document->search_match_snippet, (string) $globalSearchQuery) !!}
                                </p>
                            @elseif(!empty($document->searchable_text))
                                <p class="mt-2 text-xs text-muted-500 line-clamp-2">
                                    {!! $highlightText(\Illuminate\Support\Str::limit((string) $document->searchable_text, 220), (string) $globalSearchQuery) !!}
                                </p>
                            @endif
                        </div>
                    @empty
                        @if($personalSearchResults->isEmpty())
                            <p class="text-sm text-muted-400">{{ __('online_docs.global_search_empty') }}</p>
                        @endif
                    @endforelse

                    @foreach($personalSearchResults as $file)
                        @php
                            $fileExtension = strtolower(pathinfo((string) $file->original_name, PATHINFO_EXTENSION));
                            $isOfficeFile = in_array($fileExtension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);
                        @endphp
                        <div class="rounded-xl border border-muted-100 px-4 py-3 bg-muted-50/40">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-main truncate" title="{{ $file->original_name }}">{!! $highlightText($file->original_name, (string) $globalSearchQuery) !!}</p>
                                    <p class="text-xs text-muted-400 mt-0.5">
                                        {{ __('online_docs.file') }}
                                        · {{ strtoupper($fileExtension ?: 'FILE') }}
                                        · {{ __('online_docs.relevance_score') }}: {{ number_format((float) ($file->search_score ?? 0), 2) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if($isOfficeFile)
                                        <a href="{{ route('online-docs.files.open', ['file' => $file]) }}" class="text-xs font-medium text-primary hover:text-primary-hover">
                                            {{ __('online_docs.open') }}
                                        </a>
                                    @else
                                        <a href="{{ route('online-docs.files.preview', ['file' => $file]) }}" class="text-xs font-medium text-primary hover:text-primary-hover">
                                            {{ __('online_docs.preview') }}
                                        </a>
                                    @endif
                                    <a href="{{ route('online-docs.files.download', ['file' => $file]) }}" class="text-xs font-medium text-primary hover:text-primary-hover">
                                        {{ __('online_docs.download') }}
                                    </a>
                                </div>
                            </div>

                            @if(!empty($file->search_match_snippet))
                                <p class="mt-2 text-xs text-muted-500 line-clamp-2">
                                    {!! $highlightText((string) $file->search_match_snippet, (string) $globalSearchQuery) !!}
                                </p>
                            @elseif(!empty($file->searchable_text))
                                <p class="mt-2 text-xs text-muted-500 line-clamp-2">
                                    {!! $highlightText(\Illuminate\Support\Str::limit((string) $file->searchable_text, 220), (string) $globalSearchQuery) !!}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- AI Search Agent Panel --}}
        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-5" id="ai-search-agent">
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </span>
                <h3 class="text-sm font-semibold text-main">{{ __('online_docs.search_agent_title') }}</h3>
            </div>
            <p class="text-xs text-muted-400 mb-4">{{ __('online_docs.search_agent_hint') }}</p>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]">
                <input
                    type="text"
                    id="agent-query-input"
                    class="rounded-xl border border-muted-200 px-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="{{ __('online_docs.search_agent_placeholder') }}"
                />
                <button
                    type="button"
                    id="agent-ask-btn"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-hover transition-colors shadow-sm shadow-primary/20 disabled:opacity-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    {{ __('online_docs.search_agent_ask') }}
                </button>
            </div>

            <div id="agent-results" class="mt-4 hidden">
                <div id="agent-loading" class="hidden items-center gap-2 py-3">
                    <div class="w-4 h-4 rounded-full border-2 border-primary/20 border-t-primary animate-spin shrink-0"></div>
                    <span class="text-xs text-muted-500">{{ __('online_docs.search_agent_loading') }}</span>
                </div>
                <div id="agent-error" class="hidden rounded-xl border border-danger/20 bg-danger/5 text-danger text-xs px-4 py-3"></div>

                <div id="agent-answer-block" class="hidden">
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[11px] font-semibold text-muted-400 uppercase tracking-wider">{{ __('online_docs.search_agent_answer') }}</p>
                            <button id="agent-copy-btn" type="button" class="inline-flex items-center gap-1.5 text-xs text-primary hover:text-primary-hover font-medium">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                <span id="agent-copy-label">{{ __('online_docs.search_agent_copy') }}</span>
                            </button>
                        </div>
                        <div id="agent-answer-text" class="text-sm text-main leading-relaxed bg-muted-50 rounded-xl px-4 py-3 border border-muted-100 prose prose-sm max-w-none"></div>
                        <div id="agent-confidence" class="mt-1.5 text-[11px] text-muted-400"></div>
                    </div>

                    <div id="agent-citations-block" class="hidden">
                        <p class="text-[11px] font-semibold text-muted-400 uppercase tracking-wider mb-2">{{ __('online_docs.search_agent_sources') }}</p>
                        <div id="agent-citations-list" class="flex flex-col gap-2"></div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-muted-100">
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto]">
                            <input
                                type="text"
                                id="agent-followup-input"
                                class="rounded-xl border border-muted-200 px-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                                placeholder="{{ __('online_docs.search_agent_followup') }}"
                            />
                            <button
                                type="button"
                                id="agent-followup-btn"
                                class="px-4 py-2 rounded-xl border border-muted-200 bg-muted-50 text-main text-sm font-medium hover:bg-muted-100 transition-colors disabled:opacity-50"
                            >
                                {{ __('online_docs.search_agent_ask') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent docs --}}
        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-hidden">
            <div class="px-5 py-4 border-b border-muted-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-main">{{ __('online_docs.recent_docs') }}</h3>
                        <p class="text-[11px] text-muted-400">{{ __('online_docs.recent_docs_hint') }}</p>
                    </div>
                </div>
            </div>
            <div class="divide-y divide-muted-100">
                @forelse($recentDocuments as $document)
                    <div class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-muted-50/60 transition-colors" draggable="true" data-doc-drag data-doc-id="{{ $document->id }}" data-doc-title="{{ $document->title }}">
                        <div class="min-w-0 flex items-center gap-3">
                            <span class="shrink-0 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-muted-100 text-muted-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-main truncate">{{ $document->title }}</p>
                                <p class="text-xs text-muted-400">{{ $typeLabels[$document->type] ?? $document->type }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-xs text-muted-400 hidden sm:block">{{ $document->updated_at?->diffForHumans() }}</span>
                            <a href="{{ route('online-docs.docs.show', $document) }}" class="px-3 py-1.5 rounded-lg bg-primary/5 text-primary text-xs font-medium hover:bg-primary/10 transition-colors">
                                {{ __('online_docs.open') }}
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center gap-2 px-5 py-10 text-center">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-muted-100 text-muted-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <p class="text-sm text-muted-400">{{ __('online_docs.empty_recent') }}</p>
                    </div>
                @endforelse
            </div>
            @if($recentDocuments->hasPages())
                <div class="px-5 py-3 border-t border-muted-100 bg-muted-50/50 flex items-center justify-between gap-3">
                    <span class="text-xs text-muted-400">
                        {{ $recentDocuments->firstItem() }}–{{ $recentDocuments->lastItem() }} / {{ $recentDocuments->total() }}
                    </span>
                    <div class="flex items-center gap-1.5">
                        @if($recentDocuments->onFirstPage())
                            <span class="px-3 py-1.5 rounded-lg border border-muted-200 text-xs text-muted-300">Prev</span>
                        @else
                            <a href="{{ $recentDocuments->appends(request()->except('recent_page'))->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-muted-200 text-xs text-muted-600 hover:bg-muted-50 transition-colors">Prev</a>
                        @endif
                        <span class="px-2 py-1 text-xs text-muted-500">{{ $recentDocuments->currentPage() }}/{{ $recentDocuments->lastPage() }}</span>
                        @if($recentDocuments->hasMorePages())
                            <a href="{{ $recentDocuments->appends(request()->except('recent_page'))->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-muted-200 text-xs text-muted-600 hover:bg-muted-50 transition-colors">Next</a>
                        @else
                            <span class="px-3 py-1.5 rounded-lg border border-muted-200 text-xs text-muted-300">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-hidden">
            <div class="px-5 py-4 border-b border-muted-100 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-muted-100 text-muted-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </span>
                    <h3 class="text-sm font-semibold text-main">{{ __('online_docs.personal_storage') }}</h3>
                </div>
                <div class="rounded-xl border border-muted-200 bg-muted-50 px-3 py-2">
                    <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap text-xs text-muted-500">
                        <a href="{{ route('online-docs.home') }}" class="rounded-md bg-white px-2 py-0.5 font-medium text-main border border-muted-200 hover:text-primary">{{ __('online_docs.storage_root') }}</a>
                    @foreach($folderBreadcrumbs as $breadcrumb)
                        <span class="text-muted-300">/</span>
                        <a href="{{ route('online-docs.home', ['folder' => $breadcrumb['id']]) }}" class="rounded-md bg-white px-2 py-0.5 text-muted-600 hover:text-primary border border-muted-200">
                            {{ $breadcrumb['name'] }}
                        </a>
                    @endforeach
                    </div>
                </div>
            </div>

            @if(session('storage_error'))
                <div class="mx-5 mt-4 rounded-xl border border-danger/20 bg-danger/5 text-danger text-xs px-4 py-3">
                    {{ session('storage_error') }}
                </div>
            @endif
            @if(session('storage_success'))
                <div class="mx-5 mt-4 rounded-xl border border-success/20 bg-success/5 text-success text-xs px-4 py-3">
                    {{ session('storage_success') }}
                </div>
            @endif
            @if(session('storage_warning'))
                <div class="mx-5 mt-4 rounded-xl border border-warning/20 bg-warning/5 text-warning text-xs px-4 py-3">
                    {{ session('storage_warning') }}
                </div>
            @endif

            {{-- Ingest banner --}}
            @if($pendingIngestCount > 0)
                <div class="mx-5 mt-4 rounded-xl border border-primary/20 bg-primary/5 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-primary">{{ __('online_docs.ingest_panel_title') }}</p>
                            <p class="text-xs text-primary/70 mt-1">
                                {{ __('online_docs.ingest_panel_desc', ['count' => $pendingIngestCount]) }}
                                @if($failedIngestCount > 0)
                                    <span class="ml-1 text-danger">({{ __('online_docs.ingest_failed_count_label', ['count' => $failedIngestCount]) }})</span>
                                @endif
                            </p>
                        </div>
                        <form id="ingest-all-form" action="{{ route('online-docs.files.ingest-all') }}" method="POST" class="shrink-0">
                            @csrf
                            <button id="ingest-all-btn" type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-white text-xs font-medium hover:bg-primary-hover transition-colors shadow-sm shadow-primary/20">
                                <svg id="ingest-all-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                {{ __('online_docs.start_ingest') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div
                id="storage-root"
                class="flex flex-col gap-4 p-5"
                data-current-folder="{{ $currentFolder?->id }}"
                data-upload-url="{{ route('online-docs.files.store') }}"
                data-move-url="{{ route('online-docs.storage.move') }}"
                data-bulk-move-url="{{ route('online-docs.storage.bulk-move') }}"
                data-bulk-delete-url="{{ route('online-docs.storage.bulk-delete') }}"
                data-link-base-url="{{ url('/online-docs/links') }}"
                data-csrf="{{ csrf_token() }}"
                data-confirm-delete-selected="{{ __('online_docs.confirm_delete_selected') }}"
                data-items-count-template="{{ __('online_docs.items_count') }}"
                data-uploading-template="{{ __('online_docs.uploading_template') }}"
                data-upload-done="{{ __('online_docs.upload_done') }}"
                data-upload-failed-template="{{ __('online_docs.upload_failed_template') }}"
                data-upload-conflict-prompt="{{ __('online_docs.upload_conflict_prompt') }}"
                data-folder-conflict-prompt="{{ __('online_docs.folder_conflict_prompt') }}"
                data-conflict-modal-title-file="{{ __('online_docs.conflict_modal_title_file') }}"
                data-conflict-modal-title-folder="{{ __('online_docs.conflict_modal_title_folder') }}"
                data-conflict-modal-name-label="{{ __('online_docs.conflict_modal_name_label') }}"
                data-conflict-modal-cancel="{{ __('online_docs.conflict_modal_cancel') }}"
                data-conflict-modal-save-name="{{ __('online_docs.conflict_modal_save_name') }}"
                data-conflict-modal-auto-rename="{{ __('online_docs.conflict_modal_auto_rename') }}"
                data-conflict-modal-replace="{{ __('online_docs.conflict_modal_replace') }}"
                data-conflict-modal-folder-help="{{ __('online_docs.conflict_modal_folder_help') }}"
                data-conflict-modal-file-help="{{ __('online_docs.conflict_modal_file_help') }}"
                data-conflict-modal-cancelled="{{ __('online_docs.conflict_modal_cancelled') }}"
                data-bulk-delete-done="{{ __('online_docs.bulk_delete_done') }}"
                data-copy-link-done="{{ __('online_docs.copy_share_link_done') }}"
                data-copy-link-failed="{{ __('online_docs.copy_share_link_failed') }}"
            >
                <div class="rounded-xl border border-muted-200 bg-muted-50/60 p-3 space-y-3">
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative" data-storage-new-menu>
                                @if($storageCanEdit)
                                <button
                                    type="button"
                                    data-storage-new-toggle
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-hover"
                                >
                                    <span>+</span>
                                    <span>{{ __('online_docs.new_item') }}</span>
                                </button>
                                <div
                                    data-storage-new-panel
                                    class="absolute left-0 z-20 mt-2 hidden min-w-[180px] rounded-xl border border-muted-200 bg-white p-2 shadow-lg"
                                >
                                    <button type="button" data-storage-action="new-folder" class="w-full rounded-lg px-3 py-2 text-left text-sm text-main hover:bg-muted-50">
                                        {{ __('online_docs.create_folder') }}
                                    </button>
                                    <button type="button" data-storage-action="upload-file" class="w-full rounded-lg px-3 py-2 text-left text-sm text-main hover:bg-muted-50">
                                        {{ __('online_docs.upload_file') }}
                                    </button>
                                </div>
                                @endif
                            </div>
                            <a
                                href="{{ route('online-docs.home', $currentFolder?->id ? ['folder' => $currentFolder->id] : []) }}"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-muted-200 bg-white text-muted-600 hover:bg-muted-50"
                                aria-label="{{ __('online_docs.refresh') }}"
                                title="{{ __('online_docs.refresh') }}"
                            >
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 12a9 9 0 1 1-2.64-6.36" />
                                    <path d="M21 3v6h-6" />
                                </svg>
                            </a>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if($storageCanEdit)
                                <button type="button" data-storage-select-all class="px-3 py-2 rounded-xl border border-muted-200 bg-white text-sm text-muted-600 hover:bg-muted-50">
                                    {{ __('online_docs.select_all') }}
                                </button>
                                <button type="button" data-storage-clear-selection class="px-3 py-2 rounded-xl border border-muted-200 bg-white text-sm text-muted-600 hover:bg-muted-50">
                                    {{ __('online_docs.clear_selection') }}
                                </button>
                            @endif
                            <span class="rounded-xl border border-muted-200 bg-white px-3 py-2 text-xs text-muted-500" data-storage-results-count></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-2 xl:grid-cols-12">
                        <input
                            type="text"
                            id="storage-search"
                            class="xl:col-span-5 rounded-xl border border-muted-200 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                            placeholder="{{ __('online_docs.search_placeholder') }}"
                        />
                        <select class="xl:col-span-2 rounded-xl border border-muted-200 px-3 py-2 text-sm text-muted-700" data-storage-filter>
                            <option value="all">{{ __('online_docs.filter_all') }}</option>
                            <option value="folder">{{ __('online_docs.folder') }}</option>
                            <option value="file">{{ __('online_docs.file') }}</option>
                            <option value="link">{{ __('online_docs.document_link') }}</option>
                        </select>
                        <select class="xl:col-span-3 rounded-xl border border-muted-200 px-3 py-2 text-sm text-muted-700" data-storage-sort>
                            <option value="recent">{{ __('online_docs.sort_recent') }}</option>
                            <option value="name_asc">{{ __('online_docs.sort_name_asc') }}</option>
                            <option value="name_desc">{{ __('online_docs.sort_name_desc') }}</option>
                            <option value="size_desc">{{ __('online_docs.sort_size_desc') }}</option>
                            <option value="size_asc">{{ __('online_docs.sort_size_asc') }}</option>
                        </select>
                        <div class="xl:col-span-2 flex items-center gap-2 rounded-xl border border-muted-200 bg-white p-1">
                            <button type="button" class="w-1/2 px-3 py-1 text-xs rounded-lg bg-muted-100 text-muted-700" data-view-toggle="grid">{{ __('online_docs.view_grid') }}</button>
                            <button type="button" class="w-1/2 px-3 py-1 text-xs rounded-lg text-muted-700" data-view-toggle="list">{{ __('online_docs.view_list') }}</button>
                        </div>
                    </div>
                </div>

                <form id="storage-folder-form" method="POST" action="{{ route('online-docs.folders.store') }}" class="hidden items-center gap-2 rounded-xl border border-muted-200 bg-white px-3 py-2">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $currentFolder?->id }}" />
                    <input
                        type="text"
                        name="name"
                        required
                        class="flex-1 rounded-lg border border-muted-200 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                        placeholder="{{ __('online_docs.folder_name_placeholder') }}"
                    />
                    <button type="submit" class="px-3 py-2 rounded-lg bg-primary text-white hover:bg-primary-hover text-sm font-medium">
                        {{ __('online_docs.create_folder') }}
                    </button>
                    <button type="button" data-storage-folder-cancel class="px-3 py-2 rounded-lg border border-muted-200 text-sm text-muted-600 hover:bg-muted-50">
                        {{ __('online_docs.cancel') }}
                    </button>
                </form>

                <form id="storage-upload-form" method="POST" action="{{ route('online-docs.files.store') }}" enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="hidden" name="folder_id" value="{{ $currentFolder?->id }}" />
                    <input id="storage-upload-input" type="file" name="file" required multiple class="hidden" data-storage-upload-input />
                </form>

                @if($storageCanEdit)
                    <div id="storage-toolbar" class="hidden flex-wrap items-center gap-2 rounded-xl border border-muted-200 bg-muted-50 px-3 py-2" data-selected-label="{{ __('online_docs.selected_label') }}">
                        <span class="text-xs text-muted-600" data-selected-count>0</span>
                        <select class="rounded-lg border border-muted-200 px-2 py-1 text-xs" data-bulk-move-target>
                            <option value="">{{ __('online_docs.move_to_root') }}</option>
                            @foreach($allFolders as $folder)
                                <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200" data-bulk-move>
                            {{ __('online_docs.move_selected') }}
                        </button>
                        <button type="button" class="px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20" data-bulk-delete>
                            {{ __('online_docs.delete_selected') }}
                        </button>
                    </div>
                @endif

                <div class="relative" id="storage-dropzone" data-dropzone>
                    <div class="absolute inset-0 hidden items-center justify-center rounded-2xl border-2 border-dashed border-primary/40 bg-primary/5 text-sm text-primary" data-drop-overlay>
                        {{ __('online_docs.drop_to_upload') }}
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4" data-view="grid" data-items-container>
                        @foreach($folders as $folder)
                            <div class="storage-item group rounded-2xl border border-muted-100 bg-white p-4 shadow-sm" data-item-type="folder" data-item-id="{{ $folder->id }}" data-item-name="{{ $folder->name }}" data-item-updated="{{ $folder->updated_at?->timestamp ?? 0 }}" data-item-size="0" data-folder-drop draggable="true">
                                <div class="flex items-start justify-between gap-2">
                                    <label class="flex items-center gap-2 text-sm text-muted-500">
                                        @if($folder->user_id === auth()->id())
                                            <input type="checkbox" class="storage-select" data-item-type="folder" data-item-id="{{ $folder->id }}" />
                                        @endif
                                        <span>{{ __('online_docs.folder') }}</span>
                                        @if($folder->user_id !== auth()->id())
                                            <span class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium text-primary">{{ __('online_docs.shared_with_me') }}</span>
                                        @endif
                                    </label>
                                    @if($folder->user_id === auth()->id())
                                        <div class="relative" data-menu>
                                            <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                    <circle cx="4" cy="10" r="1.5" />
                                                    <circle cx="10" cy="10" r="1.5" />
                                                    <circle cx="16" cy="10" r="1.5" />
                                                </svg>
                                            </button>
                                            <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
                                                <form method="POST" action="{{ route('online-docs.folders.share', $folder) }}" class="mb-2 flex flex-col gap-2 border-b border-muted-100 pb-2">
                                                    @csrf
                                                    <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                    <input type="email" name="email" required placeholder="{{ __('online_docs.share_folder_email') }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                                                    <select name="permission" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                                        <option value="view">{{ __('online_docs.share_view') }}</option>
                                                        <option value="edit">{{ __('online_docs.share_edit') }}</option>
                                                    </select>
                                                    <button type="submit" class="px-3 py-1 rounded-lg bg-primary/10 text-primary text-xs hover:bg-primary/20">
                                                        {{ __('online_docs.share_folder') }}
                                                    </button>
                                                    <button type="button" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200" data-folder-share-link data-folder-share-link-url="{{ route('online-docs.folders.share.link', $folder) }}">
                                                        {{ __('online_docs.copy_share_link') }}
                                                    </button>
                                                </form>
                                                <div class="mb-2 border-b border-muted-100 pb-2">
                                                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-muted-400">{{ __('online_docs.shared_list') }}</p>
                                                    @forelse($folder->shares as $share)
                                                        <div class="mb-1 flex items-center justify-between gap-2 rounded-lg bg-muted-50 px-2 py-1">
                                                            <span class="truncate text-xs text-muted-700">{{ $share->user?->email }}</span>
                                                            <span class="text-[11px] text-muted-500 shrink-0">{{ $share->permission }}</span>
                                                            <form method="POST" action="{{ route('online-docs.folders.share.remove', ['folder' => $folder, 'share' => $share]) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                                <button type="submit" class="text-[11px] text-danger hover:text-danger/80">{{ __('online_docs.stop_sharing') }}</button>
                                                            </form>
                                                        </div>
                                                    @empty
                                                        <p class="text-xs text-muted-400">{{ __('online_docs.no_folder_shares') }}</p>
                                                    @endforelse
                                                </div>
                                            <form method="POST" action="{{ route('online-docs.folders.update', $folder) }}" class="flex flex-col gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                <input type="text" name="name" required value="{{ $folder->name }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                                                <button type="submit" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200">
                                                    {{ __('online_docs.rename') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('online-docs.folders.delete', $folder) }}" class="mt-2">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                <button type="submit" class="w-full px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20">
                                                    {{ __('online_docs.delete') }}
                                                </button>
                                            </form>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('online-docs.home', ['folder' => $folder->id]) }}" class="mt-3 block text-sm font-semibold text-main truncate group-hover:text-primary">
                                    {{ $folder->name }}
                                </a>
                                <p class="text-xs text-muted-400 mt-1">{{ $folder->updated_at?->diffForHumans() }}</p>
                            </div>
                        @endforeach

                        @foreach($files as $file)
                            @php
                                $fileExtension = strtolower(pathinfo((string) $file->original_name, PATHINFO_EXTENSION));
                                $isOfficeFile = in_array($fileExtension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);
                                $openUrl = $isOfficeFile
                                    ? route('online-docs.files.open', ['file' => $file])
                                    : route('online-docs.files.preview', $file);
                            @endphp
                            <div class="storage-item rounded-2xl border border-muted-100 bg-white p-4 shadow-sm" data-item-type="file" data-item-id="{{ $file->id }}" data-item-name="{{ $file->original_name }}" data-item-updated="{{ $file->updated_at?->timestamp ?? 0 }}" data-item-size="{{ $file->size ?? 0 }}" draggable="true">
                                <div class="flex items-start justify-between gap-2">
                                    <label class="flex items-center gap-2 text-sm text-muted-500">
                                        @if($storageCanEdit)
                                            <input type="checkbox" class="storage-select" data-item-type="file" data-item-id="{{ $file->id }}" />
                                        @endif
                                        <span>{{ __('online_docs.file') }}</span>
                                    </label>
                                    <div class="relative" data-menu>
                                        <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                <circle cx="4" cy="10" r="1.5" />
                                                <circle cx="10" cy="10" r="1.5" />
                                                <circle cx="16" cy="10" r="1.5" />
                                            </svg>
                                        </button>
                                        <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
                                            <a href="{{ $openUrl }}" class="block rounded-lg px-3 py-2 text-xs text-primary hover:bg-muted-50">
                                                {{ __('online_docs.preview') }}
                                            </a>
                                            <a href="{{ route('online-docs.files.download', $file) }}" class="block rounded-lg px-3 py-2 text-xs text-primary hover:bg-muted-50">
                                                {{ __('online_docs.download') }}
                                            </a>
                                            @if($storageCanEdit)
                                                <form method="POST" action="{{ route('online-docs.files.update', $file) }}" class="mt-2 flex flex-col gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                    <input type="text" name="name" required value="{{ $file->original_name }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                                                    <button type="submit" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200">
                                                        {{ __('online_docs.rename') }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('online-docs.files.delete', $file) }}" class="mt-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                    <button type="submit" class="w-full px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20">
                                                        {{ __('online_docs.delete') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ $openUrl }}" class="mt-3 block text-sm font-semibold text-main truncate hover:text-primary">{{ $file->original_name }}</a>
                                <div class="mt-1 flex items-center justify-between gap-2">
                                    <p class="text-xs text-muted-400">{{ number_format($file->size / 1024, 1) }} KB</p>
                                    @php $ingestStatus = $file->ingest_status ?? 'pending'; @endphp
                                    <div class="flex items-center gap-1.5">
                                        @if($ingestStatus === 'pending')
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-medium text-yellow-800">{{ __('online_docs.ingest_status_pending') }}</span>
                                        @elseif($ingestStatus === 'processing')
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-800">{{ __('online_docs.ingest_status_processing') }}</span>
                                        @elseif($ingestStatus === 'completed')
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-800">{{ __('online_docs.ingest_status_completed') }}</span>
                                            @if($file->chunk_count > 0)
                                                <span class="text-[10px] text-muted-400">{{ $file->chunk_count }} {{ __('online_docs.ingest_chunks') }}</span>
                                            @endif
                                        @elseif($ingestStatus === 'failed')
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-800">{{ __('online_docs.ingest_status_failed') }}</span>
                                        @endif
                                        @if(in_array($ingestStatus, ['pending', 'failed', 'processing']))
                                            <form method="POST"
                                                  action="{{ route('online-docs.files.ingest', $file) }}"
                                                  class="inline"
                                                  data-ingest-file-form>
                                                @csrf
                                                <button type="submit"
                                                    title="{{ __('online_docs.retry_ingest') }}"
                                                    class="inline-flex items-center justify-center rounded p-0.5 text-muted-400 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                                                    data-ingest-file-button>
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <polyline points="1 4 1 10 7 10"/>
                                                        <path d="M3.51 15a9 9 0 1 0 .49-3.86L1 10"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                                @if($ingestStatus === 'completed')
                                    <div class="mt-2 flex items-center gap-2">
                                        <button type="button"
                                            data-docs-summarize
                                            data-workspace-id="personal_file_{{ $file->id }}"
                                            data-s3-key="{{ $file->stored_path }}"
                                            data-file-name="{{ $file->original_name }}"
                                            title="Summarize this file"
                                            class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50 px-2 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-100 transition-colors">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Summarize
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @foreach($links as $link)
                            <div class="storage-item rounded-2xl border border-muted-100 bg-white p-4 shadow-sm" data-item-type="link" data-item-id="{{ $link->id }}" data-item-name="{{ $link->name }}" data-item-updated="{{ $link->updated_at?->timestamp ?? 0 }}" data-item-size="0" draggable="true">
                                <div class="flex items-start justify-between gap-2">
                                    <label class="flex items-center gap-2 text-sm text-muted-500">
                                        @if($storageCanEdit)
                                            <input type="checkbox" class="storage-select" data-item-type="link" data-item-id="{{ $link->id }}" />
                                        @endif
                                        <span>{{ __('online_docs.document_link') }}</span>
                                    </label>
                                    <div class="relative" data-menu>
                                        <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                <circle cx="4" cy="10" r="1.5" />
                                                <circle cx="10" cy="10" r="1.5" />
                                                <circle cx="16" cy="10" r="1.5" />
                                            </svg>
                                        </button>
                                        <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
                                            <a href="{{ route('online-docs.docs.show', $link->document) }}" class="block rounded-lg px-3 py-2 text-xs text-primary hover:bg-muted-50">
                                                {{ __('online_docs.open') }}
                                            </a>
                                            @if($storageCanEdit)
                                                <form method="POST" action="{{ route('online-docs.links.update', $link) }}" class="mt-2 flex flex-col gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                    <input type="text" name="name" required value="{{ $link->name }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                                                    <button type="submit" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200">
                                                        {{ __('online_docs.rename') }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('online-docs.links.delete', $link) }}" class="mt-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                    <button type="submit" class="w-full px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20">
                                                        {{ __('online_docs.delete') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('online-docs.docs.show', $link->document) }}" class="mt-3 block text-sm font-semibold text-main truncate hover:text-primary">{{ $link->name }}</a>
                                <p class="text-xs text-muted-400 mt-1">{{ __('online_docs.shortcut') }}</p>
                            </div>
                        @endforeach

                        <p data-empty-state class="text-sm text-muted-400 {{ $isStorageEmpty ? '' : 'hidden' }}">{{ __('online_docs.empty_storage') }}</p>
                    </div>

                    <div class="hidden" data-view="list">
                        <div class="rounded-2xl border border-muted-100">
                            <div class="grid grid-cols-12 gap-3 px-4 py-2 text-xs font-semibold text-muted-500">
                                <div class="col-span-6">{{ __('online_docs.name') }}</div>
                                <div class="col-span-2">{{ __('online_docs.type') }}</div>
                                <div class="col-span-2">{{ __('online_docs.modified') }}</div>
                                <div class="col-span-2 text-right">{{ __('online_docs.actions') }}</div>
                            </div>
                            <div class="divide-y divide-muted-100" data-items-container>
                                @foreach($folders as $folder)
                                    <div class="storage-item grid grid-cols-12 gap-3 px-4 py-2" data-item-type="folder" data-item-id="{{ $folder->id }}" data-item-name="{{ $folder->name }}" data-item-updated="{{ $folder->updated_at?->timestamp ?? 0 }}" data-item-size="0" data-folder-drop draggable="true">
                                        <div class="col-span-6 flex items-center gap-2">
                                            @if($folder->user_id === auth()->id())
                                                <input type="checkbox" class="storage-select" data-item-type="folder" data-item-id="{{ $folder->id }}" />
                                            @endif
                                            <a href="{{ route('online-docs.home', ['folder' => $folder->id]) }}" class="text-sm font-medium text-main truncate hover:text-primary">{{ $folder->name }}</a>
                                            @if($folder->user_id !== auth()->id())
                                                <span class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium text-primary">{{ __('online_docs.shared_with_me') }}</span>
                                            @endif
                                        </div>
                                        <div class="col-span-2 text-xs text-muted-400">{{ __('online_docs.folder') }}</div>
                                        <div class="col-span-2 text-xs text-muted-400">{{ $folder->updated_at?->diffForHumans() }}</div>
                                        <div class="col-span-2 flex items-center justify-end">
                                            @if($folder->user_id === auth()->id())
                                                <div class="relative" data-menu>
                                                    <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                            <circle cx="4" cy="10" r="1.5" />
                                                            <circle cx="10" cy="10" r="1.5" />
                                                            <circle cx="16" cy="10" r="1.5" />
                                                        </svg>
                                                    </button>
                                                    <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
                                                        <form method="POST" action="{{ route('online-docs.folders.share', $folder) }}" class="mb-2 flex flex-col gap-2 border-b border-muted-100 pb-2">
                                                            @csrf
                                                            <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                            <input type="email" name="email" required placeholder="{{ __('online_docs.share_folder_email') }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                                                            <select name="permission" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                                                <option value="view">{{ __('online_docs.share_view') }}</option>
                                                                <option value="edit">{{ __('online_docs.share_edit') }}</option>
                                                            </select>
                                                            <button type="submit" class="px-3 py-1 rounded-lg bg-primary/10 text-primary text-xs hover:bg-primary/20">
                                                                {{ __('online_docs.share_folder') }}
                                                            </button>
                                                            <button type="button" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200" data-folder-share-link data-folder-share-link-url="{{ route('online-docs.folders.share.link', $folder) }}">
                                                                {{ __('online_docs.copy_share_link') }}
                                                            </button>
                                                        </form>
                                                        <div class="mb-2 border-b border-muted-100 pb-2">
                                                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-muted-400">{{ __('online_docs.shared_list') }}</p>
                                                            @forelse($folder->shares as $share)
                                                                <div class="mb-1 flex items-center justify-between gap-2 rounded-lg bg-muted-50 px-2 py-1">
                                                                    <span class="truncate text-xs text-muted-700">{{ $share->user?->email }}</span>
                                                                    <span class="text-[11px] text-muted-500 shrink-0">{{ $share->permission }}</span>
                                                                    <form method="POST" action="{{ route('online-docs.folders.share.remove', ['folder' => $folder, 'share' => $share]) }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                                        <button type="submit" class="text-[11px] text-danger hover:text-danger/80">{{ __('online_docs.stop_sharing') }}</button>
                                                                    </form>
                                                                </div>
                                                            @empty
                                                                <p class="text-xs text-muted-400">{{ __('online_docs.no_folder_shares') }}</p>
                                                            @endforelse
                                                        </div>
                                                        <form method="POST" action="{{ route('online-docs.folders.update', $folder) }}" class="flex flex-col gap-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                            <input type="text" name="name" required value="{{ $folder->name }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                                                            <button type="submit" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200">
                                                                {{ __('online_docs.rename') }}
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('online-docs.folders.delete', $folder) }}" class="mt-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                            <button type="submit" class="w-full px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20">
                                                                {{ __('online_docs.delete') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @foreach($files as $file)
                                    @php
                                        $fileExtension = strtolower(pathinfo((string) $file->original_name, PATHINFO_EXTENSION));
                                        $isOfficeFile = in_array($fileExtension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);
                                        $openUrl = $isOfficeFile
                                            ? route('online-docs.files.open', ['file' => $file])
                                            : route('online-docs.files.preview', $file);
                                    @endphp
                                    <div class="storage-item grid grid-cols-12 gap-3 px-4 py-2" data-item-type="file" data-item-id="{{ $file->id }}" data-item-name="{{ $file->original_name }}" data-item-updated="{{ $file->updated_at?->timestamp ?? 0 }}" data-item-size="{{ $file->size ?? 0 }}" draggable="true">
                                        <div class="col-span-6 flex items-center gap-2">
                                            @if($storageCanEdit)
                                                <input type="checkbox" class="storage-select" data-item-type="file" data-item-id="{{ $file->id }}" />
                                            @endif
                                            <a href="{{ $openUrl }}" class="text-sm font-medium text-main truncate hover:text-primary">{{ $file->original_name }}</a>
                                        </div>
                                        <div class="col-span-2 flex items-center gap-1.5">
                                            <span class="text-xs text-muted-400">{{ __('online_docs.file') }}</span>
                                            @php $ingestStatus = $file->ingest_status ?? 'pending'; @endphp
                                            @if($ingestStatus === 'pending')
                                                <span class="inline-flex rounded-full bg-yellow-100 px-1.5 py-0.5 text-[10px] font-medium text-yellow-800">{{ __('online_docs.ingest_status_pending') }}</span>
                                            @elseif($ingestStatus === 'processing')
                                                <span class="inline-flex rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-800">{{ __('online_docs.ingest_status_processing') }}</span>
                                            @elseif($ingestStatus === 'completed')
                                                <span class="inline-flex rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-800">{{ __('online_docs.ingest_status_completed') }}</span>
                                            @elseif($ingestStatus === 'failed')
                                                <span class="inline-flex rounded-full bg-red-100 px-1.5 py-0.5 text-[10px] font-medium text-red-800">{{ __('online_docs.ingest_status_failed') }}</span>
                                            @endif
                                        </div>
                                        <div class="col-span-2 text-xs text-muted-400">{{ $file->updated_at?->diffForHumans() }}</div>
                                        <div class="col-span-2 flex items-center justify-end gap-2">
                                            <a href="{{ $openUrl }}" class="text-xs text-primary hover:text-primary-hover">{{ __('online_docs.preview') }}</a>
                                            <a href="{{ route('online-docs.files.download', $file) }}" class="text-xs text-primary hover:text-primary-hover">{{ __('online_docs.download') }}</a>
                                            @if($ingestStatus === 'completed')
                                                <button type="button"
                                                    data-docs-summarize
                                                    data-workspace-id="personal_file_{{ $file->id }}"
                                                    data-s3-key="{{ $file->stored_path }}"
                                                    data-file-name="{{ $file->original_name }}"
                                                    title="Summarize this file"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-purple-200 bg-purple-50 px-2 py-1 text-[11px] font-medium text-purple-700 hover:bg-purple-100 transition-colors">
                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    Summarize
                                                </button>
                                            @endif
                                            @if(in_array($ingestStatus, ['pending', 'failed', 'processing']))
                                                <form method="POST"
                                                      action="{{ route('online-docs.files.ingest', $file) }}"
                                                      class="inline"
                                                      data-ingest-file-form>
                                                    @csrf
                                                    <button type="submit"
                                                        title="{{ __('online_docs.retry_ingest') }}"
                                                        class="inline-flex items-center justify-center rounded p-0.5 text-muted-400 hover:text-blue-600 transition-colors"
                                                        data-ingest-file-button>
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                            <polyline points="1 4 1 10 7 10"/>
                                                            <path d="M3.51 15a9 9 0 1 0 .49-3.86L1 10"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            <div class="relative" data-menu>
                                                <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                        <circle cx="4" cy="10" r="1.5" />
                                                        <circle cx="10" cy="10" r="1.5" />
                                                        <circle cx="16" cy="10" r="1.5" />
                                                    </svg>
                                                </button>
                                                <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
                                                    @if($storageCanEdit)
                                                        <form method="POST" action="{{ route('online-docs.files.update', $file) }}" class="flex flex-col gap-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                            <input type="text" name="name" required value="{{ $file->original_name }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                                                            <button type="submit" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200">
                                                                {{ __('online_docs.rename') }}
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('online-docs.files.delete', $file) }}" class="mt-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                            <button type="submit" class="w-full px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20">
                                                                {{ __('online_docs.delete') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @foreach($links as $link)
                                    <div class="storage-item grid grid-cols-12 gap-3 px-4 py-2" data-item-type="link" data-item-id="{{ $link->id }}" data-item-name="{{ $link->name }}" data-item-updated="{{ $link->updated_at?->timestamp ?? 0 }}" data-item-size="0" draggable="true">
                                        <div class="col-span-6 flex items-center gap-2">
                                            @if($storageCanEdit)
                                                <input type="checkbox" class="storage-select" data-item-type="link" data-item-id="{{ $link->id }}" />
                                            @endif
                                            <a href="{{ route('online-docs.docs.show', $link->document) }}" class="text-sm font-medium text-main truncate hover:text-primary">{{ $link->name }}</a>
                                        </div>
                                        <div class="col-span-2 text-xs text-muted-400">{{ __('online_docs.document_link') }}</div>
                                        <div class="col-span-2 text-xs text-muted-400">{{ $link->updated_at?->diffForHumans() }}</div>
                                        <div class="col-span-2 flex items-center justify-end gap-2">
                                            <a href="{{ route('online-docs.docs.show', $link->document) }}" class="text-xs text-primary hover:text-primary-hover">{{ __('online_docs.open') }}</a>
                                            <div class="relative" data-menu>
                                                <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                        <circle cx="4" cy="10" r="1.5" />
                                                        <circle cx="10" cy="10" r="1.5" />
                                                        <circle cx="16" cy="10" r="1.5" />
                                                    </svg>
                                                </button>
                                                <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
                                                    @if($storageCanEdit)
                                                        <form method="POST" action="{{ route('online-docs.links.update', $link) }}" class="flex flex-col gap-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                            <input type="text" name="name" required value="{{ $link->name }}" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                                                            <button type="submit" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200">
                                                                {{ __('online_docs.rename') }}
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('online-docs.links.delete', $link) }}" class="mt-2">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="redirect_folder_id" value="{{ $currentFolder?->id }}" />
                                                            <button type="submit" class="w-full px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20">
                                                                {{ __('online_docs.delete') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <div data-empty-state class="px-4 py-3 text-sm text-muted-400 {{ $isStorageEmpty ? '' : 'hidden' }}">{{ __('online_docs.empty_storage') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/online_docs/chunked-uploader.js', 'resources/js/online_docs/storage.js'])
    <script>
    (function () {
        const input = document.getElementById('agent-query-input');
        const btn = document.getElementById('agent-ask-btn');
        const results = document.getElementById('agent-results');
        const loading = document.getElementById('agent-loading');
        const errorEl = document.getElementById('agent-error');
        const answerBlock = document.getElementById('agent-answer-block');
        const answerText = document.getElementById('agent-answer-text');
        const confidenceEl = document.getElementById('agent-confidence');
        const citationsBlock = document.getElementById('agent-citations-block');
        const citationsList = document.getElementById('agent-citations-list');

        const agentUrl = @json(route('online-docs.search-agent'));
        const csrf = @json(csrf_token());
        const pageTxt = @json(__('online_docs.search_agent_page', ['page' => ':page']));
        const lineTxt = @json(__('online_docs.search_agent_line', ['line' => ':line']));
        const confTxt = @json(__('online_docs.search_agent_confidence'));
        const openTxt = @json(__('online_docs.open'));
        const previewTxt = @json(__('online_docs.preview'));
        const emptyTxt = @json(__('online_docs.search_agent_empty'));
        const copyTxt = @json(__('online_docs.search_agent_copy'));
        const copiedTxt = @json(__('online_docs.search_agent_copied'));
        const badgeDocTxt = @json(__('online_docs.search_agent_badge_doc'));
        const badgeFileTxt = @json(__('online_docs.search_agent_badge_file'));

        const copyBtn = document.getElementById('agent-copy-btn');
        const copyLabel = document.getElementById('agent-copy-label');
        const followupInput = document.getElementById('agent-followup-input');
        const followupBtn = document.getElementById('agent-followup-btn');

        function renderMarkdown(text) {
            if (!text) return '';
            if (window.marked && window.DOMPurify) {
                return window.DOMPurify.sanitize(window.marked.parse(text, { breaks: true }));
            }
            // Lightweight fallback
            let html = text
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/### (.+)/g, '<h3 class="text-xs font-semibold text-muted-500 uppercase tracking-wider mt-3 mb-1">$1</h3>')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>');
            const lines = html.split('\n');
            const out = [];
            let inList = false;
            for (const line of lines) {
                const isBullet = /^[\-•]\s+/.test(line.trimStart());
                if (isBullet) {
                    if (!inList) { out.push('<ul class="list-disc pl-5 space-y-1 mt-1">'); inList = true; }
                    out.push('<li>' + line.replace(/^[\-•]\s+/, '') + '</li>');
                } else {
                    if (inList) { out.push('</ul>'); inList = false; }
                    if (line.startsWith('<h3')) out.push(line);
                    else out.push(line === '' ? '' : '<p class="mt-1">' + line + '</p>');
                }
            }
            if (inList) out.push('</ul>');
            return out.join('');
        }

        function setLoading(on) {
            btn.disabled = on;
            if (followupBtn) followupBtn.disabled = on;
            loading.classList.toggle('hidden', !on);
            errorEl.classList.add('hidden');
            answerBlock.classList.add('hidden');
        }

        let lastAnswer = '';

        async function ask(query) {
            query = (query || input.value).trim();
            if (!query) return;

            results.classList.remove('hidden');
            setLoading(true);

            try {
                const res = await fetch(agentUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ query }),
                });

                const data = await res.json();
                setLoading(false);

                if (!res.ok || data.error) {
                    errorEl.textContent = data.error || @json(__('online_docs.search_agent_error'));
                    errorEl.classList.remove('hidden');
                    return;
                }

                lastAnswer = data.answer || '';
                answerText.innerHTML = renderMarkdown(lastAnswer || emptyTxt);
                answerBlock.classList.remove('hidden');

                // Source badge (keyword search fallback vs AI)
                const existingBadge = document.getElementById('agent-source-badge');
                if (existingBadge) existingBadge.remove();
                if (data.source === 'bm25') {
                    const badge = document.createElement('div');
                    badge.id = 'agent-source-badge';
                    badge.className = 'mt-2 inline-flex items-center gap-1.5 text-[11px] text-muted-500 bg-muted-100 rounded-lg px-2.5 py-1';
                    badge.innerHTML = `<svg viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>Kết quả tìm kiếm theo từ khóa`;
                    confidenceEl.parentNode.insertBefore(badge, confidenceEl);
                }

                if (data.confidence && data.confidence.level) {
                    const lvlMap = { high: 'text-success', medium: 'text-warning', low: 'text-muted-400' };
                    const cls = lvlMap[data.confidence.level] || 'text-muted-400';
                    const score = data.confidence.score ? ' (' + (data.confidence.score * 100).toFixed(0) + '%)' : '';
                    confidenceEl.innerHTML = `<span class="${cls}">${confTxt}: ${data.confidence.level}${score}</span>`;
                } else {
                    confidenceEl.innerHTML = '';
                }

                const cits = (data.citations || []).filter(c => c.display_name || c.source);
                if (cits.length > 0) {
                    citationsList.innerHTML = '';
                    cits.forEach(c => {
                        const link = c.doc_link || c.file_link;
                        const linkLabel = c.doc_link ? openTxt : previewTxt;
                        const loc = [];
                        if (c.page) loc.push(pageTxt.replace(':page', c.page));
                        if (c.line) loc.push(lineTxt.replace(':line', c.line));
                        const locStr = loc.length ? `<span class="text-[11px] text-primary/70 ml-1">(${loc.join(', ')})</span>` : '';

                        const badgeText = c.source_type === 'file' ? badgeFileTxt : badgeDocTxt;
                        const badgeCls = c.source_type === 'file'
                            ? 'bg-info/10 text-info'
                            : 'bg-primary/10 text-primary';

                        const el = document.createElement('div');
                        el.className = 'flex items-center justify-between gap-3 rounded-xl border border-muted-100 px-4 py-2 bg-muted-50/50';
                        el.innerHTML = `
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded ${badgeCls}">${badgeText}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-main truncate">${c.display_name || c.source}${locStr}</p>
                                    ${c.source !== c.display_name ? `<p class="text-[11px] text-muted-400 truncate">${c.source}</p>` : ''}
                                </div>
                            </div>
                            ${link ? `<a href="${link}" class="text-xs font-medium text-primary hover:text-primary-hover shrink-0">${linkLabel}</a>` : ''}
                        `;
                        citationsList.appendChild(el);
                    });
                    citationsBlock.classList.remove('hidden');
                } else {
                    citationsBlock.classList.add('hidden');
                }

            } catch (e) {
                setLoading(false);
                errorEl.textContent = @json(__('online_docs.search_agent_error'));
                errorEl.classList.remove('hidden');
            }
        }

        // Copy answer button
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                if (!lastAnswer) return;
                navigator.clipboard.writeText(lastAnswer).then(() => {
                    copyLabel.textContent = copiedTxt;
                    setTimeout(() => { copyLabel.textContent = copyTxt; }, 2000);
                });
            });
        }

        // Follow-up query
        if (followupBtn) {
            followupBtn.addEventListener('click', () => {
                const q = followupInput.value.trim();
                if (!q) return;
                input.value = q;
                followupInput.value = '';
                ask(q);
            });
            followupInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') followupBtn.click();
            });
        }

        btn.addEventListener('click', () => ask());
        input.addEventListener('keydown', e => { if (e.key === 'Enter') ask(); });
    })();
    </script>

    {{-- Ingest loading overlay + handlers (mirrors AI Workspace behaviour) --}}
    <div id="ingest-loading-overlay" class="ingest-loading-overlay hidden" aria-live="polite" aria-busy="true">
        <div class="ingest-spinner"></div>
        <p class="text-sm font-medium text-slate-700">{{ __('online_docs.loading_ingest') }}</p>
    </div>

    <script>
        const ingestForm    = document.getElementById('ingest-all-form');
        const ingestBtn     = document.getElementById('ingest-all-btn');
        const ingestIcon    = document.getElementById('ingest-all-icon');
        const ingestOverlay = document.getElementById('ingest-loading-overlay');

        if (ingestForm && ingestBtn && ingestOverlay) {
            ingestForm.addEventListener('submit', () => {
                ingestBtn.disabled = true;
                ingestBtn.classList.add('opacity-70', 'cursor-not-allowed');
                if (ingestIcon) {
                    ingestIcon.classList.add('animate-spin');
                }
                ingestOverlay.classList.remove('hidden');
            });
        }

        document.querySelectorAll('[data-ingest-file-form]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button     = form.querySelector('[data-ingest-file-button]');
                const buttonIcon = button ? button.querySelector('svg') : null;

                if (button) {
                    button.disabled = true;
                    button.classList.add('opacity-70', 'cursor-not-allowed');
                }
                if (buttonIcon) {
                    buttonIcon.classList.add('animate-spin');
                }
                if (ingestOverlay) {
                    ingestOverlay.classList.remove('hidden');
                }
            });
        });
    </script>

    <style>
        .ingest-loading-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(4px);
        }

        .ingest-loading-overlay.hidden {
            display: none !important;
        }

        .ingest-spinner {
            width: 30px;
            height: 30px;
            border-radius: 9999px;
            border: 3px solid #e5e7eb;
            border-top-color: #2563eb;
            animation: ingest-spin 0.7s linear infinite;
        }

        @keyframes ingest-spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    {{-- Summary modal (reused for per-file summarization) --}}
    <div id="docsSummaryModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="docsCloseSummaryModal()"></div>
        <div class="relative mx-auto mt-16 max-h-[80vh] w-full max-w-2xl flex flex-col rounded-2xl bg-white shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-muted-100 px-6 py-4">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-main truncate" id="docsSummaryTitle">Summarize</h2>
                    <p class="text-xs text-muted-400" id="docsSummarySubtitle"></p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button id="docsCopySummaryBtn" type="button" onclick="docsCopySummary()"
                        class="hidden items-center gap-1.5 rounded-lg border border-muted-200 px-3 py-1.5 text-xs font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        Copy
                    </button>
                    <button type="button" onclick="docsCloseSummaryModal()"
                        class="rounded-lg p-1.5 text-muted-400 hover:bg-muted-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-3 border-b border-muted-100 px-6 py-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-muted-500">Style:</label>
                    <select id="docsSummaryStyle" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                        <option value="bullet">Bullet points</option>
                        <option value="paragraph">Paragraph</option>
                        <option value="short">Short (TL;DR)</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-muted-500">Language:</label>
                    <select id="docsSummaryLang" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                        <option value="auto">Auto</option>
                        <option value="en">English</option>
                        <option value="vi">Vietnamese</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-muted-500">Clusters:</label>
                    <select id="docsSummaryClusters" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                        <option value="5">5</option>
                        <option value="8">8</option>
                        <option value="10" selected>10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                    </select>
                </div>
                <button type="button" onclick="docsRunSummary()"
                    class="ml-auto rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-purple-700 transition-colors">
                    Regenerate
                </button>
            </div>

            <div id="docsSummaryBody" class="flex-1 overflow-y-auto px-6 py-4 text-sm text-main leading-relaxed min-h-[200px]">
                <div id="docsSummaryLoading" class="flex flex-col items-center justify-center gap-3 py-12">
                    <div class="w-8 h-8 rounded-full border-2 border-muted-200 border-t-purple-600 animate-spin"></div>
                    <p class="text-xs text-muted-400">Generating summary…</p>
                </div>
                <div id="docsSummaryContent" class="hidden prose prose-sm max-w-none"></div>
                <div id="docsSummaryError" class="hidden rounded-lg bg-danger/10 px-4 py-3 text-sm text-danger"></div>
            </div>

            <div id="docsSummaryFooter" class="hidden border-t border-muted-100 px-6 py-3 flex-wrap gap-3 text-[11px] text-muted-400">
                <span id="docsSummaryClustersInfo"></span>
                <span id="docsSummaryChunksInfo"></span>
                <span id="docsSummarySourceInfo" class="truncate"></span>
            </div>
        </div>
    </div>

    <script>
    (function () {
        let _docsS3Key = null;
        let _docsWorkspaceId = null;
        let _docsFileName = null;
        let _docsPlainText = '';

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return '';
        }

        window.docsSummarizeFile = function (workspaceId, s3Key, fileName) {
            _docsWorkspaceId = workspaceId;
            _docsS3Key = s3Key;
            _docsFileName = fileName;
            _docsPlainText = '';

            document.getElementById('docsSummaryTitle').textContent = 'Summarize Document';
            document.getElementById('docsSummarySubtitle').textContent = fileName || '';
            document.getElementById('docsCopySummaryBtn').style.display = 'none';
            document.getElementById('docsSummaryFooter').style.display = 'none';
            document.getElementById('docsSummaryContent').classList.add('hidden');
            document.getElementById('docsSummaryError').classList.add('hidden');
            document.getElementById('docsSummaryLoading').classList.remove('hidden');
            document.getElementById('docsSummaryModal').classList.remove('hidden');

            docsRunSummary();
        };

        function bindSummarizeButtons() {
            document.querySelectorAll('[data-docs-summarize]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    docsSummarizeFile(
                        btn.dataset.workspaceId || '',
                        btn.dataset.s3Key || '',
                        btn.dataset.fileName || ''
                    );
                });
            });
        }

        window.docsCloseSummaryModal = function () {
            document.getElementById('docsSummaryModal').classList.add('hidden');
        };

        window.docsCopySummary = function () {
            if (!_docsPlainText) return;
            navigator.clipboard.writeText(_docsPlainText).then(() => {
                const btn = document.getElementById('docsCopySummaryBtn');
                btn.textContent = 'Copied!';
                setTimeout(() => { btn.innerHTML = '<svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> Copy'; }, 2000);
            });
        };

        function docsRenderSummaryText(raw) {
            if (!raw) return '';
            let html = raw
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>');
            const lines = html.split('\n');
            const out = [];
            let inList = false;
            for (const line of lines) {
                const isBullet = /^[\-•]\s+/.test(line.trimStart());
                if (isBullet) {
                    if (!inList) { out.push('<ul class="list-disc pl-5 space-y-1">'); inList = true; }
                    out.push('<li>' + line.replace(/^[\-•]\s+/, '') + '</li>');
                } else {
                    if (inList) { out.push('</ul>'); inList = false; }
                    out.push(line === '' ? '' : '<p class="mb-2">' + line + '</p>');
                }
            }
            if (inList) out.push('</ul>');
            return out.join('');
        }

        window.docsRunSummary = async function () {
            const loading = document.getElementById('docsSummaryLoading');
            const content = document.getElementById('docsSummaryContent');
            const errorEl = document.getElementById('docsSummaryError');
            const footer  = document.getElementById('docsSummaryFooter');
            const copyBtn = document.getElementById('docsCopySummaryBtn');

            loading.classList.remove('hidden');
            content.classList.add('hidden');
            errorEl.classList.add('hidden');
            footer.style.display = 'none';
            copyBtn.style.display = 'none';
            _docsPlainText = '';

            const style    = document.getElementById('docsSummaryStyle').value;
            const lang     = document.getElementById('docsSummaryLang').value;
            const clusters = parseInt(document.getElementById('docsSummaryClusters').value, 10);

            try {
                const headers = {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || @json(csrf_token()),
                    'Accept': 'application/json',
                };
                const xsrf = getCookie('XSRF-TOKEN');
                if (xsrf) {
                    headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf);
                }

                const res = await fetch('/api/ai/summarize-document', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers,
                    body: JSON.stringify({
                        workspace_id: _docsWorkspaceId,
                        s3_key: _docsS3Key,
                        lang,
                        style,
                        n_clusters: clusters,
                    }),
                });

                const data = await res.json();
                loading.classList.add('hidden');

                if (!res.ok) {
                    errorEl.textContent = data.message || data.error || 'Summary failed.';
                    errorEl.classList.remove('hidden');
                    return;
                }

                if (data.error) {
                    errorEl.textContent = data.error;
                    errorEl.classList.remove('hidden');
                    return;
                }

                const summaryText = (data.summary || '').trim();
                if (!summaryText) {
                    errorEl.textContent = 'No summary was generated. Try increasing the clusters count.';
                    errorEl.classList.remove('hidden');
                    return;
                }

                _docsPlainText = summaryText;
                content.innerHTML = docsRenderSummaryText(summaryText);
                content.classList.remove('hidden');
                copyBtn.style.display = 'inline-flex';

                const clustersInfo = document.getElementById('docsSummaryClustersInfo');
                const chunksInfo   = document.getElementById('docsSummaryChunksInfo');
                const sourceInfo   = document.getElementById('docsSummarySourceInfo');

                clustersInfo.textContent = data.n_clusters ? `Clusters: ${data.n_clusters}` : '';
                chunksInfo.textContent   = data.total_chunks ? `Chunks: ${data.total_chunks}` : '';
                sourceInfo.textContent   = data.file_name || _docsFileName || data.source || '';

                footer.style.display = 'flex';
            } catch (e) {
                loading.classList.add('hidden');
                errorEl.textContent = 'Network error: ' + e.message;
                errorEl.classList.remove('hidden');
            }
        };

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') docsCloseSummaryModal();
        });

        bindSummarizeButtons();
    })();
    </script>
@endpush
