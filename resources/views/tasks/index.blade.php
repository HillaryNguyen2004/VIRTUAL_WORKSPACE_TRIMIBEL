@extends('layout_dashboard')

@section('content')

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
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full">
            <div class="flex items-center gap-4">
                <x-back-btn :route="$dashRoute" />
                <div>
                    <h2 class="font-bold text-3xl text-main tracking-tight">
                        {{ __('tasks.task_list') }}
                    </h2>
                    <p class="text-muted-500 text-sm mt-2">{{ __('tasks.subtitle') }}</p>
                </div>
            </div>

            @can('task.create')
                <a href="{{ route('tasks.create') }}"
                    class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-primary/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                        <path
                            d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                    </svg>
                    <span class="font-medium">{{ __('staff_dashboard.new_task') }}</span>
                </a>
            @endcan
        </div>

        {{-- COMBINED CARD CONTAINER (Filters + Table) --}}
        <div
            class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-hidden flex flex-col animate-fade-in-up">

            {{-- SEARCH & FILTER BAR (Now inside the card) --}}
            <form class="p-5 border-b border-muted-200 flex flex-wrap gap-4 bg-white" method="GET">
                {{-- Search --}}
                <x-form.search-input name="search" id="search" placeholder="tasks.search_placeholder"
                    :value="request('search')" />

                {{-- Status --}}
                <x-form.select name="status" id="status" placeholder="tasks.all_statuses" :value="request('status')"
                    :options="[
                        'pending' => __('tasks.pending'),
                        'in_progress' => __('tasks.in_progress'),
                        'completed' => __('tasks.completed'),
                    ]" />

                {{-- Sort --}}
                <x-form.select name="sort_dir" :value="request('sort_dir')" placeholder="tasks.default_sort" 
                    :options="[
                        'asc'  => 'A → Z',
                        'desc' => 'Z → A',
                    ]" />

                {{-- Project Filter --}}
                <x-form.select name="project_id" id="project_id" placeholder="tasks.project" :value="request('project_id')" class="w-40"
                    :options="$projectOptions->pluck('title', 'id')->toArray()" />

                {{-- Date From --}}
                <div class="relative group">
                    <span
                        class="absolute -top-2 left-3 bg-white px-1 text-xs font-medium text-muted-400 group-focus-within:text-primary transition-colors">
                        {{ __('tasks.filter_label_from') }}
                    </span>

                    <x-form.input type="date" name="start_date" :value="request('start_date')" />
                </div>

                {{-- Date To --}}
                <div class="relative group">
                    <span
                        class="absolute -top-2 left-3 bg-white px-1 text-xs font-medium text-muted-400 group-focus-within:text-primary transition-colors">
                        {{ __('tasks.filter_label_to') }}
                    </span>

                    <x-form.input type="date" name="due_date" :value="request('due_date')" />
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
                    <a href="{{ url()->current() }}" title="{{ __('tasks.reset') }}"
                        class="flex items-center justify-center border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path
                                d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                        </svg>
                    </a>
                </div>
            </form>

            {{-- TABLE SECTION --}}
            <div class="w-full overflow-x-auto">
                {{-- ADDED: table-fixed to enforce strict widths --}}
                <table class="min-w-[900px] w-full table-fixed">
                    <thead class="bg-muted-50 border-b border-muted-200">
                        <tr>
                            <th class="w-[25%] py-4 pl-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                {{ __('tasks.task_name') }}</th>
                            <th class="w-[20%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                {{ __('tasks.project') }}</th>
                            <th class="w-[15%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                {{ __('tasks.assignee') }}</th>
                            <th class="w-[12%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                {{ __('tasks.status') }}</th>
                            <th class="w-[10%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                {{ __('tasks.start_date') }}</th>
                            <th class="w-[10%] py-4 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                {{ __('tasks.due_date') }}</th>
                            <th class="w-[8%] py-4 pr-6 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                {{ __('tasks.percentage') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-muted-100">
                        @forelse($tasks as $task)
                            @php
                                $overdue = \Illuminate\Support\Carbon::parse($task->due_date)->isPast()
                                    && $task->status !== 'completed';

                                $pillClass = match ($task->status) {
                                    'pending' => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                                    'completed' => 'bg-accent/10 text-accent ring-accent/20',
                                    'in_progress' => 'bg-secondary/10 text-secondary ring-secondary/20',
                                    default => 'bg-primary/10 text-primary ring-primary/20',
                                };
                            @endphp

                            <tr class="hover:bg-canvas transition-colors">
                                {{-- Task Name --}}
                                <td class="py-3 pl-6 text-sm font-medium text-main">
                                    <div class="flex items-center gap-2">
                                        @if($task->isUnread())
                                            <span class="w-2 h-2 rounded-full bg-red-500 shadow-sm shadow-red-500/50 flex-shrink-0 animate-pulse" title="New/Updated"></span>
                                        @endif
                                        <a href="{{ route('tasks.details', $task->id) }}" class="hover:text-primary hover:underline">{{ $task->title }}</a>
                                    </div>
                                </td>

                                {{-- Project --}}
                                <td class="py-3 px-3 text-sm font-medium text-main text-center">
                                    <span class="truncate" title="{{ $task->project->title ?? 'N/A' }}">
                                        {{ $task->project->title ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Assignee --}}
                                <td class="py-3 px-3 text-sm text-center font-medium text-main">
                                    <div class="flex justify-center -space-x-2">
                                        @forelse($task->assignedUsers as $user)
                                            <!-- <div class="w-7 h-7 rounded-full bg-muted-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-muted-600" title="{{ $user->name }}">
                                                {{ mb_substr($user->name, 0, 1) }}
                                            </div> -->
                                            <span class="truncate" title="{{ $user->name }}">
                                                {{ $user->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted-400 italic text-xs">Unassigned</span>
                                        @endforelse
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="py-3 px-3">
                                    <div class="flex items-center justify-center">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $pillClass }}">
                                            {{ __('tasks.' . $task->status) }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Start Date --}}
                                <td class="py-3 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') }}
                                </td>

                                {{-- Due Date --}}
                                <td class="py-3 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                                    @if($overdue)
                                        <span class="text-red-500 ml-1">⚠</span>
                                    @endif
                                </td>

                                {{-- Progress --}}
                                <td class="py-3 pr-6">
                                    <div class="flex items-center justify-center">
                                        <div class="relative w-full h-3 bg-muted-100 rounded overflow-hidden border border-muted-200">
                                            <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-green-400 to-green-500 transition-all duration-500" style="width: {{ $task->percentage ?? 0 }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- Subtasks --}}
                            @foreach ($task->subtasks as $subtask)
                                @php
                                    $subOverdue = \Illuminate\Support\Carbon::parse($subtask->due_date)->isPast()
                                        && $subtask->status !== 'completed';

                                    $subPillClass = match ($subtask->status) {
                                        'pending' => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                                        'completed' => 'bg-accent/10 text-accent ring-accent/20',
                                        'in_progress' => 'bg-secondary/10 text-secondary ring-secondary/20',
                                        default => 'bg-primary/10 text-primary ring-primary/20',
                                    };
                                @endphp
                                <tr class="hover:bg-canvas transition-colors">
                                    {{-- Task Name --}}
                                    <td class="py-2.5 pl-6 text-sm font-medium text-main">
                                        <div class="flex items-center gap-2 pl-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3.5 h-3.5 fill-current rotate-90">
                                                <path d="M297.4 201.4C309.9 188.9 330.2 188.9 342.7 201.4L502.7 361.4C515.2 373.9 515.2 394.2 502.7 406.7C490.2 419.2 469.9 419.2 457.4 406.7L320 269.3L182.6 406.6C170.1 419.1 149.8 419.1 137.3 406.6C124.8 394.1 124.8 373.8 137.3 361.3L297.3 201.3z" />
                                            </svg>
                                            @if($subtask->isUnread())
                                                <span class="w-2 h-2 rounded-full bg-red-500 shadow-sm shadow-red-500/50 flex-shrink-0 animate-pulse" title="New/Updated"></span>
                                            @endif
                                            <a href="{{ route('tasks.details', $subtask->id) }}" class="text-sm hover:text-primary hover:underline">{{ $subtask->title }}</a>
                                        </div>
                                    </td>

                                    {{-- Project --}}
                                    <td class="py-2.5 px-3 text-sm font-medium text-main text-center">
                                        <span class="truncate" title="{{ $task->project->title ?? 'N/A' }}">
                                            {{ $task->project->title ?? 'N/A' }}
                                        </span>
                                    </td>

                                    {{-- Assignee --}}
                                    <td class="py-2.5 px-3 text-sm text-center font-medium text-main">
                                        <div class="flex justify-center -space-x-1">
                                            @forelse($subtask->assignedUsers as $user)
                                                <!-- <div class="w-7 h-7 rounded-full bg-muted-100 border border-white flex items-center justify-center text-[10px] font-bold" title="{{ $user->name }}">
                                                    {{ mb_substr($user->name, 0, 1) }}
                                                </div> -->
                                                <span class="truncate" title="{{ $user->name }}">
                                                    {{ $user->name }}
                                                </span>
                                            @empty
                                            @endforelse
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="py-2.5 px-3">
                                        <div class="flex items-center justify-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $subPillClass }}">
                                                {{ __('tasks.' . $subtask->status) }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Start Date --}}
                                    <td class="py-2.5 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($subtask->start_date)->format('d/m/Y') }}
                                    </td>

                                    {{-- Due Date --}}
                                    <td class="py-2.5 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                        <span class="{{ $subOverdue ? 'text-red-400 font-medium' : '' }}">
                                            {{ \Carbon\Carbon::parse($subtask->due_date)->format('d/m/Y') }}
                                        </span>
                                    </td>

                                    {{-- Progress --}}
                                    <td class="py-2.5 pr-6">
                                        <div class="flex items-center justify-center">
                                            <div class="relative w-full h-3 bg-muted-100 rounded overflow-hidden border border-muted-200">
                                                <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-green-400 to-green-500 transition-all duration-500" style="width: {{ $task->percentage ?? 0 }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="p-3 rounded-full bg-muted-100 text-muted-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                        </div>
                                        <p class="text-muted-500 font-medium">{{ __('tasks.no_tasks') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($tasks instanceof \Illuminate\Pagination\LengthAwarePaginator && $tasks->hasPages())
                <div class="my-6 flex justify-center w-full">
                    {{ $tasks->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </div>
@endsection