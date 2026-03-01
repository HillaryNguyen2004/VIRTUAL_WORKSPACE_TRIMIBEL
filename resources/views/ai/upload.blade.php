@extends('layout_dashboard')
@section('title', 'AI Data Upload')

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">AI Data Upload</h1>
            <p class="text-muted-500 text-sm md:text-base">Tai files du lieu phuc vu huan luyen va phan tich AI.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3 text-primary text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if (session('workspace_status'))
            <div class="rounded-2xl border border-secondary/20 bg-secondary/5 px-4 py-3 text-secondary text-sm">
                {{ session('workspace_status') }}
            </div>
        @endif

        <form class="bg-white border border-muted-200 rounded-2xl p-6 md:p-8 shadow-sm"
              method="POST" action="{{ route('ai.workspaces.store') }}">
            @csrf

            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg md:text-xl font-semibold text-main">Workspace</h2>
                    <p class="text-muted-500 text-sm">Tao workspace rieng de quan ly du lieu AI.</p>
                </div>
                <span class="inline-flex items-center rounded-full border border-muted-200 px-3 py-1 text-xs text-muted-500">Moi</span>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-2">
                    <label for="workspace_name" class="text-sm font-semibold text-main">Ten workspace</label>
                    <input id="workspace_name" name="workspace_name" type="text"
                           class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                           placeholder="Vi du: AI Support Hub" required>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="workspace_visibility" class="text-sm font-semibold text-main">Muc do truy cap</label>
                    <select id="workspace_visibility" name="workspace_visibility"
                            class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="private">Private</option>
                        <option value="team">Team</option>
                        <option value="public">Public</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="workspace_purpose" class="text-sm font-semibold text-main">Muc dich</label>
                    <textarea id="workspace_purpose" name="workspace_purpose" rows="3"
                              class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                              placeholder="Mo ta muc dich su dung workspace" required></textarea>
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="workspace_notes" class="text-sm font-semibold text-main">Ghi chu</label>
                    <textarea id="workspace_notes" name="workspace_notes" rows="3"
                              class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                              placeholder="Ghi chu them ve workspace"></textarea>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-secondary px-6 py-3 text-white text-sm font-semibold shadow-lg shadow-secondary/20 transition-all hover:bg-secondary/90 focus:ring-4 focus:ring-secondary/30 active:scale-95">
                    Tao workspace
                </button>
                <button type="reset"
                        class="inline-flex items-center justify-center rounded-xl border border-muted-300 px-6 py-3 text-sm font-semibold text-muted-500 hover:bg-muted-50">
                    Lam moi
                </button>
            </div>
        </form>

        <form class="bg-white border border-muted-200 rounded-2xl p-6 md:p-8 shadow-sm"
              method="POST" action="{{ route('ai.upload.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @php
                    $workspaceOptions = [
                        'AI Support Hub',
                        'Marketing Insights',
                        'Voice Data Lab',
                        'Vision Dataset',
                    ];
                @endphp
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="workspace_name_upload" class="text-sm font-semibold text-main">Workspace</label>
                    <select id="workspace_name_upload" name="workspace_name_upload"
                            class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="" disabled selected>Chon workspace de nap du lieu</option>
                        @foreach ($workspaceOptions as $workspace)
                            <option value="{{ $workspace }}">{{ $workspace }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-muted-400">Files upload se duoc nap vao workspace nay.</p>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="dataset_name" class="text-sm font-semibold text-main">Ten dataset</label>
                    <input id="dataset_name" name="dataset_name" type="text"
                           class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                           placeholder="Vi du: Customer Support Logs" required>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="data_type" class="text-sm font-semibold text-main">Loai du lieu</label>
                    <select id="data_type" name="data_type"
                            class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="text">Text</option>
                        <option value="image">Image</option>
                        <option value="audio">Audio</option>
                        <option value="video">Video</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="description" class="text-sm font-semibold text-main">Mo ta du lieu</label>
                    <textarea id="description" name="description" rows="4"
                              class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                              placeholder="Mo ta noi dung, nguon goc, muc dich su dung" required></textarea>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="tags" class="text-sm font-semibold text-main">Tags</label>
                    <input id="tags" name="tags" type="text"
                           class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                           placeholder="Vi du: support, vietnamese, faq">
                </div>

                <div class="flex flex-col gap-2">
                    <label for="source_url" class="text-sm font-semibold text-main">Nguon / URL</label>
                    <input id="source_url" name="source_url" type="url"
                           class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                           placeholder="https://">
                </div>

                <div class="flex flex-col gap-2">
                    <label for="license" class="text-sm font-semibold text-main">License</label>
                    <input id="license" name="license" type="text"
                           class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                           placeholder="Vi du: CC BY 4.0">
                </div>

                <div class="flex flex-col gap-2">
                    <label for="visibility" class="text-sm font-semibold text-main">Muc do truy cap</label>
                    <select id="visibility" name="visibility"
                            class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="public">Public</option>
                        <option value="internal">Internal</option>
                        <option value="private">Private</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="notes" class="text-sm font-semibold text-main">Ghi chu</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                              placeholder="Bat ky luu y nao ve du lieu"></textarea>
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="data_files" class="text-sm font-semibold text-main">Files du lieu</label>
                    <input id="data_files" name="data_files[]" type="file" multiple
                           class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                           required>
                    <p class="text-xs text-muted-400">Ho tro tai nhieu file cung luc.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-primary-gradient px-6 py-3 text-white text-sm font-semibold shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                    Tai len du lieu
                </button>
                <button type="reset"
                        class="inline-flex items-center justify-center rounded-xl border border-muted-300 px-6 py-3 text-sm font-semibold text-muted-500 hover:bg-muted-50">
                    Lam moi
                </button>
            </div>
        </form>
    </div>
@endsection
