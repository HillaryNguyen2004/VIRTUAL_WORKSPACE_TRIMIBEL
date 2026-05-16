@extends('layout_dashboard')

@section('title', __('ai.my_workspaces'))

@section('content')
    @vite(['resources/js/toggle_view.js'])

    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('subadmin') && Route::has('subadmin.dashboard')) {
            $dashRoute = 'subadmin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        } elseif (auth()->user()->hasRole('substaff') && Route::has('substaff.dashboard')) {
            $dashRoute = 'substaff.dashboard';
        }
    @endphp

    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        {{-- HEADER --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full">
            <div class="flex-1 min-w-0">
                <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">
                    {{ __('ai.my_workspaces') }}
                </h1>
                <p class="text-muted-500 text-sm md:text-base mt-1">
                    {{ __('ai.workspaces_desc') }}
                </p>
            </div>

            <button type="button" id="open-create-modal"
                class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                    <path
                        d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                </svg>
                <span class="font-medium">{{ __('ai.new_workspace') }}</span>
            </button>
        </div>

        {{-- SUCCESS MESSAGE --}}
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-4 text-green-800 text-sm">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        {{-- LIST CONTAINER --}}
        <div class="max-w-[1200px] mx-auto w-full flex flex-col gap-4 animate-fade-in-up">

            {{-- SEARCH & FILTER BAR --}}
            <form class="bg-white rounded-2xl border border-muted-300 p-4 flex flex-wrap gap-3 items-center" method="GET">
                {{-- Search --}}
                <x-form.search-input name="search" :value="request('search')"
                    placeholder="{{ __('ai.search_placeholder') }}" />

                {{-- Visibility Filter --}}
                <x-form.select name="visibility" :value="request('visibility')"
                :options="[
                    '' => __('ai.all_visibility'),
                    'private' => __('ai.visibility_private'),
                    'team' => __('ai.visibility_team'),
                    'public' => __('ai.visibility_public'),
                ]" :showChevron="true" />

                {{-- Sort --}}
                <x-form.select name="sort" :value="request('sort', 'desc')"
                :options="[
                    'desc' => __('ai.newest_first'),
                    'asc' => __('ai.oldest_first'),
                ]" :showChevron="true" />

                <div class="flex gap-2 ml-auto">
                    <button type="submit"
                        class="border border-muted-300 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors"
                        title="{{ __('ai.filter') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path
                                d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                        </svg>
                    </button>

                    <a href="{{ route('ai-workspaces.index') }}"
                        class="flex items-center justify-center border border-muted-300 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors"
                        title="{{ __('ai.reset') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path
                                d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                        </svg>
                    </a>
                </div>
            </form>

            {{-- WORKSPACE LIST --}}
            <div class="flex flex-col gap-2">
                @forelse($workspaces as $workspace)
                    @php
                        $ingestedCount = $workspace->ingestedFiles()->count();

                        $derivedStatus = data_get($workspace, 'status');
                        if (!$derivedStatus) {
                            if (($workspace->file_count ?? 0) === 0) {
                                $derivedStatus = 'empty';
                            } elseif ($ingestedCount >= ($workspace->file_count ?? 0)) {
                                $derivedStatus = 'ready';
                            } else {
                                $derivedStatus = 'processing';
                            }
                        }

                        $statusClass = 'bg-muted-100 text-muted-600';
                        $statusDot = 'bg-muted-400';
                        if (in_array(strtolower($derivedStatus), ['ready', 'active', 'completed'])) {
                            $statusClass = 'bg-success/10 text-success';
                            $statusDot = 'bg-success';
                        } elseif (in_array(strtolower($derivedStatus), ['processing', 'pending', 'draft'])) {
                            $statusClass = 'bg-primary/10 text-primary';
                            $statusDot = 'bg-primary';
                        }

                        $visibilityIcon = match ($workspace->visibility) {
                            'private' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
                            'team' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                            default => '<circle stroke-linecap="round" stroke-linejoin="round" stroke-width="2" cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12h20"/>',
                        };

                        $visibilityColor = match ($workspace->visibility) {
                            'private' => 'text-primary',
                            'team' => 'text-secondary',
                            default => 'text-accent',
                        };

                        $visibilityLabel = match ($workspace->visibility) {
                            'private' => __('ai.visibility_private'),
                            'team' => __('ai.visibility_team'),
                            'public' => __('ai.visibility_public'),
                            default => ucfirst($workspace->visibility),
                        };

                        $iconBg = match ($workspace->visibility) {
                            'private' => 'from-primary/20 to-primary-light/10',
                            'team' => 'from-secondary/20 to-secondary-light/10',
                            default => 'from-accent/20 to-accent-light/10',
                        };
                        $iconColor = match ($workspace->visibility) {
                            'private' => 'text-primary',
                            'team' => 'text-secondary',
                            default => 'text-accent',
                        };
                        $hoverColor = match ($workspace->visibility) {
                            'private' => 'hover:border-primary/50',
                            'team' => 'hover:border-secondary/50',
                            default => 'hover:border-accent/50',
                        };
                    @endphp

                    <a href="{{ route('ai-workspaces.show', $workspace) }}"
                        class="group flex items-center gap-4 bg-white border border-muted-300 rounded-2xl px-5 py-4 {{ $hoverColor }} transition-all duration-300">

                        {{-- Workspace Icon --}}
                        <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-gradient-to-br {{ $iconBg }} flex items-center justify-center">
                            <svg class="w-5 h-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {!! $visibilityIcon !!}
                            </svg>
                        </div>

                        {{-- Main Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-main text-sm transition-colors truncate">
                                    {{ $workspace->name }}
                                </span>
                                {{-- Status dot + label --}}
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
                                    {{ ucfirst($derivedStatus) }}
                                </span>
                            </div>

                            {{-- Meta row --}}
                            <div class="flex items-center gap-4 mt-1.5 flex-wrap">
                                {{-- Visibility --}}
                                <span class="flex items-center gap-1 text-xs {{ $visibilityColor }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $visibilityIcon !!}
                                    </svg>
                                    {{ $visibilityLabel }}
                                </span>

                                {{-- Files --}}
                                <span class="flex items-center gap-1 text-xs text-muted-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 lucide lucide-file-icon lucide-file">
                                        <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                                        <path d="M14 2v5a1 1 0 0 0 1 1h5"/>
                                    </svg>
                                    {{ $workspace->file_count }} {{ __('ai.files') }}
                                </span>

                                {{-- Storage --}}
                                <span class="flex items-center gap-1 text-xs text-muted-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 lucide lucide-database-icon lucide-database"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
                                    {{ formatBytes($workspace->storage_size, 2) }}
                                </span>
                            </div>
                        </div>

                        {{-- Right: Date + Arrow --}}
                        <div class="flex-shrink-0 flex items-center gap-4 text-right">
                            <span class="text-xs text-muted-400 hidden sm:block">
                                {{ $workspace->created_at ? $workspace->created_at->format('d/m/Y') : __('ai.not_available') }}
                            </span>
                            <svg class="w-4 h-4 text-muted-300 group-hover:{{ $visibilityColor }} group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>

                @empty
                    <div class="bg-white border border-muted-200 rounded-2xl py-16 flex flex-col items-center justify-center gap-3">
                        <div class="p-4 rounded-full bg-muted-100 text-muted-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-muted-500 font-medium text-sm">{{ __('ai.no_workspaces') }}</p>
                        <a href="{{ route('ai-workspaces.create') }}"
                            class="mt-1 text-xs text-primary hover:underline font-medium">
                            {{ __('ai.new_workspace') }}
                        </a>
                    </div>
                @endforelse
            </div>

            {{-- PAGINATION --}}
            @if ($workspaces instanceof \Illuminate\Pagination\LengthAwarePaginator && $workspaces->hasPages())
                <div class="flex justify-center w-full py-2">
                    {{ $workspaces->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    @include('ai.workspaces.create')

    <script>
        const createModal = document.getElementById('create-workspace-modal');
        const createBackdrop = document.getElementById('create-modal-backdrop');

        function openCreateModal() {
            createModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeCreateModal() {
            createModal.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.getElementById('open-create-modal').addEventListener('click', openCreateModal);
        createBackdrop?.addEventListener('click', closeCreateModal);
        document.getElementById('close-create-modal')?.addEventListener('click', closeCreateModal);
        document.getElementById('cancel-create-modal')?.addEventListener('click', closeCreateModal);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeCreateModal();
        });

        @if($errors->any())
            document.addEventListener('DOMContentLoaded', openCreateModal);
        @endif
    </script>
@endsection