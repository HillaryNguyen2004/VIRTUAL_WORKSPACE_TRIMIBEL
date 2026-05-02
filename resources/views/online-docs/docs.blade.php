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

        @include('online-docs.partials.home-nav', ['currentType' => null])

        @php
            $typeLabels = [
                'docs' => __('online_docs.docs_label'),
                'excel' => __('online_docs.excel_label'),
                'powerpoint' => __('online_docs.powerpoint_label'),
            ];
        @endphp

        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white rounded-xl p-5 border border-muted-200">
                <h3 class="text-lg font-semibold text-main">{{ __('online_docs.recent_docs') }}</h3>
                <p class="text-xs text-muted-400 mb-4">{{ __('online_docs.recent_docs_hint') }}</p>
                <div class="flex flex-col gap-3">
                    @forelse($recentDocuments as $document)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-muted-100 px-4 py-3" draggable="true" data-doc-drag data-doc-id="{{ $document->id }}" data-doc-title="{{ $document->title }}">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-main truncate">{{ $document->title }}</p>
                                <p class="text-xs text-muted-400 flex items-center gap-2 flex-wrap">
                                    {{ $typeLabels[$document->type] ?? $document->type }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-xs text-muted-400">{{ $document->updated_at?->diffForHumans() }}</span>
                                <a href="{{ route('online-docs.docs.show', $document) }}" class="text-xs font-medium text-primary hover:text-primary-hover">
                                    {{ __('online_docs.open') }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted-400">{{ __('online_docs.empty_recent') }}</p>
                    @endforelse
                </div>
                @if($recentDocuments->hasPages())
                    <div class="mt-4 border-t border-muted-100 pt-3 flex items-center justify-between gap-3">
                        <span class="text-xs text-muted-400">
                            {{ $recentDocuments->firstItem() }}-{{ $recentDocuments->lastItem() }} / {{ $recentDocuments->total() }}
                        </span>
                        <div class="flex items-center gap-2">
                            @if($recentDocuments->onFirstPage())
                                <span class="px-3 py-1.5 rounded-lg border border-muted-200 text-xs text-muted-300">Prev</span>
                            @else
                                <a href="{{ $recentDocuments->appends(request()->except('recent_page'))->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-muted-200 text-xs text-muted-600 hover:bg-muted-50">Prev</a>
                            @endif

                            <span class="px-2 py-1 text-xs text-muted-500">{{ $recentDocuments->currentPage() }}/{{ $recentDocuments->lastPage() }}</span>

                            @if($recentDocuments->hasMorePages())
                                <a href="{{ $recentDocuments->appends(request()->except('recent_page'))->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-muted-200 text-xs text-muted-600 hover:bg-muted-50">Next</a>
                            @else
                                <span class="px-3 py-1.5 rounded-lg border border-muted-200 text-xs text-muted-300">Next</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-muted-200">
            <div class="flex flex-col gap-2 mb-4">
                <h3 class="text-lg font-semibold text-main">{{ __('online_docs.personal_storage') }}</h3>
                <div class="flex flex-wrap items-center gap-2 text-xs text-muted-400 rounded-xl border border-muted-200 bg-muted-50 px-3 py-2">
                    <a href="{{ route('online-docs.home') }}" class="font-medium text-main hover:text-primary">{{ __('online_docs.storage_root') }}</a>
                    @foreach($folderBreadcrumbs as $breadcrumb)
                        <span class="text-muted-300">/</span>
                        <a href="{{ route('online-docs.home', ['folder' => $breadcrumb['id']]) }}" class="rounded-md bg-white px-2 py-0.5 text-muted-600 hover:text-primary border border-muted-200">
                            {{ $breadcrumb['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if(session('storage_error'))
                <div class="mb-4 rounded-lg bg-danger/10 text-danger text-xs px-3 py-2">
                    {{ session('storage_error') }}
                </div>
            @endif
            @if(session('storage_success'))
                <div class="mb-4 rounded-lg bg-success/10 text-success text-xs px-3 py-2">
                    {{ session('storage_success') }}
                </div>
            @endif
            <div
                id="storage-root"
                class="flex flex-col gap-4"
                data-current-folder="{{ $currentFolder?->id }}"
                data-upload-url="{{ route('online-docs.files.store') }}"
                data-move-url="{{ route('online-docs.storage.move') }}"
                data-bulk-move-url="{{ route('online-docs.storage.bulk-move') }}"
                data-bulk-delete-url="{{ route('online-docs.storage.bulk-delete') }}"
                data-link-base-url="{{ url('/online-docs/links') }}"
                data-csrf="{{ csrf_token() }}"
            >
                <div class="flex flex-col gap-3 rounded-2xl border border-muted-200 bg-muted-50/70 p-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-2">
                        <div class="relative" data-storage-new-menu>
                            <button
                                type="button"
                                data-storage-new-toggle
                                class="inline-flex items-center gap-2 rounded-xl bg-secondary px-4 py-2 text-sm font-medium text-white hover:bg-secondary/90"
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
                        </div>
                        <a href="{{ route('online-docs.home', $currentFolder?->id ? ['folder' => $currentFolder->id] : []) }}" class="px-3 py-2 rounded-xl border border-muted-200 bg-white text-sm text-muted-600 hover:bg-muted-50">
                            {{ __('online_docs.refresh') }}
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            type="text"
                            id="storage-search"
                            class="rounded-xl border border-muted-200 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                            placeholder="{{ __('online_docs.search_placeholder') }}"
                        />
                        <div class="flex items-center gap-2 rounded-xl border border-muted-200 p-1">
                            <button type="button" class="px-3 py-1 text-xs rounded-lg bg-muted-100 text-muted-700" data-view-toggle="grid">{{ __('online_docs.view_grid') }}</button>
                            <button type="button" class="px-3 py-1 text-xs rounded-lg text-muted-700" data-view-toggle="list">{{ __('online_docs.view_list') }}</button>
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
                    <button type="submit" class="px-3 py-2 rounded-lg bg-secondary text-white hover:bg-secondary/90 text-sm font-medium">
                        {{ __('online_docs.create_folder') }}
                    </button>
                    <button type="button" data-storage-folder-cancel class="px-3 py-2 rounded-lg border border-muted-200 text-sm text-muted-600 hover:bg-muted-50">
                        {{ __('online_docs.cancel') }}
                    </button>
                </form>

                <form id="storage-upload-form" method="POST" action="{{ route('online-docs.files.store') }}" enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="hidden" name="folder_id" value="{{ $currentFolder?->id }}" />
                    <input id="storage-upload-input" type="file" name="file" required class="hidden" data-storage-upload-input />
                </form>

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

                <div class="relative" id="storage-dropzone" data-dropzone>
                    <div class="absolute inset-0 hidden items-center justify-center rounded-2xl border-2 border-dashed border-primary/40 bg-primary/5 text-sm text-primary" data-drop-overlay>
                        {{ __('online_docs.drop_to_upload') }}
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4" data-view="grid">
                        @foreach($folders as $folder)
                            <div class="storage-item group rounded-2xl border border-muted-100 bg-white p-4 shadow-sm" data-item-type="folder" data-item-id="{{ $folder->id }}" data-item-name="{{ $folder->name }}" data-folder-drop draggable="true">
                                <div class="flex items-start justify-between gap-2">
                                    <label class="flex items-center gap-2 text-sm text-muted-500">
                                        <input type="checkbox" class="storage-select" data-item-type="folder" data-item-id="{{ $folder->id }}" />
                                        <span>{{ __('online_docs.folder') }}</span>
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
                            <div class="storage-item rounded-2xl border border-muted-100 bg-white p-4 shadow-sm" data-item-type="file" data-item-id="{{ $file->id }}" data-item-name="{{ $file->original_name }}" draggable="true">
                                <div class="flex items-start justify-between gap-2">
                                    <label class="flex items-center gap-2 text-sm text-muted-500">
                                        <input type="checkbox" class="storage-select" data-item-type="file" data-item-id="{{ $file->id }}" />
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
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ $openUrl }}" class="mt-3 block text-sm font-semibold text-main truncate hover:text-primary">{{ $file->original_name }}</a>
                                <p class="text-xs text-muted-400 mt-1">{{ number_format($file->size / 1024, 1) }} KB</p>
                            </div>
                        @endforeach

                        @foreach($links as $link)
                            <div class="storage-item rounded-2xl border border-muted-100 bg-white p-4 shadow-sm" data-item-type="link" data-item-id="{{ $link->id }}" data-item-name="{{ $link->name }}" draggable="true">
                                <div class="flex items-start justify-between gap-2">
                                    <label class="flex items-center gap-2 text-sm text-muted-500">
                                        <input type="checkbox" class="storage-select" data-item-type="link" data-item-id="{{ $link->id }}" />
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
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('online-docs.docs.show', $link->document) }}" class="mt-3 block text-sm font-semibold text-main truncate hover:text-primary">{{ $link->name }}</a>
                                <p class="text-xs text-muted-400 mt-1">{{ __('online_docs.shortcut') }}</p>
                            </div>
                        @endforeach

                        @if($folders->isEmpty() && $files->isEmpty() && $links->isEmpty())
                            <p class="text-sm text-muted-400">{{ __('online_docs.empty_storage') }}</p>
                        @endif
                    </div>

                    <div class="hidden" data-view="list">
                        <div class="rounded-2xl border border-muted-100">
                            <div class="grid grid-cols-12 gap-3 px-4 py-2 text-xs font-semibold text-muted-500">
                                <div class="col-span-6">{{ __('online_docs.name') }}</div>
                                <div class="col-span-2">{{ __('online_docs.type') }}</div>
                                <div class="col-span-2">{{ __('online_docs.modified') }}</div>
                                <div class="col-span-2 text-right">{{ __('online_docs.actions') }}</div>
                            </div>
                            <div class="divide-y divide-muted-100">
                                @foreach($folders as $folder)
                                    <div class="storage-item grid grid-cols-12 gap-3 px-4 py-2" data-item-type="folder" data-item-id="{{ $folder->id }}" data-item-name="{{ $folder->name }}" data-folder-drop draggable="true">
                                        <div class="col-span-6 flex items-center gap-2">
                                            <input type="checkbox" class="storage-select" data-item-type="folder" data-item-id="{{ $folder->id }}" />
                                            <a href="{{ route('online-docs.home', ['folder' => $folder->id]) }}" class="text-sm font-medium text-main truncate hover:text-primary">{{ $folder->name }}</a>
                                        </div>
                                        <div class="col-span-2 text-xs text-muted-400">{{ __('online_docs.folder') }}</div>
                                        <div class="col-span-2 text-xs text-muted-400">{{ $folder->updated_at?->diffForHumans() }}</div>
                                        <div class="col-span-2 flex items-center justify-end">
                                            <div class="relative" data-menu>
                                                <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                        <circle cx="4" cy="10" r="1.5" />
                                                        <circle cx="10" cy="10" r="1.5" />
                                                        <circle cx="16" cy="10" r="1.5" />
                                                    </svg>
                                                </button>
                                                <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
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
                                    <div class="storage-item grid grid-cols-12 gap-3 px-4 py-2" data-item-type="file" data-item-id="{{ $file->id }}" data-item-name="{{ $file->original_name }}" draggable="true">
                                        <div class="col-span-6 flex items-center gap-2">
                                            <input type="checkbox" class="storage-select" data-item-type="file" data-item-id="{{ $file->id }}" />
                                            <a href="{{ $openUrl }}" class="text-sm font-medium text-main truncate hover:text-primary">{{ $file->original_name }}</a>
                                        </div>
                                        <div class="col-span-2 text-xs text-muted-400">{{ __('online_docs.file') }}</div>
                                        <div class="col-span-2 text-xs text-muted-400">{{ $file->updated_at?->diffForHumans() }}</div>
                                        <div class="col-span-2 flex items-center justify-end gap-2">
                                            <a href="{{ $openUrl }}" class="text-xs text-primary hover:text-primary-hover">{{ __('online_docs.preview') }}</a>
                                            <a href="{{ route('online-docs.files.download', $file) }}" class="text-xs text-primary hover:text-primary-hover">{{ __('online_docs.download') }}</a>
                                            <div class="relative" data-menu>
                                                <button type="button" data-menu-trigger class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-muted-200 text-muted-600 hover:bg-muted-100" aria-label="{{ __('online_docs.more_actions') }}">
                                                    <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                                        <circle cx="4" cy="10" r="1.5" />
                                                        <circle cx="10" cy="10" r="1.5" />
                                                        <circle cx="16" cy="10" r="1.5" />
                                                    </svg>
                                                </button>
                                                <div class="absolute right-0 z-10 mt-2 hidden w-56 rounded-xl border border-muted-200 bg-white p-3 shadow-lg" data-menu-panel>
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @foreach($links as $link)
                                    <div class="storage-item grid grid-cols-12 gap-3 px-4 py-2" data-item-type="link" data-item-id="{{ $link->id }}" data-item-name="{{ $link->name }}" draggable="true">
                                        <div class="col-span-6 flex items-center gap-2">
                                            <input type="checkbox" class="storage-select" data-item-type="link" data-item-id="{{ $link->id }}" />
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($folders->isEmpty() && $files->isEmpty() && $links->isEmpty())
                                    <div class="px-4 py-3 text-sm text-muted-400">{{ __('online_docs.empty_storage') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/online_docs/storage.js'])
@endpush
