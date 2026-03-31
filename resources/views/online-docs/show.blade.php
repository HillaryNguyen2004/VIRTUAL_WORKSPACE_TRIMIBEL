@extends('layout_dashboard')
@section('title', __('online_docs.title'))

@section('content')
    <div class="online-docs-editor-page flex flex-col gap-4 w-full mx-auto text-main px-2 md:px-4 lg:px-6 py-4 min-h-[calc(100vh-2rem)]">
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
        <div class="online-docs-toolbar relative z-[1200] flex flex-wrap items-center gap-3">
            <a href="{{ route('online-docs.home') }}" class="online-docs-action-btn px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                {{ __('online_docs.back_all') }}
            </a>
            @if($canEdit && ($isDocs || $isExcel || $isPowerpoint))
                <button type="button" data-open-modal="import-modal" class="online-docs-action-btn px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                    {{ $importLabel }}
                </button>
            @endif
            @if($isExcel)
                <a href="{{ route('online-docs.docs.xlsx', $document) }}" class="online-docs-action-btn px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                    {{ __('online_docs.export_xlsx') }}
                </a>
            @elseif($isPowerpoint)
                <a href="{{ route('online-docs.docs.export', $document) }}" class="online-docs-action-btn px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                    {{ __('online_docs.export_pptx') }}
                </a>
            @else
                <a href="{{ route('online-docs.docs.export', $document) }}" class="online-docs-action-btn px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                    {{ __('online_docs.export_docx') }}
                </a>
            @endif
            @if(($isDocs || $isPowerpoint) && $onlyofficeConfig && $canUpdateDocument)
                @if($isForcedViewMode)
                    <a href="{{ route('online-docs.docs.show', ['document' => $document, 'mode' => 'edit']) }}" class="online-docs-action-btn online-docs-primary-btn px-4 py-2 rounded-xl bg-secondary text-white text-sm font-medium hover:bg-secondary/90">
                        {{ __('online_docs.enable_edit') }}
                    </a>
                @else
                    <a href="{{ route('online-docs.docs.show', ['document' => $document, 'mode' => 'view']) }}" class="online-docs-action-btn px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                        {{ __('online_docs.view_only') }}
                    </a>
                @endif
            @endif
            @can('share', $document)
                <button type="button" data-open-modal="share-modal" class="online-docs-action-btn px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                    Share with others
                </button>
            @endcan
        </div>

        <div
            id="active-editors"
            class="online-docs-presence flex flex-wrap items-center gap-2 text-xs text-muted-600"
            data-presence-url="{{ $presenceUrl }}"
            data-presence-touch-url="{{ $presenceTouchUrl }}"
            data-can-edit="{{ $canEdit ? 'true' : 'false' }}"
            data-csrf="{{ csrf_token() }}"
        >
            <span class="font-medium text-muted-700">Editing:</span>
            <div id="active-editors-list" class="flex flex-wrap items-center gap-2">
                <span class="text-muted-400">No one else</span>
            </div>
        </div>

        <div class="online-docs-editor-stage relative z-10">
            @if($isExcel)
                <div class="online-docs-editor-surface rounded-xl border border-muted-200 bg-white">
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
                <div class="online-docs-editor-surface rounded-xl border border-muted-200 bg-white">
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
                    <div class="online-docs-editor-surface bg-white rounded-xl border border-muted-200 overflow-hidden tiptap-editor">
                        <div id="doc-editor" class="min-h-[calc(100vh-190px)] p-4 sm:p-6"></div>
                    </div>
                </form>
            @endif
        </div>

        @can('update', $document)
            @if($isDocs || $isExcel || $isPowerpoint)
                <div id="import-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/40 p-4" data-modal-overlay>
                    <div class="w-full max-w-lg rounded-xl border border-muted-200 bg-white p-5">
                        @if(session('docx_error') || session('xlsx_error') || session('pptx_error'))
                            <div class="mb-3 rounded-lg bg-danger/10 text-danger text-xs px-3 py-2">
                                {{ session('docx_error') ?: (session('xlsx_error') ?: session('pptx_error')) }}
                            </div>
                        @endif
                        @error($importField)
                            <div class="mb-3 rounded-lg bg-danger/10 text-danger text-xs px-3 py-2">
                                {{ $message }}
                            </div>
                        @enderror
                        <form method="POST" action="{{ $importAction }}" enctype="multipart/form-data" class="flex flex-col gap-3">
                            @csrf
                            <input type="file" name="{{ $importField }}" accept="{{ $importAccept }}" required class="text-sm" />
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" data-close-modal class="px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                                    {{ __('online_docs.cancel') }}
                                </button>
                                <button type="submit" class="px-4 py-2 rounded-xl bg-secondary text-white hover:bg-secondary/90 text-sm font-medium">
                                    {{ $importLabel }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        @can('share', $document)
            <div id="share-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/40 p-4" data-modal-overlay>
                <div class="w-full max-w-2xl rounded-xl border border-muted-200 bg-white p-5 max-h-[85vh] overflow-y-auto">
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
                            <button type="button" id="doc-share-toggle" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md border border-muted-200 px-2 py-1 text-xs text-muted-600 hover:bg-muted-100">v</button>
                            <div id="doc-share-list" class="absolute z-20 mt-2 hidden max-h-56 w-full overflow-auto rounded-xl border border-muted-200 bg-white shadow-lg">
                                @foreach($shareCandidates as $candidate)
                                    <button
                                        type="button"
                                        class="doc-share-option flex w-full flex-col gap-0.5 px-3 py-2 text-left text-sm hover:bg-muted-50"
                                        data-value="{{ $candidate->email }}"
                                        data-label="{{ $candidate->name ? $candidate->name . ' - ' : '' }}{{ $candidate->email }}"
                                    >
                                        <span class="text-muted-800">{{ $candidate->name ?: $candidate->email }}</span>
                                        <span class="text-xs text-muted-400">{{ $candidate->email }}</span>
                                    </button>
                                @endforeach
                                <div id="doc-share-empty" class="hidden px-3 py-2 text-xs text-muted-400">No matches</div>
                            </div>
                        </div>
                        <select name="permission" class="rounded-xl border border-muted-200 px-4 py-2 text-sm">
                            <option value="view">{{ __('online_docs.share_view') }}</option>
                            <option value="edit">{{ __('online_docs.share_edit') }}</option>
                        </select>
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" data-close-modal class="px-4 py-2 rounded-xl border border-muted-200 text-sm font-medium text-muted-700 hover:bg-muted-50">
                                {{ __('online_docs.cancel') }}
                            </button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-secondary text-white hover:bg-secondary/90 text-sm font-medium">
                                Share with others
                            </button>
                        </div>
                    </form>

                    <div class="mt-5 border-t border-muted-200 pt-4">
                        <h4 class="text-sm font-semibold text-main mb-3">{{ __('online_docs.shared_list') }}</h4>
                        @forelse($sharedUsers as $sharedUser)
                            <div class="rounded-xl border border-muted-200 p-3 mb-3 last:mb-0">
                                <div class="flex items-center justify-between gap-3 mb-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-main truncate">{{ $sharedUser->name ?: $sharedUser->email }}</p>
                                        <p class="text-xs text-muted-500 truncate">{{ $sharedUser->email }}</p>
                                    </div>
                                    <span class="text-xs text-muted-500">{{ $sharedUser->pivot->permission }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="POST" action="{{ route('online-docs.docs.share.update', $document) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="user_id" value="{{ $sharedUser->id }}" />
                                        <select name="permission" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                            <option value="view" {{ $sharedUser->pivot->permission === 'view' ? 'selected' : '' }}>{{ __('online_docs.share_view') }}</option>
                                            <option value="edit" {{ $sharedUser->pivot->permission === 'edit' ? 'selected' : '' }}>{{ __('online_docs.share_edit') }}</option>
                                        </select>
                                        <button type="submit" class="px-3 py-1 rounded-lg bg-muted-100 text-muted-700 text-xs hover:bg-muted-200">
                                            {{ __('online_docs.update') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('online-docs.docs.share.remove', $document) }}">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="user_id" value="{{ $sharedUser->id }}" />
                                        <button type="submit" class="px-3 py-1 rounded-lg bg-danger/10 text-danger text-xs hover:bg-danger/20">
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
        @endcan
    </div>
@endsection

@push('styles')
    <style>
        .online-docs-editor-page {
            background:
                radial-gradient(circle at 12% 0%, rgba(124, 58, 237, 0.18) 0%, rgba(124, 58, 237, 0) 42%),
                radial-gradient(circle at 88% 100%, rgba(168, 85, 247, 0.16) 0%, rgba(168, 85, 247, 0) 44%),
                linear-gradient(180deg, #fbf8ff 0%, #f5efff 100%);
        }

        .online-docs-toolbar,
        .online-docs-presence {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e7dcff;
            border-radius: 14px;
            padding: 10px;
            backdrop-filter: blur(3px);
        }

        .online-docs-action-btn {
            border-color: #d9c6ff !important;
            color: #5b21b6 !important;
            background: #f8f3ff !important;
        }

        .online-docs-action-btn:hover {
            background: #efe3ff !important;
        }

        .online-docs-primary-btn {
            background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%) !important;
            color: #fff !important;
            border-color: transparent !important;
            box-shadow: 0 10px 24px rgba(124, 58, 237, 0.28);
        }

        .online-docs-editor-surface {
            border-color: #d8c7ff !important;
            box-shadow: 0 12px 30px rgba(76, 29, 149, 0.12);
        }
    </style>
@endpush

@push('scripts')
    @if($isExcel)
        @vite(['resources/js/online_docs/excel.js'])
    @elseif(($isDocs || $isPowerpoint) && $onlyofficeConfig)
        <script src="{{ rtrim(config('onlyoffice.document_server_url'), '/') }}/web-apps/apps/api/documents/api.js"></script>
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
