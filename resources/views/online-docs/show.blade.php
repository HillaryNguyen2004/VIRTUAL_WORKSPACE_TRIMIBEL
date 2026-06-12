@extends('layout_dashboard')
@section('title', __('online_docs.title'))

@section('content')
    <div class="flex flex-col gap-3 w-full mx-auto text-main px-4 md:px-8 lg:px-12 py-4 min-h-[calc(100vh-2rem)]">
        @php
            $canEdit = $editorCanEdit ?? auth()->user()->can('update', $document);
            $canUpdateDocument = auth()->user()->can('update', $document);
            $isExcel = $document->type === 'excel';
            $isDocs = $document->type === 'docs';
            $isPowerpoint = $document->type === 'powerpoint';
            $isForcedViewMode = $forcedView ?? (request()->query('mode') === 'view');
            $importField = $isExcel ? 'xlsx' : ($isPowerpoint ? 'pptx' : 'docx');
            $importLabel = $isExcel ? __('online_docs.import_xlsx') : ($isPowerpoint ? __('online_docs.import_pptx') : __('online_docs.import_docx'));
            $importAccept = $isExcel ? '.xls,.xlsx' : ($isPowerpoint ? '.ppt,.pptx' : '.doc,.docx');
            $importAction = $isExcel
                ? route('online-docs.docs.import.xlsx', $document)
                : ($isPowerpoint
                    ? route('online-docs.docs.import.pptx', $document)
                    : route('online-docs.docs.import', $document));
            $xlsxUrl = route('online-docs.docs.xlsx', $document) . '?v=' . ($document->updated_at?->getTimestamp() ?: time());
            $shouldOpenImportModal = session('docx_error')
                || session('xlsx_error')
                || session('pptx_error')
                || $errors->has('docx')
                || $errors->has('xlsx')
                || $errors->has('pptx');
            $shouldOpenShareModal = $errors->has('email')
                || $errors->has('permission')
                || $errors->has('user_id');
            $presenceUrl = route('online-docs.docs.presence', $document);
            $presenceTouchUrl = route('online-docs.docs.presence.touch', $document);
        @endphp

        {{-- Toolbar --}}
        <div class="relative z-[1200] bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 px-4 py-2.5 flex flex-wrap items-center gap-2">
            {{-- Doc title --}}
            <span class="text-sm font-semibold text-main truncate max-w-[180px] hidden sm:block" title="{{ $document->title }}">{{ $document->title }}</span>
            <span class="hidden sm:block h-4 w-px bg-muted-200 mx-1"></span>

            <a href="{{ route('online-docs.home') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-muted-200 text-xs font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('online_docs.back_all') }}
            </a>

            @if($canEdit && ($isDocs || $isExcel || $isPowerpoint))
                <button type="button" data-open-modal="import-modal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-muted-200 text-xs font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    {{ $importLabel }}
                </button>
            @endif

            @if($isExcel)
                <a href="{{ route('online-docs.docs.xlsx', $document) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-muted-200 text-xs font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    {{ __('online_docs.export_xlsx') }}
                </a>
            @elseif($isPowerpoint)
                <a href="{{ route('online-docs.docs.export', $document) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-muted-200 text-xs font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    {{ __('online_docs.export_pptx') }}
                </a>
            @else
                <a href="{{ route('online-docs.docs.export', $document) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-muted-200 text-xs font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    {{ __('online_docs.export_docx') }}
                </a>
            @endif

            @if(($isDocs || $isPowerpoint) && $onlyofficeConfig && $canUpdateDocument)
                @if($isForcedViewMode)
                    <a href="{{ route('online-docs.docs.show', ['document' => $document, 'mode' => 'edit']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary text-white text-xs font-medium hover:bg-primary-hover transition-colors shadow-sm shadow-primary/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ __('online_docs.enable_edit') }}
                    </a>
                @else
                    <a href="{{ route('online-docs.docs.show', ['document' => $document, 'mode' => 'view']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-muted-200 text-xs font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        {{ __('online_docs.view_only') }}
                    </a>
                @endif
            @endif

            @if($isDocs)
                <span class="h-4 w-px bg-muted-200 mx-1"></span>
                <button type="button" data-open-modal="action-items-modal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-muted-200 text-xs font-medium text-muted-600 hover:bg-muted-50 transition-colors" data-action-items-open>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    {{ __('online_docs.extract_action_items') }}
                </button>
                <button type="button" data-open-modal="ai-summary-modal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-primary/30 bg-primary/5 text-xs font-medium text-primary hover:bg-primary/10 transition-colors" data-ai-summary-open>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    {{ __('online_docs.ai_summary') }}
                </button>
            @endif

            @can('share', $document)
                <button type="button" data-open-modal="share-modal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-muted-200 text-xs font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    Share with others
                </button>
            @endcan
        </div>

        {{-- Presence bar --}}
        <div
            id="active-editors"
            class="bg-white rounded-xl border border-muted-200 px-4 py-2 flex flex-wrap items-center gap-2 text-xs text-muted-600"
            data-presence-url="{{ $presenceUrl }}"
            data-presence-touch-url="{{ $presenceTouchUrl }}"
            data-can-edit="{{ $canEdit ? 'true' : 'false' }}"
            data-csrf="{{ csrf_token() }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-muted-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <span class="font-medium text-muted-500">Editing:</span>
            <div id="active-editors-list" class="flex flex-wrap items-center gap-2">
                <span class="text-muted-400">No one else</span>
            </div>
        </div>

        <div class="relative z-10">
            @if($isExcel)
                <div class="rounded-2xl border border-muted-200 bg-white shadow-lg shadow-main/5 overflow-hidden">
                    <div
                        id="excel-editor"
                        class="h-[calc(100vh-190px)] min-h-[680px]"
                        data-xlsx-url="{{ $xlsxUrl }}"
                        data-save-url="{{ route('online-docs.docs.xlsx.save', $document) }}"
                        data-read-only="{{ $canEdit ? 'false' : 'true' }}"
                        data-saving-text="{{ __('online_docs.saving') }}"
                        data-saved-text="{{ __('online_docs.saved') }}"
                        data-csrf="{{ csrf_token() }}"
                    ></div>
                </div>
            @elseif(($isDocs || $isPowerpoint) && $onlyofficeConfig)
                <div class="rounded-2xl border border-muted-200 bg-white shadow-lg shadow-main/5 overflow-hidden">
                    <div id="onlyoffice-editor" style="height: calc(100vh - 190px); min-height: 680px; width: 100%;"></div>
                </div>
            @else
                <form
                    id="doc-editor-form"
                    method="POST"
                    action="{{ route('online-docs.docs.update', $document) }}"
                    data-saving-text="{{ __('online_docs.saving') }}"
                    data-saved-text="{{ __('online_docs.saved') }}"
                    data-read-only="{{ $canEdit ? 'false' : 'true' }}"
                    data-read-only-text="{{ __('online_docs.read_only') }}"
                >
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="title" value="{{ old('title', $document->title) }}" />
                    <textarea id="doc-content" name="content" class="hidden">{{ $html }}</textarea>
                    <div class="rounded-2xl border border-muted-200 bg-white shadow-lg shadow-main/5 overflow-hidden tiptap-editor">
                        <div id="doc-editor" class="min-h-[calc(100vh-190px)] p-4 sm:p-6"></div>
                    </div>
                </form>
            @endif
        </div>

        @can('update', $document)
            @if($isDocs || $isExcel || $isPowerpoint)
                <div id="import-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 p-4" data-modal-overlay>
                    <div class="w-full max-w-lg rounded-2xl border border-muted-200 bg-white shadow-2xl shadow-main/10 overflow-hidden">
                        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-muted-100">
                            <h4 class="text-sm font-semibold text-main">{{ $importLabel }}</h4>
                            <button type="button" data-close-modal class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-muted-400 hover:bg-muted-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="px-5 py-4">
                            @if(session('docx_error') || session('xlsx_error') || session('pptx_error'))
                                <div class="mb-3 rounded-xl border border-danger/20 bg-danger/5 text-danger text-xs px-4 py-3">
                                    {{ session('docx_error') ?: (session('xlsx_error') ?: session('pptx_error')) }}
                                </div>
                            @endif
                            @error($importField)
                                <div class="mb-3 rounded-xl border border-danger/20 bg-danger/5 text-danger text-xs px-4 py-3">
                                    {{ $message }}
                                </div>
                            @enderror
                            <form method="POST" action="{{ $importAction }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                                @csrf
                                <label class="flex flex-col gap-1.5">
                                    <span class="text-xs font-medium text-muted-600">{{ $importLabel }}</span>
                                    <input type="file" name="{{ $importField }}" accept="{{ $importAccept }}" required class="text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary/5 file:text-primary file:px-3 file:py-1.5 file:text-xs file:font-medium hover:file:bg-primary/10" />
                                </label>
                                <div class="flex items-center justify-end gap-2 pt-1">
                                    <button type="button" data-close-modal class="px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                                        {{ __('online_docs.cancel') }}
                                    </button>
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-hover transition-colors shadow-sm shadow-primary/20">
                                        {{ $importLabel }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

        @can('share', $document)
            <div id="share-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 p-4" data-modal-overlay>
                <div class="w-full max-w-2xl rounded-2xl border border-muted-200 bg-white shadow-2xl shadow-main/10 max-h-[85vh] flex flex-col">
                    <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-muted-100 shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                            </span>
                            <h4 class="text-sm font-semibold text-main">Share with others</h4>
                        </div>
                        <button type="button" data-close-modal class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-muted-400 hover:bg-muted-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-5 py-4 flex flex-col gap-4">
                        <form method="POST" action="{{ route('online-docs.docs.share', $document) }}" class="flex flex-col gap-3">
                            @csrf
                            <div class="relative" id="doc-share-picker">
                                <input
                                    type="text"
                                    name="email"
                                    required
                                    autocomplete="off"
                                    class="w-full rounded-xl border border-muted-200 px-4 py-2 pr-9 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    placeholder="{{ __('online_docs.share_email') }}"
                                    id="doc-share-input"
                                />
                                <button type="button" id="doc-share-toggle" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg border border-muted-200 px-2 py-1 text-xs text-muted-500 hover:bg-muted-100 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div id="doc-share-list" class="absolute z-20 mt-2 hidden max-h-56 w-full overflow-auto rounded-xl border border-muted-200 bg-white shadow-lg">
                                    @foreach($shareCandidates as $candidate)
                                        <button type="button" class="doc-share-option flex w-full flex-col gap-0.5 px-3 py-2 text-left text-sm hover:bg-muted-50"
                                            data-value="{{ $candidate->email }}"
                                            data-label="{{ $candidate->name ? $candidate->name . ' - ' : '' }}{{ $candidate->email }}"
                                        >
                                            <span class="text-main font-medium">{{ $candidate->name ?: $candidate->email }}</span>
                                            <span class="text-xs text-muted-400">{{ $candidate->email }}</span>
                                        </button>
                                    @endforeach
                                    <div id="doc-share-empty" class="hidden px-3 py-2 text-xs text-muted-400">No matches</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <select name="permission" class="flex-1 rounded-xl border border-muted-200 px-4 py-2 text-sm">
                                    <option value="view">{{ __('online_docs.share_view') }}</option>
                                    <option value="edit">{{ __('online_docs.share_edit') }}</option>
                                </select>
                                <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary-hover transition-colors shadow-sm shadow-primary/20 shrink-0">
                                    Share
                                </button>
                            </div>
                        </form>

                        <div class="border-t border-muted-100 pt-3">
                            <p class="text-xs font-semibold text-muted-500 uppercase tracking-wider mb-3">{{ __('online_docs.shared_list') }}</p>
                            @forelse($sharedUsers as $sharedUser)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-muted-100 px-4 py-3 mb-2 last:mb-0 hover:bg-muted-50/50 transition-colors">
                                    <div class="min-w-0 flex items-center gap-3">
                                        <span class="shrink-0 inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary font-semibold text-xs">
                                            {{ strtoupper(substr($sharedUser->name ?: $sharedUser->email, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-main truncate">{{ $sharedUser->name ?: $sharedUser->email }}</p>
                                            <p class="text-xs text-muted-400 truncate">{{ $sharedUser->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form method="POST" action="{{ route('online-docs.docs.share.update', $document) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="user_id" value="{{ $sharedUser->id }}" />
                                            <select name="permission" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                                <option value="view" {{ $sharedUser->pivot->permission === 'view' ? 'selected' : '' }}>{{ __('online_docs.share_view') }}</option>
                                                <option value="edit" {{ $sharedUser->pivot->permission === 'edit' ? 'selected' : '' }}>{{ __('online_docs.share_edit') }}</option>
                                            </select>
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200 transition-colors">
                                                {{ __('online_docs.update') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('online-docs.docs.share.remove', $document) }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="user_id" value="{{ $sharedUser->id }}" />
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20 transition-colors">
                                                {{ __('online_docs.remove') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-muted-400">{{ __('online_docs.empty_shared') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        @if($isDocs)
            <div id="action-items-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 p-4" data-modal-overlay>
                <div class="w-full max-w-2xl rounded-2xl border border-muted-200 bg-white shadow-2xl shadow-main/10 max-h-[85vh] flex flex-col">
                    <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-muted-100 shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-muted-100 text-muted-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </span>
                            <div>
                                <h4 class="text-sm font-semibold text-main">{{ __('online_docs.extract_action_items') }}</h4>
                                <p class="text-[11px] text-muted-400">{{ __('online_docs.action_items_hint') }}</p>
                            </div>
                        </div>
                        <button type="button" data-close-modal class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-muted-400 hover:bg-muted-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-5 py-4 flex flex-col gap-3">
                        <div id="action-items-loading" class="hidden items-center gap-2 py-2">
                            <div class="w-4 h-4 rounded-full border-2 border-primary/20 border-t-primary animate-spin shrink-0"></div>
                            <span class="text-xs text-muted-500">{{ __('online_docs.action_items_loading') }}</span>
                        </div>
                        <div id="action-items-empty" class="hidden rounded-xl border border-muted-200 bg-muted-50 px-4 py-3 text-xs text-muted-500">{{ __('online_docs.action_items_empty') }}</div>
                        <div id="action-items-error" class="hidden rounded-xl border border-danger/20 bg-danger/5 px-4 py-3 text-xs text-danger">{{ __('online_docs.action_items_error') }}</div>
                        <div id="action-items-list" class="flex flex-col gap-2"></div>
                    </div>
                </div>
            </div>

            <div id="ai-summary-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/40 p-4" data-modal-overlay>
                <div class="w-full max-w-2xl rounded-2xl border border-violet-100 bg-white shadow-2xl max-h-[90vh] flex flex-col">
                    {{-- Header --}}
                    <div class="flex items-center justify-between gap-3 px-5 pt-5 pb-4 border-b border-muted-100">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-violet-100 text-violet-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </span>
                            <div>
                                <h4 class="text-sm font-semibold text-main leading-tight">{{ __('online_docs.ai_summary') }}</h4>
                                <p class="text-[11px] text-muted-400 leading-tight">{{ __('online_docs.ai_summary_hint') }}</p>
                            </div>
                        </div>
                        <button type="button" data-close-modal class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-muted-400 hover:bg-muted-100 hover:text-muted-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Scrollable body --}}
                    <div class="flex-1 overflow-y-auto px-5 py-4 flex flex-col gap-4">
                        {{-- Loading --}}
                        <div id="ai-summary-loading" class="hidden flex-col items-center gap-3 py-8">
                            <div class="w-8 h-8 rounded-full border-2 border-violet-200 border-t-violet-500 animate-spin"></div>
                            <p class="text-xs text-muted-500">{{ __('online_docs.ai_summary_loading') }}</p>
                        </div>

                        {{-- Error --}}
                        <div id="ai-summary-error" class="hidden rounded-xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm text-danger">
                            {{ __('online_docs.ai_summary_error') }}
                        </div>

                        {{-- Summary content --}}
                        <div id="ai-summary-content" class="hidden">
                            <div id="ai-summary-body" class="ai-summary-prose text-sm text-main leading-relaxed"></div>
                            <div id="ai-summary-citations" class="hidden mt-4 pt-4 border-t border-muted-100">
                                <p class="text-[11px] font-semibold text-muted-500 uppercase tracking-wider mb-2">Sources</p>
                                <div id="ai-summary-citations-list" class="flex flex-col gap-1.5"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div id="ai-summary-footer" class="hidden items-center justify-between gap-3 px-5 py-3 border-t border-muted-100 bg-muted-50/60 rounded-b-2xl">
                        <span id="ai-summary-meta" class="text-[11px] text-muted-400"></span>
                        <button type="button" id="ai-summary-copy" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-muted-200 text-xs text-muted-700 hover:bg-white hover:border-violet-200 hover:text-violet-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Copy
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('styles')
    <style>
        /* AI Summary modal prose */
        .ai-summary-prose ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .ai-summary-prose ul li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            line-height: 1.6;
        }
        .ai-summary-prose ul li::before {
            content: '';
            flex-shrink: 0;
            margin-top: 0.45rem;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #9333ea);
        }
        .ai-summary-prose ol {
            padding-left: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .ai-summary-prose ol li {
            line-height: 1.6;
        }
        .ai-summary-prose h1,
        .ai-summary-prose h2,
        .ai-summary-prose h3 {
            font-weight: 600;
            color: #3b0764;
            margin-top: 1rem;
            margin-bottom: 0.4rem;
            line-height: 1.4;
        }
        .ai-summary-prose h1 { font-size: 1rem; }
        .ai-summary-prose h2 { font-size: 0.9375rem; }
        .ai-summary-prose h3 { font-size: 0.875rem; }
        .ai-summary-prose p {
            margin: 0.4rem 0;
            line-height: 1.65;
        }
        .ai-summary-prose strong {
            color: #4c1d95;
            font-weight: 600;
        }
        .ai-summary-prose code {
            background: #f3eeff;
            color: #6d28d9;
            padding: 0.1em 0.35em;
            border-radius: 4px;
            font-size: 0.8125rem;
        }
        .ai-summary-prose blockquote {
            border-left: 3px solid #c4b5fd;
            padding-left: 0.875rem;
            color: #6b7280;
            margin: 0.5rem 0;
            font-style: italic;
        }
        .ai-summary-prose hr {
            border: none;
            border-top: 1px solid #ede9fe;
            margin: 0.75rem 0;
        }
    </style>
@endpush

@push('scripts')
    @if($isExcel)
        @vite(['resources/js/online_docs/excel.js'])
    @elseif(($isDocs || $isPowerpoint) && $onlyofficeConfig)
        <script src="{{ rtrim(config('onlyoffice.editor_url'), '/') }}/web-apps/apps/api/documents/api.js"></script>
        <script>
            const onlyofficeConfig = @json($onlyofficeConfig);
            const docEditor = new DocsAPI.DocEditor('onlyoffice-editor', onlyofficeConfig);
        </script>
    @else
        @vite(['resources/js/online_docs/editor.js'])
    @endif

    <script>
        const initOnlineDocsModals = () => {
            if (document.body.dataset.onlineDocsModalsBound === '1') {
                return;
            }
            document.body.dataset.onlineDocsModalsBound = '1';

            const openModal = (modal) => {
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = (modal) => {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (!document.querySelector('[data-modal-overlay]:not(.hidden)')) {
                    document.body.classList.remove('overflow-hidden');
                }
            };

            document.querySelectorAll('[data-open-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const modalId = button.getAttribute('data-open-modal');
                    const modal = document.getElementById(modalId);
                    openModal(modal);
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    closeModal(button.closest('[data-modal-overlay]'));
                });
            });

            document.querySelectorAll('[data-modal-overlay]').forEach((overlay) => {
                overlay.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        closeModal(overlay);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                document.querySelectorAll('[data-modal-overlay]:not(.hidden)').forEach((modal) => {
                    closeModal(modal);
                });
            });

            const shouldOpenImportModal = @json($shouldOpenImportModal);
            if (shouldOpenImportModal) {
                openModal(document.getElementById('import-modal'));
            }

            const shouldOpenShareModal = @json($shouldOpenShareModal);
            if (shouldOpenShareModal) {
                openModal(document.getElementById('share-modal'));
            }

            const actionItemsOpen = document.querySelector('[data-action-items-open]');
            const actionItemsList = document.getElementById('action-items-list');
            const actionItemsLoading = document.getElementById('action-items-loading');
            const actionItemsEmpty = document.getElementById('action-items-empty');
            const actionItemsError = document.getElementById('action-items-error');
            const actionItemsUrl = @json(route('online-docs.docs.action-items', $document));

            const renderActionItems = (items) => {
                if (!actionItemsList) {
                    return;
                }

                actionItemsList.innerHTML = '';
                items.forEach((item, index) => {
                    const row = document.createElement('div');
                    row.className = 'rounded-lg border border-muted-200 bg-white px-3 py-2';
                    const due = item.due_date ? `<span class="text-[11px] text-muted-500">Due: ${item.due_date}</span>` : '';
                    const priority = item.priority ? `<span class="text-[11px] text-muted-500 uppercase">${item.priority}</span>` : '';
                    row.innerHTML = `
                        <p class="text-sm text-main"><strong>${index + 1}.</strong> ${item.task || ''}</p>
                        <div class="mt-1 flex items-center gap-2">${due}${priority}</div>
                    `;
                    actionItemsList.appendChild(row);
                });
            };

            if (actionItemsOpen && actionItemsList) {
                actionItemsOpen.addEventListener('click', async () => {
                    actionItemsLoading?.classList.remove('hidden');
                    actionItemsEmpty?.classList.add('hidden');
                    actionItemsError?.classList.add('hidden');
                    actionItemsList.innerHTML = '';

                    try {
                        const response = await fetch(actionItemsUrl, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('action items request failed');
                        }

                        const payload = await response.json();
                        const items = Array.isArray(payload.items) ? payload.items : [];
                        if (!items.length) {
                            actionItemsEmpty?.classList.remove('hidden');
                        } else {
                            renderActionItems(items);
                        }
                    } catch (error) {
                        actionItemsError?.classList.remove('hidden');
                    } finally {
                        actionItemsLoading?.classList.add('hidden');
                    }
                });
            }

            const aiSummaryOpen = document.querySelector('[data-ai-summary-open]');
            const aiSummaryLoading = document.getElementById('ai-summary-loading');
            const aiSummaryError = document.getElementById('ai-summary-error');
            const aiSummaryContent = document.getElementById('ai-summary-content');
            const aiSummaryBody = document.getElementById('ai-summary-body');
            const aiSummaryCitations = document.getElementById('ai-summary-citations');
            const aiSummaryCitationsList = document.getElementById('ai-summary-citations-list');
            const aiSummaryFooter = document.getElementById('ai-summary-footer');
            const aiSummaryMeta = document.getElementById('ai-summary-meta');
            const aiSummaryCopy = document.getElementById('ai-summary-copy');
            const aiSummaryUrl = @json(route('online-docs.docs.summary', $document));
            let aiSummaryLoaded = false;
            let aiSummaryRawText = '';

            const showLoading = () => {
                aiSummaryLoading?.classList.remove('hidden');
                aiSummaryLoading?.classList.add('flex');
                aiSummaryError?.classList.add('hidden');
                aiSummaryContent?.classList.add('hidden');
                aiSummaryFooter?.classList.add('hidden');
                aiSummaryFooter?.classList.remove('flex');
            };

            const hideLoading = () => {
                aiSummaryLoading?.classList.add('hidden');
                aiSummaryLoading?.classList.remove('flex');
            };

            const renderSummary = (payload) => {
                const raw = (payload.summary || '').trim();
                aiSummaryRawText = raw;

                // Render markdown → sanitized HTML
                const html = (window.marked && window.DOMPurify)
                    ? window.DOMPurify.sanitize(window.marked.parse(raw, { breaks: true }))
                    : raw.replace(/\n/g, '<br>');

                if (aiSummaryBody) aiSummaryBody.innerHTML = html;

                // Citations
                const citations = Array.isArray(payload.citations) ? payload.citations : [];
                if (citations.length && aiSummaryCitations && aiSummaryCitationsList) {
                    aiSummaryCitationsList.innerHTML = citations.map((c) => {
                        const src = (c.source || c.id || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        const loc = (c.location || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        return `<div class="flex items-start gap-2 text-[11px] text-muted-600">
                            <span class="mt-0.5 inline-flex items-center justify-center w-4 h-4 rounded bg-violet-100 text-violet-600 font-semibold text-[10px] shrink-0">${c.rank || ''}</span>
                            <span class="truncate">${src}${loc ? ' <span class="text-muted-400">— ' + loc + '</span>' : ''}</span>
                        </div>`;
                    }).join('');
                    aiSummaryCitations.classList.remove('hidden');
                } else if (aiSummaryCitations) {
                    aiSummaryCitations.classList.add('hidden');
                }

                // Meta footer
                const chunks = payload.total_chunks || 0;
                const k = payload.n_clusters || 0;
                if (aiSummaryMeta && (chunks || k)) {
                    aiSummaryMeta.textContent = chunks
                        ? `${chunks} passage${chunks > 1 ? 's' : ''} · ${k} cluster${k > 1 ? 's' : ''}`
                        : '';
                }

                aiSummaryContent?.classList.remove('hidden');
                aiSummaryFooter?.classList.remove('hidden');
                aiSummaryFooter?.classList.add('flex');
            };

            if (aiSummaryOpen && aiSummaryContent) {
                aiSummaryOpen.addEventListener('click', async () => {
                    if (aiSummaryLoaded) return;

                    showLoading();

                    try {
                        const response = await fetch(aiSummaryUrl, {
                            headers: { 'Accept': 'application/json' },
                        });

                        if (!response.ok) throw new Error('summary request failed');

                        const payload = await response.json();
                        if (payload.error) {
                            if (aiSummaryError) aiSummaryError.textContent = payload.error;
                            aiSummaryError?.classList.remove('hidden');
                        } else {
                            renderSummary(payload);
                            aiSummaryLoaded = true;
                        }
                    } catch (_) {
                        aiSummaryError?.classList.remove('hidden');
                    } finally {
                        hideLoading();
                    }
                });
            }

            if (aiSummaryCopy) {
                aiSummaryCopy.addEventListener('click', async () => {
                    if (!aiSummaryRawText) return;
                    try {
                        await navigator.clipboard.writeText(aiSummaryRawText);
                        const orig = aiSummaryCopy.innerHTML;
                        aiSummaryCopy.textContent = 'Copied!';
                        setTimeout(() => { aiSummaryCopy.innerHTML = orig; }, 1500);
                    } catch (_) {}
                });
            }

            const sharePicker = document.getElementById('doc-share-picker');
            const shareInput = document.getElementById('doc-share-input');
            const shareToggle = document.getElementById('doc-share-toggle');
            const shareList = document.getElementById('doc-share-list');
            const shareEmpty = document.getElementById('doc-share-empty');

            if (sharePicker && shareInput && shareList) {
                const shareOptions = Array.from(shareList.querySelectorAll('.doc-share-option'));

                const showShareList = () => {
                    shareList.classList.remove('hidden');
                };

                const hideShareList = () => {
                    shareList.classList.add('hidden');
                };

                const filterShareOptions = (query) => {
                    const q = (query || '').trim().toLowerCase();
                    let visible = 0;

                    shareOptions.forEach((option) => {
                        const label = (option.dataset.label || '').toLowerCase();
                        const value = (option.dataset.value || '').toLowerCase();
                        const match = !q || label.includes(q) || value.includes(q);
                        option.classList.toggle('hidden', !match);
                        if (match) {
                            visible += 1;
                        }
                    });

                    if (shareEmpty) {
                        shareEmpty.classList.toggle('hidden', visible > 0);
                    }
                };

                shareInput.addEventListener('focus', () => {
                    filterShareOptions(shareInput.value);
                    showShareList();
                });

                shareInput.addEventListener('input', () => {
                    filterShareOptions(shareInput.value);
                    showShareList();
                });

                if (shareToggle) {
                    shareToggle.addEventListener('click', () => {
                        if (shareList.classList.contains('hidden')) {
                            filterShareOptions(shareInput.value);
                            showShareList();
                            shareInput.focus();
                        } else {
                            hideShareList();
                        }
                    });
                }

                shareOptions.forEach((option) => {
                    option.addEventListener('click', () => {
                        shareInput.value = option.dataset.value || option.textContent.trim();
                        hideShareList();
                    });
                });

                document.addEventListener('click', (event) => {
                    if (!sharePicker.contains(event.target)) {
                        hideShareList();
                    }
                });
            }

            const activeEditorsRoot = document.getElementById('active-editors');
            const activeEditorsList = document.getElementById('active-editors-list');
            if (activeEditorsRoot && activeEditorsList) {
                const presenceUrl = activeEditorsRoot.dataset.presenceUrl;
                const presenceTouchUrl = activeEditorsRoot.dataset.presenceTouchUrl;
                const canEdit = activeEditorsRoot.dataset.canEdit === 'true';
                const csrf = activeEditorsRoot.dataset.csrf;

                const renderEditors = (editors) => {
                    if (!Array.isArray(editors) || editors.length === 0) {
                        activeEditorsList.innerHTML = '<span class="text-muted-400">No one else</span>';
                        return;
                    }

                    activeEditorsList.innerHTML = editors.map((editor) => {
                        const name = (editor.name || 'User').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        const initials = (editor.initials || 'U').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        return `<span class="inline-flex items-center gap-1 rounded-full bg-muted-100 px-2 py-1 text-[11px] text-muted-700" title="${name}"><span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-primary font-semibold">${initials}</span><span class="max-w-[120px] truncate">${name}</span></span>`;
                    }).join('');
                };

                const ping = async () => {
                    try {
                        const response = await fetch(canEdit ? presenceTouchUrl : presenceUrl, {
                            method: canEdit ? 'POST' : 'GET',
                            headers: canEdit
                                ? {
                                    'X-CSRF-TOKEN': csrf,
                                    'Accept': 'application/json',
                                }
                                : {
                                    'Accept': 'application/json',
                                },
                        });
                        if (!response.ok) {
                            return;
                        }
                        const payload = await response.json();
                        renderEditors(payload.editors || []);
                    } catch (error) {
                        // ignore transient presence errors
                    }
                };

                ping();
                window.setInterval(ping, 8000);
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initOnlineDocsModals, { once: true });
        } else {
            initOnlineDocsModals();
        }

        document.addEventListener('livewire:navigated', () => {
            document.body.dataset.onlineDocsModalsBound = '0';
            initOnlineDocsModals();
        });
    </script>
@endpush
