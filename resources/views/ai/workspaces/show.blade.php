@extends('layout_dashboard')
@section('title', $workspace->name)

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                @include('components.back-btn', ['route' => 'ai-workspaces.index'])

                <div>
                    <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">{{ $workspace->name }}</h1>
                    @if ($workspace->description)
                        <p class="text-muted-500 text-sm md:text-base mt-1">{{ $workspace->description }}</p>
                    @endif
                    <p class="text-muted-500 text-sm mt-2">{{ __('ai.created_by') }} {{ $workspace->user->name }} ({{ $workspace->user->username }})</p>
                </div>
            </div>


            <div class="flex items-center gap-2">
                {{-- Primary Action: Summarize --}}
                {{-- <button onclick="openSummaryModal('workspace', null, '{{ $workspace->id }}')"
                    class="inline-flex items-center justify-center gap-2  border border-primary/30 px-4 py-2.5 rounded-xl text-sm font-medium text-primary hover:bg-primary/10 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Summarize Workspace
                </button> --}}

                @can('update', $workspace)
                    {{-- Subtle vertical divider --}}
                    <div class="h-6 w-px bg-muted-200 mx-2 hidden sm:block"></div>
                    
                    {{-- Secondary Action: Edit --}}
                    <button type="button" id="open-edit-modal" title="{{ __('ai.edit') }}"
                        class="flex items-center justify-center p-2.5 rounded-xl border border-muted-300 bg-white text-muted-500 transition-all hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    
                    {{-- Danger Action: Delete --}}
                    <form action="{{ route('ai-workspaces.destroy', $workspace) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="{{ __('ai.delete') }}"
                            class="flex items-center justify-center p-2.5 rounded-xl border border-muted-300 bg-white text-muted-500 transition-all hover:border-danger/30 hover:bg-danger/5 hover:text-danger"
                            onclick="return confirm('{{ __('ai.confirm_delete') }}')">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                <path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- FLASH MESSAGES --}}
        @if(session('success'))
            <div class="flex w-full max-w-[1200px] mx-auto items-start gap-3 rounded-2xl border border-success/20 bg-success/5 px-4 py-4 text-success text-sm">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 fill-current" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="flex w-full max-w-[1200px] mx-auto items-start gap-3 rounded-2xl border border-accent/20 bg-accent/5 px-4 py-4 text-accent text-sm">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 fill-current" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ session('warning') }}
            </div>
        @endif

        @if($errors->any())
            <div class="flex w-full max-w-[1200px] mx-auto rounded-2xl border border-danger/20 bg-danger/5 px-4 py-4 text-danger text-sm">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- STATS KPI CARDS --}}
        <div class="w-full max-w-[1200px] mx-auto grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <x-white-card-container color="primary/50" class="p-3 items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-file-icon lucide-file">
                        <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                        <path d="M14 2v5a1 1 0 0 0 1 1h5"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-400">{{ __('ai.total_files') }}</p>
                    <p class="text-lg font-bold text-main leading-tight">{{ $stats['total_files'] }}</p>
                </div>
            </x-white-card-container>

            <x-white-card-container class="hover:border-secondary/80 p-3 items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-secondary/10 text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-database-icon lucide-database">
                        <ellipse cx="12" cy="5" rx="9" ry="3"/>
                        <path d="M3 5V19A9 3 0 0 0 21 19V5"/>
                        <path d="M3 12A9 3 0 0 0 21 12"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-400">{{ __('ai.storage_used') }}</p>
                    <p class="text-lg font-bold text-main leading-tight">{{ formatBytes($stats['storage_size'], 2) }}</p>
                </div>
            </x-white-card-container>

            <x-white-card-container color="accent/50" class="p-3 items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-accent/10 text-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-400">{{ __('ai.total_chunks') }}</p>
                    <p class="text-lg font-bold text-main leading-tight">{{ $stats['total_chunks'] }}</p>
                </div>
            </x-white-card-container>

            <x-white-card-container class="hover:border-success/80 p-3 items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-success/10 text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-400">{{ __('ai.last_ingested') }}</p>
                    <p class="text-sm font-bold text-main leading-tight">
                        @if($workspace->last_ingested_at)
                            {{ $workspace->last_ingested_at->diffForHumans() }}
                        @else
                            <span class="text-muted-400 font-medium">{{ __('ai.never') }}</span>
                        @endif
                    </p>
                </div>
            </x-white-card-container>

        </div>

        {{-- UPLOAD SECTION --}}
        @can('upload', $workspace)
            <x-white-card-container color="primary/50" class="p-6 flex-col w-full max-w-[1200px] mx-auto">
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-main">{{ __('ai.upload_files') }}</h4>
                    <p class="text-sm text-muted-500 mt-1">{{ __('ai.upload_files_desc') }}</p>
                </div>

                <form action="{{ route('ai-workspaces.upload-files', $workspace) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <label for="files-batch" class="block mb-5" id="batch-dropzone-label">
                        <div id="batch-dropzone"
                            class="border-2 border-dashed border-muted-300 rounded-2xl p-8 text-center hover:border-primary/50 hover:bg-primary/5 transition-colors cursor-pointer">
                            <svg class="mx-auto h-12 w-12 text-muted-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                            </svg>
                            <p class="text-sm font-medium text-muted-700">{{ __('ai.drag_files') }}</p>
                            <p class="text-xs text-muted-500 mt-1">{{ __('ai.supported_formats') }}: PDF, TXT, MD, DOCX, XLSX, CSV</p>
                        </div>
                        <input type="file" name="files[]" id="files-batch" multiple
                            accept=".pdf,.txt,.md,.docx,.pptx,.xlsx,.csv" class="hidden">
                    </label>
                    <p class="text-sm text-muted-600 mb-5" id="batchFileList"></p>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-white font-semibold shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('ai.upload_files') }}
                        </button>
                    </div>
                </form>

                @if($upload_errors ?? false)
                    <div class="mt-4 space-y-1">
                        @foreach($upload_errors as $error)
                            <p class="text-danger text-xs">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
            </x-white-card-container>
        @endcan

        {{-- INGEST SECTION --}}
        @can('ingest', $workspace)
            @if($stats['pending_files'] > 0 || $stats['failed_files'] > 0)
                @php $reingestCount = $stats['pending_files'] + $stats['failed_files']; @endphp
                <div class="relative overflow-hidden rounded-2xl border border-primary/30 bg-inherit p-6">
                    <div class="flex items-end justify-between gap-4">
                        <div class="flex gap-4">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold uppercase tracking-wide text-primary/80">{{ __('ai.ingest_required') }}</h4>
                                <p class="text-sm text-primary/70 mt-1">
                                    {{ __('ai.reingest_ready', ['count' => $reingestCount]) }}
                                    @if($stats['pending_files'] > 0 && $stats['failed_files'] > 0)
                                        <span class="block mt-0.5 text-primary/60">
                                            {{ $stats['pending_files'] }} {{ __('ai.pending') }}, {{ $stats['failed_files'] }} {{ __('ai.failed') }}
                                        </span>
                                    @elseif($stats['pending_files'] > 0)
                                        <span class="block mt-0.5 text-primary/60">{{ $stats['pending_files'] }} {{ __('ai.pending') }}</span>
                                    @else
                                        <span class="block mt-0.5 text-primary/60">{{ $stats['failed_files'] }} {{ __('ai.failed') }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <form id="ingest-form" action="{{ route('ai-workspaces.ingest', $workspace) }}" method="POST" class="flex-shrink-0">
                            @csrf
                            <button id="ingest-btn" type="submit"
                                class="flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover">
                                <svg id="ingest-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ __('ai.start_ingest') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        {{-- FILES LIST --}}
        <x-white-card-container color="primary/50" class="overflow-hidden flex-col w-full max-w-[1200px] mx-auto">
            <div class="flex items-center justify-between border-b border-muted-200 px-5 py-4">
                <div>
                    <h4 class="text-lg font-semibold text-main">{{ __('ai.workspace_files') }}</h4>
                    <p class="text-sm text-muted-500">All documents uploaded to this workspace.</p>
                </div>
            </div>

            @if($files->count() === 0)
                <div class="flex flex-col items-center justify-center gap-3 py-16 px-6 text-center">
                    <div class="p-3 rounded-full bg-muted-100 text-muted-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="text-muted-500 font-medium">{{ __('ai.no_files') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-muted-50 text-xs uppercase tracking-wider text-muted-400 border-b border-muted-200">
                            <tr>
                                <th class="px-5 py-4 text-left font-semibold">{{ __('ai.file_name') }}</th>
                                <th class="px-4 py-4 text-center font-semibold">{{ __('ai.file_size') }}</th>
                                <th class="px-4 py-4 text-center font-semibold">{{ __('ai.status') }}</th>
                                <th class="px-4 py-4 text-center font-semibold">{{ __('ai.chunks') }}</th>
                                <th class="px-4 py-4 text-center font-semibold">{{ __('ai.uploaded_at') }}</th>
                                <th class="px-5 py-4 text-center font-semibold">{{ __('ai.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-muted-100 text-sm">
                            @foreach($files as $file)
                                <tr class="hover:bg-muted-50 transition-colors">

                                    {{-- File Name --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-muted-100">
                                                @if (str_ends_with(strtolower($file->file_name), '.pdf'))
                                                    <svg class="w-5 h-5 text-danger fill-current" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>
                                                @elseif (str_ends_with(strtolower($file->file_name), '.xlsx') || str_ends_with(strtolower($file->file_name), '.xls') || str_ends_with(strtolower($file->file_name), '.csv'))
                                                    <svg class="w-5 h-5 text-success fill-current" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>
                                                @else
                                                    <svg class="w-5 h-5 text-primary fill-current" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-main truncate">{{ $file->original_name }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- File Size --}}
                                    <td class="px-4 py-4 text-center">
                                        <div class="font-semibold text-main">{{ formatBytes($file->file_size, 2) }}</div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-4 text-center">
                                        @if($file->ingest_status === 'pending')
                                            <span class="inline-flex items-center rounded-full bg-accent/10 px-3 py-1 text-xs font-semibold text-accent">{{ __('ai.pending') }}</span>
                                        @elseif($file->ingest_status === 'processing')
                                            <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">{{ __('ai.processing') }}</span>
                                        @elseif($file->ingest_status === 'completed')
                                            <span class="inline-flex items-center rounded-full bg-success/5 px-3 py-1 text-xs font-semibold text-success">{{ __('ai.completed') }}</span>
                                        @elseif($file->ingest_status === 'failed')
                                            <span class="inline-flex items-center rounded-full bg-danger/10 px-3 py-1 text-xs font-semibold text-danger">{{ __('ai.failed') }}</span>
                                        @endif
                                    </td>

                                    {{-- Chunks --}}
                                    <td class="px-4 py-4 text-center">
                                        <span class="font-semibold text-main">{{ $file->chunk_count > 0 ? $file->chunk_count : '-' }}</span>
                                    </td>

                                    {{-- Uploaded At --}}
                                    <td class="px-4 py-4 text-center text-xs text-muted-500">
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
                                                        <button type="submit" title="{{ __('ai.retry_ingest') }}"
                                                            class="p-1.5 rounded-lg text-muted-400 hover:bg-primary/10 hover:text-primary transition-colors"
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
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4lucide lucide-download-icon lucide-download">
                                                    <path d="M12 15V3"/>
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                    <path d="m7 10 5 5 5-5"/>
                                                </svg>
                                            </a>

                                            @can('delete', $file)
                                                <form action="{{ route('workspace-files.delete', $file) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        onclick="return confirm('{{ __('ai.confirm_delete') }}')"
                                                        class="p-1.5 rounded-lg text-muted-400 hover:bg-danger/10 hover:text-danger transition-colors">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 lucide lucide-trash-icon lucide-trash">
                                                                <path d="M10 11v6"/>
                                                                <path d="M14 11v6"/>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                                                <path d="M3 6h18"/>
                                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
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

                @if($files->hasPages())
                    <div class="flex justify-center px-5 py-5 border-t border-muted-200">
                        {{ $files->links('vendor.pagination.tailwind') }}
                    </div>
                @endif
            @endif
        </x-white-card-container>

        <div id="ingest-loading-overlay" class="ingest-loading-overlay hidden" aria-live="polite" aria-busy="true">
            <div class="ingest-spinner"></div>
            <p class="text-sm font-medium text-slate-700">{{ __('ai.loading_ingest') }}</p>
        </div>
    </div>

    @can('update', $workspace)
        @include('ai.workspaces.edit')
    @endcan

    <script>
        const editModal = document.getElementById('edit-workspace-modal');
        const editBackdrop = document.getElementById('edit-modal-backdrop');

        function openEditModal() {
            if (!editModal) return;
            editModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            if (!editModal) return;
            editModal.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.getElementById('open-edit-modal')?.addEventListener('click', openEditModal);
        editBackdrop?.addEventListener('click', closeEditModal);
        document.getElementById('close-edit-modal')?.addEventListener('click', closeEditModal);
        document.getElementById('cancel-edit-modal')?.addEventListener('click', closeEditModal);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeEditModal();
        });

        @if($errors->any())
            document.addEventListener('DOMContentLoaded', openEditModal);
        @endif
    </script>

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
                    <button id="summary-run-btn" onclick="runSummary()" class="ml-auto text-xs font-semibold px-3 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        Summarize
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
            document.getElementById('summary-modal-body').innerHTML = `
                <div class="flex flex-col items-center justify-center py-14 gap-4 text-center">
                    <svg class="w-10 h-10 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-muted-400">Choose your options above, then click <strong class="text-main">Summarize</strong>.</p>
                    <div class="flex items-center gap-3 mt-1">
                        <button onclick="runSummary()"
                            class="px-5 py-2 text-sm font-semibold bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors">
                            Summarize
                        </button>
                        <button onclick="closeSummaryModal()"
                            class="px-5 py-2 text-sm font-medium text-muted-500 border border-muted-200 rounded-xl hover:bg-muted-50 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>`;
            document.getElementById('summaryModal').classList.remove('hidden');
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
            const runBtn   = document.getElementById('summary-run-btn');
            const style    = document.getElementById('ws-summary-style').value;
            const lang     = document.getElementById('ws-summary-lang').value;
            const clusters = parseInt(document.getElementById('ws-summary-clusters').value, 10);

            if (runBtn) { runBtn.disabled = true; runBtn.textContent = 'Running…'; }
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

            const payload = {
                lang,
                style,
                n_clusters:   clusters,
                workspace_id: _summaryWorkspaceId,
            };

            // --- Streaming path for workspace summaries ---
            if (_summaryMode === 'workspace') {
                try {
                    const res = await fetch('/api/ai/summarize-workspace/stream', {
                        method:  'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                            'Accept':       'text/plain',
                        },
                        body: JSON.stringify(payload),
                    });

                    if (!res.ok) {
                        const txt = await res.text();
                        body.innerHTML = `<p class="text-sm text-red-600 text-center py-6">${txt || 'Summary service returned an error.'}</p>`;
                        return;
                    }

                    const reader  = res.body.getReader();
                    const decoder = new TextDecoder();
                    let   buffer  = '';
                    let   rawText = '';
                    let   started = false;

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop();

                        for (const line of lines) {
                            if (!line.trim()) continue;
                            let evt;
                            try { evt = JSON.parse(line); } catch { continue; }

                            if (evt.type === 'progress') {
                                if (!started) {
                                    body.innerHTML = `
                                        <div id="summary-stream-progress" class="text-xs text-muted-400 mb-3 flex items-center gap-2">
                                            <svg class="animate-spin w-3 h-3 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                            </svg>
                                            <span id="summary-progress-label"></span>
                                        </div>
                                        <div id="summary-stream-content" class="flex flex-col gap-2"></div>`;
                                    started = true;
                                }
                                const label = document.getElementById('summary-progress-label');
                                if (label) label.textContent = `Processing file ${evt.current}/${evt.total}: ${evt.file}`;

                            } else if (evt.type === 'token') {
                                rawText += evt.text;
                                const progress = document.getElementById('summary-stream-progress');
                                if (progress) progress.remove();
                                if (!document.getElementById('summary-stream-content')) {
                                    body.innerHTML = `<div id="summary-stream-content" class="flex flex-col gap-2"></div>`;
                                }
                                document.getElementById('summary-stream-content').innerHTML = renderSummaryText(rawText);

                            } else if (evt.type === 'done') {
                                _summaryPlainText = rawText;
                                copyBtn.classList.remove('hidden');

                                const clusterText = evt.n_clusters  ? `${evt.n_clusters} clusters`  : '';
                                const chunkText   = evt.total_chunks ? `${evt.total_chunks} chunks` : '';
                                const fileText    = evt.file_name    ? evt.file_name                 : '';

                                document.getElementById('summary-stat-clusters-text').textContent = clusterText;
                                document.getElementById('summary-stat-chunks-text').textContent   = chunkText;
                                document.getElementById('summary-stat-source').textContent        = fileText;

                                if (clusterText || chunkText) footer.style.display = 'flex';

                            } else if (evt.type === 'error') {
                                body.innerHTML = `<p class="text-sm text-red-600 text-center py-6">${evt.message}</p>`;
                            }
                        }
                    }

                    if (!rawText && !body.querySelector('.text-red-600')) {
                        body.innerHTML = `<p class="text-sm text-muted-400 text-center py-6">No content to summarise yet. Make sure documents are ingested.</p>`;
                    }
                } catch (err) {
                    body.innerHTML = `<p class="text-sm text-red-600 text-center py-6">Network error — could not reach the summary service.</p>`;
                    console.error('summary stream error', err);
                } finally {
                    if (runBtn) { runBtn.disabled = false; runBtn.textContent = 'Regenerate'; }
                }
                return;
            }

            // --- Non-streaming path for document summaries ---
            payload.s3_key = _summaryS3Key;

            try {
                const res  = await fetch('/api/ai/summarize-document', {
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

                const clusterText = data.n_clusters  ? `${data.n_clusters} clusters`  : '';
                const chunkText   = data.total_chunks ? `${data.total_chunks} chunks`  : '';
                const fileText    = data.file_name    ? data.file_name                 : '';

                document.getElementById('summary-stat-clusters-text').textContent = clusterText;
                document.getElementById('summary-stat-chunks-text').textContent   = chunkText;
                document.getElementById('summary-stat-source').textContent        = fileText || data.source;

                if (clusterText || chunkText) footer.style.display = 'flex';
            } catch (err) {
                body.innerHTML = `<p class="text-sm text-red-600 text-center py-6">Network error — could not reach the summary service.</p>`;
                console.error('summary error', err);
            } finally {
                if (runBtn) { runBtn.disabled = false; runBtn.textContent = 'Regenerate'; }
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
