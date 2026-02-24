@extends('layout_dashboard')
@section('title', __('online_docs.title'))

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        @php
            $canEdit = auth()->user()->can('update', $document);
        @endphp
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('online_docs.editor_title') }}</h2>
                <p class="text-muted-500 text-sm mt-1">{{ $document->title }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('online-docs.docs.export', $document) }}" class="px-4 py-2 rounded-xl bg-muted-100 text-muted-700 hover:bg-muted-200 text-sm font-medium">
                    {{ __('online_docs.export_docx') }}
                </a>
                @if($canEdit)
                <button type="submit" form="doc-editor-form" class="px-4 py-2 rounded-xl bg-secondary text-white hover:bg-secondary/90 text-sm font-medium">
                    {{ __('online_docs.save') }}
                </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <div class="xl:col-span-9">
                <form
                    id="doc-editor-form"
                    method="POST"
                    action="{{ route('online-docs.docs.update', $document) }}"
                    data-saving-text="{{ __('online_docs.saving') }}"
                    data-saved-text="{{ __('online_docs.saved') }}"
                    data-read-only="{{ $canEdit ? 'false' : 'true' }}"
                    data-read-only-text="{{ __('online_docs.read_only') }}"
                    class="flex flex-col gap-4"
                >
                    @csrf
                    @method('PUT')
                    <input
                        type="text"
                        name="title"
                        value="{{ old('title', $document->title) }}"
                        required
                        {{ $canEdit ? '' : 'disabled' }}
                        class="rounded-xl border border-muted-200 px-4 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                    <textarea id="doc-content" name="content" class="hidden">{{ $html }}</textarea>
                    <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 tiptap-editor">
                        <div class="flex flex-wrap items-center gap-2 border-b border-muted-100 p-3" id="doc-toolbar">
                            <select id="doc-heading" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                <option value="paragraph">P</option>
                                <option value="h1">H1</option>
                                <option value="h2">H2</option>
                                <option value="h3">H3</option>
                            </select>
                            <select id="doc-font-size" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                <option value="">Size</option>
                                <option value="12px">12</option>
                                <option value="14px">14</option>
                                <option value="16px">16</option>
                                <option value="18px">18</option>
                                <option value="20px">20</option>
                                <option value="24px">24</option>
                                <option value="28px">28</option>
                                <option value="32px">32</option>
                            </select>
                            <select id="doc-line-height" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                <option value="">Line</option>
                                <option value="1">1.0</option>
                                <option value="1.15">1.15</option>
                                <option value="1.5">1.5</option>
                                <option value="2">2.0</option>
                            </select>
                            <select id="doc-font-family" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                <option value="">Font</option>
                                <option value="Arial">Arial</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Trebuchet MS">Trebuchet MS</option>
                                <option value="Verdana">Verdana</option>
                                <option value="Tahoma">Tahoma</option>
                                <option value="Courier New">Courier New</option>
                            </select>
                            <button type="button" data-command="bold" class="px-2 py-1 text-xs font-semibold rounded-md border border-muted-200">B</button>
                            <button type="button" data-command="italic" class="px-2 py-1 text-xs font-semibold rounded-md border border-muted-200">I</button>
                            <button type="button" data-command="underline" class="px-2 py-1 text-xs font-semibold rounded-md border border-muted-200">U</button>
                            <button type="button" data-command="strike" class="px-2 py-1 text-xs font-semibold rounded-md border border-muted-200">S</button>
                            <button type="button" data-command="blockquote" class="px-2 py-1 text-xs rounded-md border border-muted-200">Quote</button>
                            <select id="doc-list-style" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                <option value="">List</option>
                                <option value="bullet">Bulleted</option>
                                <option value="ordered">Numbered</option>
                            </select>
                            <button type="button" data-command="code" class="px-2 py-1 text-xs rounded-md border border-muted-200">Code</button>
                            <button type="button" data-command="codeBlock" class="px-2 py-1 text-xs rounded-md border border-muted-200">Code Block</button>
                            <button type="button" data-command="alignLeft" class="px-2 py-1 text-xs rounded-md border border-muted-200">Left</button>
                            <button type="button" data-command="alignCenter" class="px-2 py-1 text-xs rounded-md border border-muted-200">Center</button>
                            <button type="button" data-command="alignRight" class="px-2 py-1 text-xs rounded-md border border-muted-200">Right</button>
                            <button type="button" data-command="alignJustify" class="px-2 py-1 text-xs rounded-md border border-muted-200">Justify</button>
                            <button type="button" data-command="highlight" class="px-2 py-1 text-xs rounded-md border border-muted-200">Highlight</button>
                            <input type="color" id="doc-color" class="h-7 w-8 border border-muted-200 rounded-md" title="Text color" />
                            <select id="doc-color-recent" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                <option value="">Recent</option>
                            </select>
                            <button type="button" data-command="image" class="px-2 py-1 text-xs rounded-md border border-muted-200">Image</button>
                            <input type="file" id="doc-image-input" accept="image/*" class="hidden" />
                            <button type="button" data-command="table" class="px-2 py-1 text-xs rounded-md border border-muted-200">Table</button>
                            <select id="doc-table-style" class="rounded-lg border border-muted-200 px-2 py-1 text-xs">
                                <option value="">Table style</option>
                                <option value="grid">Grid</option>
                                <option value="light">Light</option>
                                <option value="none">No border</option>
                            </select>
                            <button type="button" data-command="addRow" class="px-2 py-1 text-xs rounded-md border border-muted-200">Row +</button>
                            <button type="button" data-command="addColumn" class="px-2 py-1 text-xs rounded-md border border-muted-200">Col +</button>
                            <button type="button" data-command="deleteRow" class="px-2 py-1 text-xs rounded-md border border-muted-200">Row -</button>
                            <button type="button" data-command="deleteColumn" class="px-2 py-1 text-xs rounded-md border border-muted-200">Col -</button>
                            <button type="button" data-command="deleteTable" class="px-2 py-1 text-xs rounded-md border border-muted-200">Del Table</button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 border-t border-muted-100 p-3" id="doc-find-toolbar">
                            <input type="text" id="doc-find" placeholder="Find" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                            <input type="text" id="doc-replace" placeholder="Replace" class="rounded-lg border border-muted-200 px-2 py-1 text-xs" />
                            <button type="button" data-command="findNext" class="px-2 py-1 text-xs rounded-md border border-muted-200">Find</button>
                            <button type="button" data-command="replace" class="px-2 py-1 text-xs rounded-md border border-muted-200">Replace</button>
                            <button type="button" data-command="replaceAll" class="px-2 py-1 text-xs rounded-md border border-muted-200">Replace All</button>
                        </div>
                        <div id="doc-editor" class="min-h-[480px] p-4"></div>
                    </div>
                    <div class="text-xs text-muted-400" id="doc-save-status"></div>
                </form>
            </div>

            <div class="xl:col-span-3 flex flex-col gap-6">
                @can('update', $document)
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5">
                    <h3 class="text-lg font-semibold text-main mb-4">{{ __('online_docs.import_docx') }}</h3>
                    <form method="POST" action="{{ route('online-docs.docs.import', $document) }}" enctype="multipart/form-data" class="flex flex-col gap-3">
                        @csrf
                        <input type="file" name="docx" accept=".doc,.docx" required class="text-sm" />
                        <button type="submit" class="px-4 py-2 rounded-xl bg-secondary text-white hover:bg-secondary/90 text-sm font-medium">
                            {{ __('online_docs.import_docx') }}
                        </button>
                    </form>
                </div>
                @endcan

                @can('share', $document)
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5">
                    <h3 class="text-lg font-semibold text-main mb-4">{{ __('online_docs.share') }}</h3>
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
                        <button type="submit" class="px-4 py-2 rounded-xl bg-secondary text-white hover:bg-secondary/90 text-sm font-medium">
                            {{ __('online_docs.share') }}
                        </button>
                    </form>

                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-main mb-2">{{ __('online_docs.shared_list') }}</h4>
                        @forelse($sharedUsers as $sharedUser)
                            <div class="flex flex-col gap-2 py-2 border-b border-muted-100 last:border-b-0">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-muted-600 truncate">{{ $sharedUser->email }}</span>
                                    <span class="text-xs text-muted-400">{{ $sharedUser->pivot->permission }}</span>
                                </div>
                                <div class="flex items-center gap-2">
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
                @endcan
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/online_docs/editor.js'])
@endpush
