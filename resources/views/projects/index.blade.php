@extends('layout_dashboard')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        // Determine dashboard route based on role
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

    <div class="flex flex-col w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        {{-- HEADER SECTION --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full mb-8">
            <div class="flex items-center gap-4">
                <x-back-btn :route="$dashRoute" />
                <div>
                    <h2 class="font-bold text-3xl text-main tracking-tight">
                        {{ __('projects.project_list') }}
                    </h2>
                    <p class="text-muted-500 text-sm mt-2">{{ __('projects.subtitle') }}</p>
                </div>
            </div>

            @can('admin.projects.create')
            <a href="{{ route('projects.create') }}"
                class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                    <path d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                </svg>
                <span class="font-medium">{{ __('projects.create') }}</span>
            </a>
            @endcan
        </div>

        <div class="animate-fade-in-up">
            {{-- CARD CONTAINER --}}
            <div
                class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-hidden flex flex-col">

                {{-- FILTERS --}}
                <form method="GET" class="m-5 flex flex-wrap gap-4 bg-white">
                    <x-form.search-input
                        name="search"
                        :value="request('search')"
                        placeholder="{{ __('projects.search_placeholder') }}"
                    />

                    <x-form.select
                        name="sort_dir"
                        :value="request('sort_dir')"
                        :options="[
                            '' => 'Default Sort',
                            'asc' => 'A → Z',
                            'desc' => 'Z → A',
                        ]"
                        :showChevron="true"
                    />

                    {{-- Date From --}}
                    <div class="relative group">
                        <span class="absolute -top-2 left-3 bg-white px-1 text-xs font-medium text-muted-400 group-focus-within:text-primary transition-colors">
                            {{ __('tasks.filter_label_from') }}
                        </span>

                        <x-form.input
                            type="date"
                            name="start_date"
                            :value="request('start_date')"
                        />
                    </div>

                    {{-- Date To --}}
                    <div class="relative group">
                        <span class="absolute -top-2 left-3 bg-white px-1 text-xs font-medium text-muted-400 group-focus-within:text-primary transition-colors">
                            {{ __('tasks.filter_label_to') }}
                        </span>

                        <x-form.input
                            type="date"
                            name="due_date"
                            :value="request('due_date')"
                        />
                    </div>

                    <div class="flex gap-2">
                        {{-- Filter button --}}
                        <button type="submit" title="{{ __('tasks.filter') }}"
                            class="border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                                <path
                                    d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                            </svg>
                        </button>

                        {{-- Reset button --}}
                        <a href="{{ route('projects.index') }}" title="{{ __('tasks.reset') }}"
                            class="flex items-center justify-center border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                                <path
                                    d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                            </svg>
                        </a>
                    </div>
                </form>

                {{-- TABLE --}}
                {{-- Projects Table --}}
                <div class="overflow-x-auto w-full">
                    <table class="w-full overflow-scroll min-w-[900px]">
                        <thead class="bg-muted-50 border-y border-muted-200">
                            <tr>
                                <th
                                    class="w-[40%] py-4 pl-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                    {{ __('projects.title') }}</th>
                                <th
                                    class="w-[15%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                    {{ __('projects.status') }}</th>
                                <th
                                    class="w-[15%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                    {{ __('projects.start_date') }}</th>
                                <th
                                    class="w-[15%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                    {{ __('projects.due_date') }}</th>
                                <th
                                    class="w-[15%] py-4 pr-6 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">
                                    {{ __('projects.percentage') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-muted-200">
                            @forelse($projects as $project)
                                @php
                                    $overdue = \Carbon\Carbon::parse($project->due_date)->isPast()
                                        && $project->status !== 'completed';

                                    $pillClass = match ($project->status) {
                                        'active' => 'bg-accent/10 text-accent ring-accent/20',
                                        default => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                                    };
                                @endphp
                                    <td class="py-4 pl-6 text-sm font-medium text-main">
                                        <a href="{{ route('projects.details', $project->id) }}" class="hover:text-primary hover:underline transition-colors">
                                            {{ $project->title }}
                                        </a>
                                    </td>
                                    <td class="py-4 px-3">
                                        <div class="flex justify-center">
                                            <div
                                                class="text-center w-fit px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $pillClass }}">
                                                {{ ucfirst($project->status) }}
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-4 px-3 text-sm text-muted-500 text-center">
                                        {{ \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') }}
                                    </td>

                                    <td class="py-4 px-3 text-sm text-muted-500 text-center">
                                        {{ \Carbon\Carbon::parse($project->due_date)->format('d/m/Y') }}
                                        @if($overdue)
                                            <span class="text-red-500 ml-1">⚠</span>
                                        @endif
                                    </td>

                                    <td class="py-4 pr-6">
                                        <div class="h-5 w-full bg-muted-100 rounded overflow-hidden border border-muted-200">
                                            @php
                                                $percentage = $project->percentage ?? 0;
                                                $progressColor = 'bg-gradient-to-r from-green-400 to-green-500';
                                            @endphp
                                            <div class="h-full {{ $progressColor }} transition-all duration-500"
                                                style="width: {{ $percentage }}%">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <div class="p-3 rounded-full bg-muted-100 text-muted-400">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <p class="text-muted-500 font-medium">{{ __('projects.no_projects') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($projects instanceof \Illuminate\Pagination\LengthAwarePaginator && $projects->hasPages())
                    <div class="my-6 flex justify-center w-full">
                        {{ $projects->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection