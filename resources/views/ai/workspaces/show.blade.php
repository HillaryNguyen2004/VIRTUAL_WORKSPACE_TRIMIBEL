@extends('layout_dashboard')
@section('title', $workspace->name)

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
            <div class="flex items-center gap-4">
                <x-back-btn :route="'ai-workspaces.index'" />
                <div>
                    <h1 class="font-bold text-3xl text-main tracking-tight">{{ $workspace->name }}</h1>
                    @if ($workspace->description)
                        <p class="text-muted-500 text-sm mt-2">{{ $workspace->description }}</p>
                    @endif
                </div>
            </div>

            <div class="flex gap-2">
                <button onclick="openSummaryModal('workspace', null, '{{ $workspace->id }}')"
                    class="inline-flex items-center justify-center gap-2 bg-white border border-purple-300 px-4 py-2 rounded-xl text-sm font-medium text-purple-700 hover:bg-purple-50 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Summarize Workspace
                </button>
                @can('update', $workspace)
                    <a href="{{ route('ai-workspaces.edit', $workspace) }}"
                        class="inline-flex items-center justify-center gap-2 bg-white border border-blue-600/30 px-4 py-2 rounded-xl text-sm font-medium text-blue-600 hover:bg-blue-50 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        {{ __('ai.edit') }}
                    </a>
                    <form action="{{ route('ai-workspaces.destroy', $workspace) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 bg-white border border-danger/30 px-4 py-2 rounded-xl text-sm font-medium text-danger hover:bg-danger/5 transition-all shadow-sm"
                            onclick="return confirm('{{ __('ai.confirm_delete') }}')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            {{ __('ai.delete') }}
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-4 text-green-800 text-sm">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-4 text-yellow-800 text-sm">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('warning') }}
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-red-800 text-sm">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 animate-fade-in-up">
            <div class="rounded-lg border border-muted-200 bg-white p-4">
                <div class="text-xs text-muted-600 mb-1">{{ __('ai.total_files') }}</div>
                <div class="text-2xl font-bold text-main">{{ $stats['total_files'] }}</div>
            </div>
            <div class="rounded-lg border border-muted-200 bg-white p-4">
                <div class="text-xs text-muted-600 mb-1">{{ __('ai.storage_used') }}</div>
                <div class="text-2xl font-bold text-main">{{ formatBytes($stats['storage_size'], 2) }}</div>
            </div>
            <div class="rounded-lg border border-muted-200 bg-white p-4">
                <div class="text-xs text-muted-600 mb-1">{{ __('ai.total_chunks') }}</div>
                <div class="text-2xl font-bold text-main">{{ $stats['total_chunks'] }}</div>
            </div>
            <div class="rounded-lg border border-muted-200 bg-white p-4">
                <div class="text-xs text-muted-600 mb-1">{{ __('ai.last_ingested') }}</div>
                <div class="text-2xl font-medium text-main">
                    @if($workspace->last_ingested_at)
                        {{ $workspace->last_ingested_at->diffForHumans() }}
                    @else
                        {{ __('ai.never') }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        @can('upload', $workspace)
            <div class="bg-white rounded-2xl border border-muted-200 shadow-sm p-6 md:p-8 animate-fade-in-up [animation-delay:100ms]">
                <h2 class="text-lg md:text-xl font-semibold text-main mb-2">{{ __('ai.upload_files') }}</h2>
                <p class="text-muted-600 text-sm mb-6">{{ __('ai.upload_files_desc') }}</p>

                <form action="{{ route('ai-workspaces.upload-files', $workspace) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-6">
                        <label for="files-batch" class="block" id="batch-dropzone-label">
                            <div id="batch-dropzone"
                                class="border-2 border-dashed border-muted-300 rounded-lg p-8 text-center hover:border-primary/50 hover:bg-primary/5 transition-colors cursor-pointer">
                                <svg class="mx-auto h-12 w-12 text-muted-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                </svg>
                                <p class="text-sm font-medium text-muted-700">{{ __('ai.drag_files') }}</p>
                                <p class="text-xs text-muted-500 mt-1">{{ __('ai.supported_formats') }}: PDF, TXT, MD, DOCX, XLSX, CSV</p>
                            </div>
                            <input
                                type="file"
                                name="files[]"
                                id="files-batch"
                                multiple
                                accept=".pdf,.txt,.md,.docx,.pptx,.xlsx,.csv"
                                class="hidden">
                        </label>
                        <p class="text-sm text-muted-600 mt-2" id="batchFileList"></p>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-primary-gradient px-6 py-3 text-white text-sm font-semibold shadow-lg shadow-primary/20 transition-all hover:shadow-lg focus:ring-4 focus:ring-primary/30 active:scale-95">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ __('ai.upload_files') }}
                    </button>
                </form>

                @if($upload_errors ?? false)
                    <div class="mt-4 space-y-1">
                        @foreach($upload_errors as $error)
                            <p class="text-red-600 text-xs">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
            </div>
        @endcan

        <!-- Ingest Section -->
        @can('ingest', $workspace)
        @if($stats['pending_files'] > 0 || $stats['failed_files'] > 0)
            @php
                $reingestCount = $stats['pending_files'] + $stats['failed_files'];
            @endphp
            <div class="bg-blue-50 rounded-2xl border border-blue-200 p-6 md:p-8 animate-fade-in-up [animation-delay:200ms]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-blue-900">{{ __('ai.ingest_required') }}</h2>
                        <p class="text-blue-700 text-sm mt-2">
                            {{ __('ai.reingest_ready', ['count' => $reingestCount]) }}
                            @if($stats['pending_files'] > 0 && $stats['failed_files'] > 0)
                                <span class="block mt-1 text-blue-600">
                                    {{ $stats['pending_files'] }} {{ __('ai.pending') }}, {{ $stats['failed_files'] }} {{ __('ai.failed') }}
                                </span>
                            @elseif($stats['pending_files'] > 0)
                                <span class="block mt-1 text-blue-600">
                                    {{ $stats['pending_files'] }} {{ __('ai.pending') }}
                                </span>
                            @else
                                <span class="block mt-1 text-blue-600">
                                    {{ $stats['failed_files'] }} {{ __('ai.failed') }}
                                </span>
                            @endif
                        </p>
                    </div>
                    <form id="ingest-form" action="{{ route('ai-workspaces.ingest', $workspace) }}" method="POST" class="flex-shrink-0">
                        @csrf
                        <button id="ingest-btn"
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                            <svg id="ingest-icon" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            {{ __('ai.start_ingest') }}
                        </button>
                    </form>
                </div>
            </div>
        @endif
        @endcan

        <!-- Files List -->
        <div class="bg-white rounded-2xl border border-muted-200 shadow-sm overflow-hidden animate-fade-in-up [animation-delay:200ms]">
            <div class="px-6 md:px-8 py-6 border-b border-muted-200">
                <h2 class="text-lg md:text-xl font-semibold text-main">{{ __('ai.workspace_files') }}</h2>
            </div>

            @if($files->count() === 0)
                <div class="px-6 md:px-8 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-muted-600 text-sm">{{ __('ai.no_files') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-muted-200 bg-muted-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-muted-700">{{ __('ai.file_name') }}</th>
                                <th class="px-6 py-3 text-left font-semibold text-muted-700">{{ __('ai.file_size') }}</th>
                                <th class="px-6 py-3 text-left font-semibold text-muted-700">{{ __('ai.status') }}</th>
                                <th class="px-6 py-3 text-left font-semibold text-muted-700">{{ __('ai.chunks') }}</th>
                                <th class="px-6 py-3 text-left font-semibold text-muted-700">{{ __('ai.uploaded_at') }}</th>
                                <th class="px-6 py-3 text-right font-semibold text-muted-700">{{ __('ai.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-muted-200">
                            @foreach($files as $file)
                                <tr class="hover:bg-muted-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-muted-100 flex items-center justify-center">
                                                @if (str_ends_with(strtolower($file->file_name), '.pdf'))
                                                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>
                                                @elseif (str_ends_with(strtolower($file->file_name), '.docx') || str_ends_with(strtolower($file->file_name), '.doc') || str_ends_with(strtolower($file->file_name), '.txt') || str_ends_with(strtolower($file->file_name), '.md'))
                                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>
                                                @elseif (str_ends_with(strtolower($file->file_name), '.xlsx') || str_ends_with(strtolower($file->file_name), '.xls') || str_ends_with(strtolower($file->file_name), '.csv'))
                                                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-main truncate">{{ $file->original_name }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-muted-600">
                                        {{ formatBytes($file->file_size, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($file->ingest_status === 'pending')
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('ai.pending') }}</span>
                                        @elseif($file->ingest_status === 'processing')
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800">{{ __('ai.processing') }}</span>
                                        @elseif($file->ingest_status === 'completed')
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-green-100 text-green-800">{{ __('ai.completed') }}</span>
                                        @elseif($file->ingest_status === 'failed')
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-red-100 text-red-800">{{ __('ai.failed') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-muted-600">
                                        {{ $file->chunk_count > 0 ? $file->chunk_count : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-muted-600 text-xs">
                                        {{ $file->created_at->format('d/m/Y, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-3">
                                            @if($file->ingest_status === 'completed')
                                                <button type="button"
                                                    onclick="openSummaryModal('document', '{{ $file->file_path }}', '{{ $workspace->id }}', '{{ addslashes($file->original_name) }}')"
                                                    title="Summarize this document"
                                                    class="p-1.5 rounded-lg text-muted-400 hover:bg-purple-50 hover:text-purple-600 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </button>
                                            @endif
                                            {{-- <a href="{{ route('workspace-files.preview', $file) }}" target="_blank"
                                                class="text-blue-600 hover:text-blue-700 text-xs font-medium">Preview</a> --}}
                                            @can('ingest', $workspace)
                                                @if(in_array($file->ingest_status, ['pending', 'failed']))
                                                    <form action="{{ route('workspace-files.ingest', $file) }}" method="POST" class="inline" data-ingest-file-form>
                                                        @csrf
                                                        <button type="submit"
                                                            title="{{ __('ai.retry_ingest') }}"
                                                            class="p-1.5 rounded-lg text-muted-400 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                                                            data-ingest-file-button>
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                                                <polyline points="1 4 1 10 7 10"></polyline>
                                                                <path d="M3.51 15a9 9 0 1 0 .49-3.86L1 10"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                            <a href="{{ route('workspace-files.download', $file) }}"
                                                class="p-1.5 rounded-lg text-muted-400 hover:bg-primary/10 hover:text-primary transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                                    class="w-4 h-4 fill-current">
                                                    <path d="M352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 306.7L246.6 265.3C234.1 252.8 213.8 252.8 201.3 265.3C188.8 277.8 188.8 298.1 201.3 310.6L297.3 406.6C309.8 419.1 330.1 419.1 342.6 406.6L438.6 310.6C451.1 298.1 451.1 277.8 438.6 265.3C426.1 252.8 405.8 252.8 393.3 265.3L352 306.7L352 96zM160 384C124.7 384 96 412.7 96 448L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 448C544 412.7 515.3 384 480 384L433.1 384L376.5 440.6C345.3 471.8 294.6 471.8 263.4 440.6L206.9 384L160 384zM464 440C477.3 440 488 450.7 488 464C488 477.3 477.3 488 464 488C450.7 488 440 477.3 440 464C440 450.7 450.7 440 464 440z"/>
                                                </svg>
                                            </a>
                                            @can('delete', $file)
                                                <form action="{{ route('workspace-files.delete', $file) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        onclick="return confirm('{{ __('ai.confirm_delete') }}')"
                                                        class="p-1.5 rounded-lg text-muted-400 hover:bg-danger/10 hover:text-danger transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                                            class="w-4 h-4 fill-current">
                                                            <path
                                                                d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($files->hasPages())
                    <div class="px-6 py-4 border-t border-muted-200">
                        {{ $files->links() }}
                    </div>
                @endif
            @endif
        </div>

        <div id="ingest-loading-overlay" class="ingest-loading-overlay hidden" aria-live="polite" aria-busy="true">
            <div class="ingest-spinner"></div>
            <p class="text-sm font-medium text-slate-700">{{ __('ai.loading_ingest') }}</p>
        </div>
    </div>

    <!-- File upload script -->
    <script>
        const batchFileInput = document.getElementById('files-batch');
        const batchDropzone = document.getElementById('batch-dropzone');
        const batchFileList = document.getElementById('batchFileList');

        if (batchFileInput && batchFileList) {
            batchFileInput.addEventListener('change', () => {
                if (!batchFileInput.files || batchFileInput.files.length === 0) {
                    batchFileList.textContent = '';
                    return;
                }
                batchFileList.textContent = Array.from(batchFileInput.files).map((f) => f.name).join(', ');
            });
        }

        if (batchDropzone && batchFileInput) {
            batchDropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                batchDropzone.classList.add('border-primary', 'bg-primary/5');
            });

            batchDropzone.addEventListener('dragleave', () => {
                batchDropzone.classList.remove('border-primary', 'bg-primary/5');
            });

            batchDropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                batchDropzone.classList.remove('border-primary', 'bg-primary/5');
                batchFileInput.files = e.dataTransfer.files;
                batchFileInput.dispatchEvent(new Event('change'));
            });
        }

        const ingestForm = document.getElementById('ingest-form');
        const ingestBtn = document.getElementById('ingest-btn');
        const ingestIcon = document.getElementById('ingest-icon');
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
                const button = form.querySelector('[data-ingest-file-button]');
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

    {{-- Summary Modal --}}
    <div id="summaryModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50" onclick="closeSummaryModal()"></div>
        <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl flex flex-col max-h-[80vh]">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-muted-200 shrink-0">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 id="summaryModalTitle" class="font-bold text-main text-base">Summary</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="summary-copy-btn" onclick="copySummary()" title="Copy to clipboard"
                            class="hidden p-1.5 rounded-lg text-muted-400 hover:text-purple-600 hover:bg-purple-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <button onclick="closeSummaryModal()" class="p-1.5 rounded-full text-muted-400 hover:text-primary hover:bg-muted-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                {{-- Options --}}
                <div class="flex items-center gap-3 px-6 py-3 bg-muted-50 border-b border-muted-200 shrink-0 flex-wrap">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-muted-500">Style</label>
                        <select id="ws-summary-style" class="text-xs border border-muted-200 rounded-lg px-2 py-1.5 bg-white text-main focus:outline-none focus:ring-2 focus:ring-primary/20">
                            <option value="bullet">Bullet points</option>
                            <option value="paragraph">Paragraph</option>
                            <option value="short">Short (TL;DR)</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-muted-500">Language</label>
                        <select id="ws-summary-lang" class="text-xs border border-muted-200 rounded-lg px-2 py-1.5 bg-white text-main focus:outline-none focus:ring-2 focus:ring-primary/20">
                            <option value="auto">Auto</option>
                            <option value="en">English</option>
                            <option value="vi">Vietnamese</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-muted-500">Clusters</label>
                        <select id="ws-summary-clusters" class="text-xs border border-muted-200 rounded-lg px-2 py-1.5 bg-white text-main focus:outline-none focus:ring-2 focus:ring-primary/20">
                            <option value="5">5</option>
                            <option value="8">8</option>
                            <option value="10" selected>10</option>
                            <option value="15">15</option>
                            <option value="20">20</option>
                        </select>
                    </div>
                    <button onclick="runSummary()" class="ml-auto text-xs font-semibold px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        Regenerate
                    </button>
                </div>
                {{-- Body --}}
                <div id="summary-modal-body" class="flex-1 overflow-y-auto px-6 py-5 custom-scrollbar"></div>
                {{-- Footer stats --}}
                <div id="summary-modal-footer" class="px-6 py-3 border-t border-muted-200 shrink-0 gap-3 text-xs text-muted-500" style="display:none">
                    <span id="summary-stat-clusters" class="inline-flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
                        <span id="summary-stat-clusters-text"></span>
                    </span>
                    <span id="summary-stat-chunks" class="inline-flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                        <span id="summary-stat-chunks-text"></span>
                    </span>
                    <span id="summary-stat-source" class="ml-auto italic"></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        let _summaryMode = 'workspace';
        let _summaryS3Key = null;
        let _summaryWorkspaceId = null;
        let _summaryFileName = null;
        let _summaryPlainText = '';

        function openSummaryModal(mode, s3Key, workspaceId, fileName) {
            _summaryMode        = mode;
            _summaryS3Key       = s3Key;
            _summaryWorkspaceId = workspaceId;
            _summaryFileName    = fileName || null;

            document.getElementById('summaryModalTitle').textContent =
                mode === 'workspace' ? 'Workspace Summary'
                                     : 'Document Summary' + (fileName ? ' — ' + fileName : '');

            document.getElementById('summary-copy-btn').classList.add('hidden');
            document.getElementById('summary-modal-footer').style.display = 'none';
            document.getElementById('summaryModal').classList.remove('hidden');
            runSummary();
        }

        function closeSummaryModal() {
            document.getElementById('summaryModal').classList.add('hidden');
        }

        function copySummary() {
            if (!_summaryPlainText) return;
            navigator.clipboard.writeText(_summaryPlainText).then(() => {
                const btn = document.getElementById('summary-copy-btn');
                btn.title = 'Copied!';
                setTimeout(() => { btn.title = 'Copy to clipboard'; }, 2000);
            });
        }

        function renderSummaryText(raw) {
            _summaryPlainText = raw;
            const lines = raw.split('\n').filter(l => l.trim());
            const isList = lines.some(l => /^[-•*]/.test(l.trim()));

            const html = lines.map(line => {
                const withBold = line.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                if (/^[-•*]\s*/.test(line.trim())) {
                    const clean = withBold.replace(/^[-•*]\s*/, '');
                    return `<li class="flex gap-2 text-sm text-main leading-relaxed">
                                <span class="text-purple-500 mt-1 shrink-0">&#8226;</span>
                                <span>${clean}</span>
                            </li>`;
                }
                return `<p class="text-sm text-main leading-relaxed">${withBold}</p>`;
            }).join('');

            return isList ? `<ul class="flex flex-col gap-2">${html}</ul>`
                          : `<div class="flex flex-col gap-2">${html}</div>`;
        }

        async function runSummary() {
            const body     = document.getElementById('summary-modal-body');
            const footer   = document.getElementById('summary-modal-footer');
            const copyBtn  = document.getElementById('summary-copy-btn');
            const style    = document.getElementById('ws-summary-style').value;
            const lang     = document.getElementById('ws-summary-lang').value;
            const clusters = parseInt(document.getElementById('ws-summary-clusters').value, 10);

            _summaryPlainText = '';
            copyBtn.classList.add('hidden');
            footer.style.display = 'none';

            body.innerHTML = `
                <div class="flex items-center justify-center py-12 gap-3 text-muted-400">
                    <svg class="animate-spin w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span class="text-sm font-medium">Generating summary&hellip;</span>
                </div>`;

            const endpoint = _summaryMode === 'workspace'
                ? '/api/ai/summarize-workspace'
                : '/api/ai/summarize-document';

            const payload = {
                lang,
                style,
                n_clusters:   clusters,
                workspace_id: _summaryWorkspaceId,
            };
            if (_summaryMode === 'document') {
                payload.s3_key = _summaryS3Key;
            }

            try {
                const res  = await fetch(endpoint, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (!res.ok) {
                    const msg = data.error || data.message || 'Summary service returned an error.';
                    body.innerHTML = `<p class="text-sm text-red-600 text-center py-6">${msg}</p>`;
                    return;
                }

                if (data.error) {
                    body.innerHTML = `<p class="text-sm text-red-600 text-center py-6">${data.error}</p>`;
                    return;
                }

                if (!data.summary || !data.summary.trim()) {
                    body.innerHTML = `<p class="text-sm text-muted-400 text-center py-6">No content to summarise yet. Make sure documents are ingested.</p>`;
                    return;
                }

                body.innerHTML = renderSummaryText(data.summary);
                copyBtn.classList.remove('hidden');

                // Footer stats
                const clusterText = data.n_clusters  ? `${data.n_clusters} clusters`  : '';
                const chunkText   = data.total_chunks ? `${data.total_chunks} chunks`  : '';
                const sourceText  = data.source       ? data.source                    : '';
                const fileText    = data.file_name    ? data.file_name                 : '';

                document.getElementById('summary-stat-clusters-text').textContent = clusterText;
                document.getElementById('summary-stat-chunks-text').textContent   = chunkText;
                document.getElementById('summary-stat-source').textContent        = fileText || sourceText;

                if (clusterText || chunkText) {
                    footer.style.display = 'flex';
                }
            } catch (err) {
                body.innerHTML = `<p class="text-sm text-red-600 text-center py-6">Network error — could not reach the summary service.</p>`;
                console.error('summary error', err);
            }
        }
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
@endsection
