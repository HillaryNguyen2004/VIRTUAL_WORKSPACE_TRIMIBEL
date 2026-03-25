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

    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        {{-- HEADER --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full mb-8">
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">
                    {{ __('ai.my_workspaces') }}
                </h2>
                <p class="text-muted-500 text-sm mt-2">
                    {{ __('ai.workspaces_desc') }}
                </p>
            </div>

            <a href="{{ route('ai-workspaces.create') }}"
                class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                    <path
                        d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                </svg>
                <span class="font-medium">{{ __('ai.new_workspace') }}</span>
            </a>
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

        {{-- CARD CONTAINER --}}
        <div
            class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-visible flex flex-col animate-fade-in-up">

            {{-- SEARCH & FILTER BAR --}}
            <form class="p-5 border-b border-muted-200 flex flex-wrap gap-4 bg-white rounded-t-2xl" method="GET">
                {{-- Search --}}
                <x-form.search-input name="search" :value="request('search')"
                    placeholder="{{ __('ai.search_placeholder') }}" />

                {{-- Visibility Filter --}}
                <x-form.select name="visibility" :value="request('visibility')" :options="[
            '' => __('ai.all_visibility'),
            'private' => 'Private',
            'team' => 'Team',
            'public' => 'Public',
        ]" :showChevron="true" />

                {{-- Sort --}}
                <x-form.select name="sort" :value="request('sort', 'desc')" :options="[
            'desc' => __('ai.newest_first'),
            'asc' => __('ai.oldest_first'),
        ]" :showChevron="true" />

                <div class="flex gap-2">
                    <button type="submit"
                        class="border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors"
                        title="{{ __('ai.filter') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path
                                d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                        </svg>
                    </button>

                    <a href="{{ route('ai-workspaces.index') }}"
                        class="flex items-center justify-center border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors"
                        title="{{ __('ai.reset') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path
                                d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                        </svg>
                    </a>
                </div>
            </form>

            {{-- TABLE --}}
            <div class="overflow-x-auto w-full h-[420px]">
                <table class="w-full table-fixed">
                    <thead class="bg-muted-50 border-b border-muted-200">
                        <tr>
                            <th
                                class="w-[25%] py-4 pl-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                {{ __('ai.name') }}
                            </th>
                            <th
                                class="w-[15%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                {{ __('ai.visibility') }}
                            </th>
                            <th
                                class="w-[15%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                {{ __('ai.files') }}
                            </th>
                            <th
                                class="w-[15%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                {{ __('ai.storage') }}
                            </th>
                            <th
                                class="w-[15%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                {{ __('ai.status') }}
                            </th>
                            <th
                                class="w-[15%] py-4 pr-6 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                {{ __('ai.created') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-muted-100">
                        @forelse($workspaces as $workspace)
                            @php
                                $ingestedCount = $workspace->ingestedFiles()->count();
                                $storageMb = round(($workspace->storage_size ?? 0) / (1024 * 1024), 1);

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
                                if (in_array(strtolower($derivedStatus), ['ready', 'active', 'completed'])) {
                                    $statusClass = 'bg-accent/10 text-accent';
                                } elseif (in_array(strtolower($derivedStatus), ['processing', 'pending', 'draft'])) {
                                    $statusClass = 'bg-primary/10 text-primary';
                                } elseif (in_array(strtolower($derivedStatus), ['empty', 'inactive'])) {
                                    $statusClass = 'bg-muted-100 text-muted-600';
                                }

                                $visibilityClass = 'bg-muted-100 text-muted-600';
                                if ($workspace->visibility === 'private') {
                                    $visibilityClass = 'bg-blue-500/10 text-blue-500';
                                } elseif ($workspace->visibility === 'team') {
                                    $visibilityClass = 'bg-violet-500/10 text-violet-500';
                                } elseif ($workspace->visibility === 'public') {
                                    $visibilityClass = 'bg-green-500/10 text-green-500';
                                }
                            @endphp

                            <tr class="hover:bg-canvas transition-colors group relative">
                                {{-- Name --}}
                                <td class="py-4 pl-6 text-sm font-medium text-main">
                                    <a href="{{ route('ai-workspaces.show', $workspace) }}"
                                        class="hover:text-primary hover:underline transition-colors">
                                        {{ $workspace->name }}
                                    </a>
                                </td>

                                {{-- Visibility --}}
                                <td class="py-4 px-3 text-center text-sm">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $visibilityClass }}">
                                        {{ ucfirst($workspace->visibility) }}
                                    </span>
                                </td>

                                {{-- File Count --}}
                                <td class="py-4 px-3 text-center text-sm text-muted-500">
                                    <span class="bg-white border border-muted-200 px-2 py-0.5 rounded-full text-xs">
                                        {{ $workspace->file_count }}
                                    </span>
                                </td>

                                {{-- Storage --}}
                                <td class="py-4 px-3 text-center text-sm text-muted-500">
                                    {{ number_format($storageMb, 1) }} MB
                                </td>

                                {{-- Status --}}
                                <td class="py-4 px-3 text-center">
                                    <div
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ ucfirst($derivedStatus) }}
                                    </div>
                                </td>

                                {{-- Created --}}
                                <td class="py-4 pr-6 text-center text-sm text-muted-500">
                                    {{ $workspace->created_at ? $workspace->created_at->format('d/m/Y') : 'N/A' }}
                                </td>

                                {{-- ACTIONS --}}
                                <!-- <td class="py-4 pr-6 text-right relative">
                                                    <div class="relative flex justify-end">
                                                        <button onclick="toggleActionMenu(event, 'menu-{{ $workspace->id }}')"
                                                            class="p-2 rounded-lg text-muted-400 hover:bg-muted-100 hover:text-main transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor" stroke-width="2">
                                                                <circle cx="12" cy="12" r="1"></circle>
                                                                <circle cx="19" cy="12" r="1"></circle>
                                                                <circle cx="5" cy="12" r="1"></circle>
                                                            </svg>
                                                        </button>

                                                        <div id="menu-{{ $workspace->id }}"
                                                            class="dropdown-menu hidden absolute right-0 top-full mt-2 w-44 bg-white border border-muted-200 rounded-xl shadow-xl shadow-main/10 z-50 flex-col py-1.5 origin-top-right">

                                                            <a href="{{ route('ai-workspaces.show', $workspace) }}"
                                                                class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm text-muted-600 hover:bg-muted-50 hover:text-main transition-colors">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted-400"
                                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                </svg>
                                                                {{ __('ai.view') }}
                                                            </a>

                                                            <a href="{{ route('ai-workspaces.edit', $workspace) }}"
                                                                class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm text-muted-600 hover:bg-muted-50 hover:text-main transition-colors">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted-400"
                                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                                {{ __('ai.edit') }}
                                                            </a>

                                                            <div class="h-px bg-muted-100 my-1 mx-2"></div>

                                                            <form action="{{ route('ai-workspaces.destroy', $workspace) }}" method="POST"
                                                                onsubmit="return confirm('{{ __('ai.confirm_delete') }}')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button
                                                                    class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm text-danger hover:bg-danger/5 transition-colors">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                    {{ __('ai.delete') }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td> -->
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="p-3 rounded-full bg-muted-100 text-muted-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <p class="text-muted-500 font-medium">{{ __('ai.no_workspaces') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($workspaces instanceof \Illuminate\Pagination\LengthAwarePaginator && $workspaces->hasPages())
                <div class="mt-6 flex justify-center w-full pb-6">
                    {{ $workspaces->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function toggleActionMenu(event, menuId) {
            event.stopPropagation();
            const menu = document.getElementById(menuId);
            const isHidden = menu.classList.contains('hidden');

            closeAllMenus();

            if (isHidden) {
                menu.classList.remove('hidden');
            }
        }

        function closeAllMenus() {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }

        document.addEventListener('click', function (event) {
            const isClickInsideDropdown = event.target.closest('.dropdown-menu');
            const isClickInsideButton = event.target.closest('button[onclick^="toggleActionMenu"]');

            if (!isClickInsideDropdown && !isClickInsideButton) {
                closeAllMenus();
            }
        });
    </script>
@endsection