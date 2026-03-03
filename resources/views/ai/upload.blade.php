@extends('layout_dashboard')
@section('title', __('ai.title'))

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">{{ __('ai.title') }}</h1>
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
                    <h2 class="text-lg md:text-xl font-semibold text-main">{{ __('ai.workspace_section') }}</h2>
                    <p class="text-muted-500 text-sm">{{ __('ai.workspace_desc') }}</p>
                </div>
                <span class="inline-flex items-center rounded-full border border-muted-200 px-3 py-1 text-xs text-muted-500">{{ __('ai.badge_new') }}</span>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-2">
                    <label for="workspace_name" class="text-sm font-semibold text-main">{{ __('ai.workspace_name') }}</label>
                    <input id="workspace_name" name="workspace_name" type="text"
                           class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                           placeholder="{{ __('ai.workspace_name_placeholder') }}" required>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="workspace_visibility" class="text-sm font-semibold text-main">{{ __('ai.visibility') }}</label>
                    <select id="workspace_visibility" name="workspace_visibility"
                            class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="private">{{ __('ai.visibility_private') }}</option>
                        <option value="team">{{ __('ai.visibility_team') }}</option>
                        <option value="public">{{ __('ai.visibility_public') }}</option>
                    </select>
                </div>

            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-primary-gradient px-6 py-3 text-white text-sm font-semibold shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                    {{ __('ai.create_workspace') }}
                </button>
                <button type="reset"
                        class="inline-flex items-center justify-center rounded-xl border border-muted-300 px-6 py-3 text-sm font-semibold text-muted-500 hover:bg-muted-50">
                    {{ __('ai.reset') }}
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
                    <label for="workspace_name_upload" class="text-sm font-semibold text-main">{{ __('ai.workspace_label') }}</label>
                    <select id="workspace_name_upload" name="workspace_name_upload"
                            class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary" required>
                        <option value="" disabled selected>{{ __('ai.workspace_select') }}</option>
                        @foreach ($workspaceOptions as $workspace)
                            <option value="{{ $workspace }}">{{ $workspace }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-muted-400">{{ __('ai.workspace_help') }}</p>
                </div>

                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="data_files" class="text-sm font-semibold text-main">{{ __('ai.data_files') }}</label>
                    <input id="data_files" name="data_files[]" type="file" multiple
                           class="w-full rounded-xl border border-muted-300 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                           required>
                    <p class="text-xs text-muted-400">{{ __('ai.data_files_help') }}</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-primary-gradient px-6 py-3 text-white text-sm font-semibold shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                    {{ __('ai.upload_action') }}
                </button>
                <button type="reset"
                        class="inline-flex items-center justify-center rounded-xl border border-muted-300 px-6 py-3 text-sm font-semibold text-muted-500 hover:bg-muted-50">
                    {{ __('ai.reset') }}
                </button>
            </div>
        </form>
    </div>
@endsection
