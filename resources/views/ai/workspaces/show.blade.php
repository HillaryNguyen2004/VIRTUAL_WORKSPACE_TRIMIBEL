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

        <!-- Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-lg border border-muted-200 bg-white p-4">
                <div class="text-xs text-muted-600 mb-1">{{ __('ai.total_files') }}</div>
                <div class="text-2xl font-bold text-main">{{ $stats['total_files'] }}</div>
            </div>
            <div class="rounded-lg border border-muted-200 bg-white p-4">
                <div class="text-xs text-muted-600 mb-1">{{ __('ai.storage_used') }}</div>
                <div class="text-2xl font-bold text-main">{{ $stats['storage_used_mb'] }} MB</div>
            </div>
            <div class="rounded-lg border border-muted-200 bg-white p-4">
                <div class="text-xs text-muted-600 mb-1">{{ __('ai.total_chunks') }}</div>
                <div class="text-2xl font-bold text-main">{{ $stats['total_chunks'] }}</div>
            </div>
            <div class="rounded-lg border border-muted-200 bg-white p-4">
                <div class="text-xs text-muted-600 mb-1">{{ __('ai.last_ingested') }}</div>
                <div class="text-sm font-medium text-main">
                    @if($workspace->last_ingested_at)
                        {{ $workspace->last_ingested_at->diffForHumans() }}
                    @else
                        {{ __('ai.never') }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="bg-white rounded-2xl border border-muted-200 shadow-sm p-6 md:p-8">
            <h2 class="text-lg md:text-xl font-semibold text-main mb-2">{{ __('ai.upload_files') }}</h2>
            <p class="text-muted-600 text-sm mb-6">{{ __('ai.upload_files_desc') }}</p>

            <form action="{{ route('ai-workspaces.upload-files', $workspace) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-6">
                    <label for="files" class="block">
                        <div class="border-2 border-dashed border-muted-300 rounded-lg p-8 text-center hover:border-primary/50 hover:bg-primary/5 transition-colors cursor-pointer">
                            <svg class="mx-auto h-12 w-12 text-muted-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            <p class="text-sm font-medium text-muted-700">{{ __('ai.drag_files') }}</p>
                            <p class="text-xs text-muted-500 mt-1">{{ __('ai.supported_formats') }}: PDF, TXT, MD, DOCX, PPTX, XLSX</p>
                        </div>
                        <input
                            type="file"
                            name="files[]"
                            id="files"
                            multiple
                            accept=".pdf,.txt,.md,.docx,.pptx,.xlsx"
                            class="hidden"
                            onchange="document.getElementById('fileList').innerHTML = Array.from(this.files).map(f => f.name).join(', ')">
                    </label>
                    <p class="text-sm text-muted-600 mt-2" id="fileList"></p>
                    @if($upload_errors ?? false)
                        <div class="mt-3 space-y-1">
                            @foreach($upload_errors as $error)
                                <p class="text-red-600 text-xs">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif
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
        </div>

        <!-- Ingest Section -->
        @if($stats['pending_files'] > 0 || $stats['failed_files'] > 0)
            <div class="bg-blue-50 rounded-2xl border border-blue-200 p-6 md:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-blue-900">{{ __('ai.ingest_required') }}</h2>
                        <p class="text-blue-700 text-sm mt-2">
                            {{ __('ai.files_pending_ingest', ['count' => $stats['pending_files']]) }}
                        </p>
                    </div>
                    <form action="{{ route('ai-workspaces.ingest', $workspace) }}" method="POST" class="flex-shrink-0">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            {{ __('ai.start_ingest') }}
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Files List -->
        <div class="bg-white rounded-2xl border border-muted-200 shadow-sm overflow-hidden">
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
                                                @if(str_ends_with(strtolower($file->file_name), '.pdf'))
                                                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>
                                                @else
                                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/></svg>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-main truncate">{{ $file->original_name }}</p>
                                                <p class="text-xs text-muted-500">{{ $file->file_name }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-muted-600">
                                        {{ round($file->file_size / 1024, 2) }} KB
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
                                        {{ $file->created_at->format('M d, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form action="{{ route('workspace-files.delete', $file) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-700 text-xs font-medium"
                                                    onclick="return confirm('{{ __('ai.confirm_delete') }}')">
                                                {{ __('ai.delete') }}
                                            </button>
                                        </form>
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
    </div>

    <!-- File upload script -->
    <script>
        const fileInput = document.getElementById('files');
        const dropZone = fileInput.closest('label');

        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-primary', 'bg-primary/5');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-primary', 'bg-primary/5');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                fileInput.files = e.dataTransfer.files;
                fileInput.onchange?.();
            });
        }
    </script>
@endsection
