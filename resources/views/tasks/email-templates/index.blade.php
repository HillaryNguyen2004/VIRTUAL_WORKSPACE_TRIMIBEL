@extends('layout_dashboard')

@section('content')
    @vite(['resources/js/toggle_view.js'])

    @php
        use Illuminate\Support\Facades\Route;

        // Determine dashboard route based on role
        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }
    @endphp

    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER SECTION --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full mb-8">
            <div class="flex items-center gap-4">
                @include('components.back-btn' , ['route' => $dashRoute])
                
                <div>
                    <h2 class="font-bold text-3xl text-main tracking-tight">
                        {{ __('template.title') }}
                    </h2>
                    <p class="text-muted-500 text-sm mt-2">{{ __('template.subtitle') }}</p>
                </div>
            </div>

            <a href="{{ route('email-templates.create') }}"
                class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                    <path d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                </svg>
                <span class="font-medium">{{ __('template.add_new_template') }}</span>
            </a>
        </div>

        {{-- CARD CONTAINER --}}
        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-hidden flex flex-col animate-fade-in-up">

            {{-- SEARCH & FILTER BAR --}}
            <form class="p-5 border-b border-muted-200 flex flex-wrap gap-4 bg-white" method="GET">
                <div class="flex-1 min-w-[200px] relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-muted-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input name="search" id="search" type="text" placeholder="{{ __('template.search_placeholder') }}" value="{{ request('search') }}"
                        class="block w-full pl-10 bg-canvas border border-muted-200 text-main py-2.5 px-4 rounded-xl placeholder-muted-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>

                <select name="sort_by"
                    class="bg-canvas border border-muted-200 text-main py-2.5 px-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all cursor-pointer hover:border-primary/50">
                    <option value="">{{ __('template.sort_by') }}</option>
                    <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>{{ __('template.sort_by_created_at') }}</option>
                    <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>{{ __('template.sort_by_name') }}</option>
                </select>

                <select name="sort_dir"
                    class="bg-canvas border border-muted-200 text-main py-2.5 px-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all cursor-pointer hover:border-primary/50">
                    <option value="">{{ __('template.default_sort') }}</option>
                    <option value="desc" {{ request('sort_dir') === 'desc' ? 'selected' : '' }}>{{ __('template.desc') }}</option>
                    <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>{{ __('template.asc') }}</option>
                </select>

                <div class="flex gap-2">
                    {{-- Filter button --}}
                    <button type="submit" title="{{ __('tasks.filter') }}"
                        class="border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                        </svg>
                    </button>

                    {{-- Reset button --}}
                    <a href="{{ route('email-templates.index') }}" title="{{ __('tasks.reset') }}"
                        class="flex items-center justify-center border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                        </svg>
                    </a>
                </div>
            </form>

            {{-- TABLE SECTION --}}
            <div class="overflow-x-auto w-full">
                <table class="w-full table-fixed">
                    <thead class="bg-muted-50 border-b border-muted-200">
                        <tr>
                            <th class="w-[10%] py-4 pl-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">ID</th>
                            <th class="w-[30%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('template.name') }}</th>
                            <th class="w-[45%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('template.subject') }}</th>
                            <th class="w-[15%] py-4 pr-6 text-right text-xs font-semibold text-muted-400 uppercase tracking-wider"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-muted-100">
                        @forelse($templates as $template)
                            <tr class="hover:bg-canvas transition-colors group">
                                {{-- ID --}}
                                <td class="py-4 pl-6 text-sm text-muted-500">
                                    <div class="truncate" title="{{ $template->id }}">{{ $template->id }}</div>
                                </td>

                                {{-- Name --}}
                                <td class="py-4 px-3 text-sm font-medium text-main">
                                    <div class="truncate" title="{{ $template->name }}">{{ $template->name }}</div>
                                </td>

                                {{-- Subject --}}
                                <td class="py-4 px-3 text-sm text-muted-500">
                                    <div class="truncate" title="{{ $template->subject }}">{{ $template->subject }}</div>
                                </td>

                                {{-- Actions --}}
                                <td class="py-4 pr-6 text-right">
                                    <div class="flex justify-end gap-1">
                                        {{-- Toggle View --}}
                                        <button class="toggle-row p-1.5 rounded-lg text-muted-400 hover:bg-primary/5 hover:text-primary transition-colors focus:outline-none"
                                            id="view-btn-{{ $template->id }}"
                                            data-target="taskDetails{{ $template->id }}"
                                            aria-controls="taskDetails{{ $template->id }}"
                                            aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                                <path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320z" />
                                            </svg>
                                        </button>

                                        {{-- Edit --}}
                                        <a href="{{ route('email-templates.edit', $template) }}"
                                            class="p-1.5 rounded-lg text-muted-400 hover:bg-secondary/10 hover:text-secondary transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                                <path d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7z" />
                                            </svg>
                                        </a>

                                        {{-- Delete --}}
                                        <form action="{{ route('email-templates.destroy', $template) }}" method="POST"
                                            onsubmit="return confirm('{{ __('template.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="p-1.5 rounded-lg text-muted-400 hover:bg-danger/10 hover:text-danger transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                                    <path d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Details Row --}}
                            <tr id="taskDetails{{ $template->id }}" class="hidden bg-canvas border-b border-muted-100 shadow-inner">
                                <td colspan="4" class="p-6">
                                    <div class="flex flex-col gap-6">
                                        <div>
                                            <strong class="text-sm font-bold text-main">{{ __('template.description') }}</strong>
                                            <p class="text-muted-600 text-sm mt-2 leading-relaxed whitespace-pre-line">
                                                {{ $template->description ?? 'No description provided.' }}
                                            </p>
                                        </div>

                                        <div class="bg-white border border-muted-200 rounded-xl p-4">
                                            <strong class="text-sm font-bold text-main block mb-2">{{ __('template.content') }} preview</strong>
                                            <div class="text-muted-600 text-sm font-mono bg-muted-50 p-3 rounded-lg max-h-[300px] overflow-auto">
                                                {{-- Using strip_tags to safely preview content or keep {!! !!} if you trust the source --}}
                                                {!! $template->content !!}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="p-3 rounded-full bg-muted-100 text-muted-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <p class="text-muted-500 font-medium">{{ __('template.no_templates_found') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($templates instanceof \Illuminate\Contracts\Pagination\Paginator && $templates->hasPages())
                <div class="mt-6 flex justify-center w-full pb-6">
                    {{ $templates->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>
@endsection