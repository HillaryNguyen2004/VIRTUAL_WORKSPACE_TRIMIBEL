@extends('layout_dashboard')

@section('content')
    @vite(['resources/js/toggle_update_phase.js'])
    @php
        // Determine dashboard route based on role for back button
        if (auth()->user()->hasRole('admin')) {
            $dashRoute = 'projects.index';
        } else {
            $dashRoute = 'projects.index';
        }

        // Status styling
        $statusColors = match ($project->status) {
            'active' => ['class' => 'bg-emerald-100 text-emerald-700', 'label' => ucfirst($project->status)],
            'completed' => ['class' => 'bg-blue-100 text-blue-700', 'label' => ucfirst($project->status)],
            'inactive' => ['class' => 'bg-gray-100 text-gray-700', 'label' => ucfirst($project->status)],
            default => ['class' => 'bg-indigo-100 text-indigo-700', 'label' => ucfirst($project->status)],
        };
    @endphp

    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER WITH PROJECT TITLE AND ACTIONS --}}
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-4">
                <x-back-btn :route="$dashRoute" />
                <h1 class="font-bold text-2xl text-main">
                    Project #{{ str_pad($project->id, 5, '0', STR_PAD_LEFT) }}
                </h1>
            </div>

            <div class="flex gap-2">
                @can('admin.projects.edit')
                    <a href="{{ route('projects.edit', $project->id) }}"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl transition-all shadow-lg text-sm border text-muted-400 hover:bg-secondary/10 hover:text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                            <path
                                d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7z" />
                        </svg>
                        {{ __('projects.edit') }}
                    </a>
                @endcan
                @can('admin.projects.delete')
                    @if($project->tasks()->count() === 0)
                        <form method="POST" action="{{ route('projects.destroy', $project->id) }}"
                            onsubmit="return confirm('{{ __('projects.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="flex items-center gap-2 px-5 py-2.5 rounded-xl transition-all shadow-lg text-sm bg-danger hover:bg-danger/80 text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                    <path
                                        d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
                                </svg>
                                {{ __('projects.delete') }}
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="flex items-center gap-3 bg-accent/10 border border-accent/20 text-accent p-4 rounded-xl">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        {{-- MAIN CONTENT CARD --}}
        <div
            class="w-full bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 relative overflow-hidden animate-fade-in-up">

            {{-- PROJECT TITLE --}}
            <div class="px-6 py-4 border-b border-muted-200 bg-primary/10">
                <h2 class="text-xl font-semibold text-primary">{{ $project->title }}</h2>
            </div>

            {{-- METADATA --}}
            <div class="px-6 py-3 bg-muted-50 border-b border-muted-200 flex gap-6 text-sm text-muted-600">
                <span>{{ __('tasks.created_at') }}:
                    {{ $project->created_at ? $project->created_at->format('d/m/Y') : 'N/A' }}</span>
                <span>{{ __('tasks.updated_at') }}:
                    {{ $project->updated_at ? $project->updated_at->format('d/m/Y') : 'N/A' }}</span>
            </div>

            {{-- TWO-COLUMN LAYOUT --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 px-6 py-6">

                {{-- LEFT COLUMN --}}
                <div class="space-y-3">
                    {{-- Status --}}
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">{{ __('projects.status') }}:</div>
                        <div class="flex-1">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $statusColors['class'] }}">
                                {{ $statusColors['label'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Staff / Manager --}}
                    @if($project->staffUser)
                        <div class="flex">
                            <div class="w-36 text-sm font-semibold text-muted-600">{{ __('projects.staff') }}:</div>
                            <div class="flex-1 text-sm">
                                <a href="mailto:{{ $project->staffUser->email }}"
                                    class="hover:text-primary hover:underline">{{ $project->staffUser->name }}</a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- RIGHT COLUMN --}}
                <div class="space-y-3 mt-3 lg:mt-0">
                    {{-- Start Date --}}
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">{{ __('projects.start_date') }}:</div>
                        <div class="flex-1 text-sm">
                            {{ \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') }}
                        </div>
                    </div>

                    {{-- Due Date --}}
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">{{ __('projects.due_date') }}:</div>
                        <div class="flex-1 text-sm">
                            {{ \Carbon\Carbon::parse($project->due_date)->format('d/m/Y') }}
                        </div>
                    </div>

                    {{-- Percentage --}}
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">% {{ __('projects.done') }}:</div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-5 bg-muted-100 rounded overflow-hidden border border-muted-200">
                                    <div class="h-full bg-gradient-to-r from-green-400 to-green-500 transition-all duration-500 flex items-center justify-center"
                                        style="width: {{ $project->percentage }}%">
                                        @if($project->percentage > 15)
                                            <span
                                                class="text-[10px] font-semibold text-white">{{ $project->percentage }}%</span>
                                        @endif
                                    </div>
                                </div>
                                @if($project->percentage <= 15)
                                    <span
                                        class="text-xs font-semibold text-muted-600 min-w-[35px]">{{ $project->percentage }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DESCRIPTION SECTION --}}
            <div class="px-6 py-4 border-t border-muted-200">
                <h3 class="text-sm font-semibold text-muted-700 mb-3">{{ __('projects.description') }}</h3>
                <div class="text-sm text-muted-600 leading-relaxed bg-muted-50 rounded p-4 border border-muted-200">
                    @if($project->description)
                        {!! $project->description !!}
                    @else
                        <p class="text-muted-400 italic">{{ __('tasks.no_description') }}</p>
                    @endif
                </div>
            </div>

            {{-- TEAM MEMBERS SECTION --}}
            <div class="px-6 py-4 border-t border-muted-200">
                <h3 class="text-sm font-semibold text-muted-700 mb-3">{{ __('tasks.team_members') }}</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($project->teamMembers() as $member)
                        <div class="group relative">
                            <div
                                class="w-10 h-10 rounded-full bg-muted-100 flex items-center justify-center text-muted-600 font-bold ring-2 ring-white cursor-pointer shadow-sm group-hover:bg-primary group-hover:text-white transition-colors">
                                {{ substr($member->name, 0, 1) }}
                            </div>
                            <!-- Tooltip -->
                            <span
                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                                {{ $member->name }}
                            </span>
                        </div>
                    @empty
                        <div class="text-muted-400 text-sm italic">{{ __('tasks.no_users') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- PHASES SECTION --}}
            <div class="px-6 py-4 border-t border-muted-200">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-muted-700">{{ __('phases.project_phases') }}</h3>
                </div>

                <div class="max-h-[230px] overflow-y-auto mb-6">
                    @if($project->phases->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($project->phases as $phase)
                                <div
                                    class="p-4 border border-muted-200 rounded-xl bg-white hover:border-primary/50 transition-all group relative">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-semibold text-main">{{ $phase->title }}</h4>
                                        <div class="flex items-center gap-1">
                                            {{-- Edit Button --}}
                                            <button type="button"
                                                class="edit-phase-btn p-1.5 rounded-lg text-muted-400 hover:bg-secondary/10 hover:text-secondary transition-colors"
                                                data-id="{{ $phase->id }}" data-title="{{ $phase->title }}"
                                                data-start-date="{{ $phase->start_date ? \Carbon\Carbon::parse($phase->start_date)->format('Y-m-d') : '' }}"
                                                data-due-date="{{ $phase->due_date ? \Carbon\Carbon::parse($phase->due_date)->format('Y-m-d') : '' }}"
                                                data-action="{{ route('phases.update', $phase->id) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                                    class="w-4 h-4 fill-current">
                                                    <path
                                                        d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7z" />
                                                </svg>
                                            </button>

                                            @if($phase->tasks()->count() === 0)
                                                <form method="POST" action="{{ route('phases.destroy', $phase->id) }}"
                                                    onsubmit="return confirm('{{ __('phases.delete_confirm') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-1.5 rounded-lg text-muted-400 hover:bg-danger/10 hover:text-danger transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-xs text-muted-500 flex flex-col gap-1">
                                        @if($phase->start_date || $phase->due_date)
                                            <div class="flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                    </path>
                                                </svg>
                                                <span>
                                                    {{ $phase->start_date ? \Carbon\Carbon::parse($phase->start_date)->format('d/m/Y') : '...' }}
                                                    -
                                                    {{ $phase->due_date ? \Carbon\Carbon::parse($phase->due_date)->format('d/m/Y') : '...' }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                                </path>
                                            </svg>
                                            <span>{{ __('phases.tasks_count', ['count' => $phase->tasks->count()]) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div
                            class="text-sm text-muted-500 italic bg-muted-50 p-4 rounded-xl border border-dashed border-muted-300 text-center">
                            {{ __('phases.no_phases') }}
                        </div>
                    @endif
                </div>

                {{-- Add Phase --}}
                <div class="mb-6">
                    <div class="bg-muted-50 p-5 rounded-xl border border-muted-200">
                        <form action="{{ route('phases.store', $project->id) }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                <div class="md:col-span-2 text-sm">
                                    <x-form.input label="phases.phase_title" name="title"
                                        placeholder="e.g. Planning, Development" isRequired="true" />
                                </div>
                                <div>
                                    <x-form.input type="date" label="phases.start_date" name="start_date" />
                                </div>
                                <div>
                                    <x-form.input type="date" label="phases.due_date" name="due_date" />
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <button type="submit"
                                    class="px-5 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-hover shadow-lg shadow-primary/20 transition-all">
                                    {{ __('app.btn_create') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- TASKS LIST --}}
            <div class="px-6 py-4 border-t border-muted-200">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-muted-700">
                        {{ __('tasks.task_list') }}
                    </h3>
                    @can('task.create')
                        <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}"
                            class="text-sm text-primary hover:underline">
                            {{ __('tasks.add') }}
                        </a>
                    @endcan
                </div>

                <div class="w-full overflow-x-auto rounded border border-muted-200">
                    <table class="min-w-[900px] w-full table-fixed">
                        <thead class="bg-muted-50 border-b border-muted-200">
                            <tr>
                                <th
                                    class="w-[33%] py-3 pl-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                    {{ __('tasks.task_name') }}
                                </th>
                                <th
                                    class="w-[15%] py-3 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                    {{ __('tasks.assignee') }}
                                </th>
                                <th
                                    class="w-[12%] py-3 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                    {{ __('tasks.status') }}
                                </th>
                                <th
                                    class="w-[12%] py-3 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                    {{ __('tasks.start_date') }}
                                </th>
                                <th
                                    class="w-[12%] py-3 pr-6 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                    {{ __('tasks.due_date') }}
                                </th>
                                <th
                                    class="w-[16%] py-3 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                    {{ __('tasks.percentage') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-muted-100 bg-white">
                            @php
                                // Group current page tasks by phase
                                $tasksByPhase = $tasks->groupBy(function ($task) {
                                    return $task->phase_id ?? 'no_phase';
                                });

                                // Sort to put tasks with phases first, then no_phase at the end
                                $sortedPhases = $tasksByPhase->sortBy(function ($tasks, $phaseKey) {
                                    return $phaseKey === 'no_phase' ? 999999 : $phaseKey;
                                });
                            @endphp

                            @forelse($sortedPhases as $phaseKey => $phaseTasks)
                                @php
                                    $phase = $phaseKey !== 'no_phase' ? $project->phases->find($phaseKey) : null;
                                    $phaseName = $phase ? $phase->title : __('phases.no_phase');
                                @endphp

                                {{-- PHASE HEADER ROW --}}
                                <tr class="bg-primary/5">
                                    <td colspan="6" class="py-2.5 pl-6 pr-3">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                            </svg>
                                            <span class="text-sm font-semibold text-primary">{{ $phaseName }}</span>
                                        </div>
                                    </td>
                                </tr>

                                @foreach($phaseTasks as $task)
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

                                    {{-- TASK ROW --}}
                                    <tr class="hover:bg-canvas transition-colors">
                                        {{-- title --}}
                                        <td class="w-[33%] py-3 pl-6 text-sm font-medium text-main">
                                            <div class="flex items-center gap-2">
                                                @if($task->isUnread())
                                                    <span
                                                        class="w-2 h-2 rounded-full bg-red-500 shadow-sm shadow-red-500/50 flex-shrink-0 animate-pulse"
                                                        title="New/Updated"></span>
                                                @endif
                                                <a href="{{ route('tasks.details', $task->id) }}"
                                                    class="hover:text-primary hover:underline">{{ $task->title }}</a>
                                            </div>
                                        </td>

                                        {{-- assignee --}}
                                        <td class="w-[15%] py-3 px-3 text-center text-sm font-medium text-main">
                                            @forelse($task->assignedUsers as $user)
                                                {{ $user->name }}
                                            @empty
                                                Unassigned
                                            @endforelse
                                        </td>

                                        {{-- status --}}
                                        <td class="w-[14%] py-3 px-3">
                                            <div class="flex items-center justify-center">
                                                <div
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $pillClass }}">
                                                    {{ __('tasks.' . $task->status) }}
                                                </div>
                                            </div>
                                        </td>

                                        {{-- start date --}}
                                        <td class="w-[11%] py-3 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') }}
                                        </td>

                                        {{-- due date --}}
                                        <td class="w-[11%] py-3 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                                            @if($overdue)
                                                <span class="text-red-500 ml-1">⚠</span>
                                            @endif
                                        </td>

                                        {{-- Progress Bar or Score --}}
                                        <td class="w-[16%] py-3 px-3">
                                            <div class="h-5 w-full bg-muted-100 rounded overflow-hidden border border-muted-200">
                                                @php
                                                    $percentage = $task->percentage ?? 0;
                                                    $progressColor = 'bg-gradient-to-r from-green-400 to-green-500';
                                                @endphp
                                                <div class="h-full {{ $progressColor }} transition-all duration-500"
                                                    style="width: {{ $percentage }}%">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    {{-- Subtask --}}
                                    @foreach ($task->subtasks as $subtask)
                                        @php
                                            $overdue = \Illuminate\Support\Carbon::parse($subtask->due_date)->isPast()
                                                && $subtask->status !== 'completed';

                                            $pillClass = match ($subtask->status) {
                                                'pending' => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                                                'completed' => 'bg-accent/10 text-accent ring-accent/20',
                                                'in_progress' => 'bg-secondary/10 text-secondary ring-secondary/20',
                                                default => 'bg-primary/10 text-primary ring-primary/20',
                                            };
                                        @endphp
                                        <tr class="hover:bg-canvas transition-colors">
                                            {{-- title --}}
                                            <td class="w-[33%] py-3 pl-6 text-sm font-medium text-main">
                                                <div class="flex items-center gap-2 pl-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-3.5 h-3.5 fill-current rotate-90">
                                                        <path d="M297.4 201.4C309.9 188.9 330.2 188.9 342.7 201.4L502.7 361.4C515.2 373.9 515.2 394.2 502.7 406.7C490.2 419.2 469.9 419.2 457.4 406.7L320 269.3L182.6 406.6C170.1 419.1 149.8 419.1 137.3 406.6C124.8 394.1 124.8 373.8 137.3 361.3L297.3 201.3z" />
                                                    </svg>
                                                    @if($subtask->isUnread())
                                                        <span
                                                            class="w-2 h-2 rounded-full bg-red-500 shadow-sm shadow-red-500/50 flex-shrink-0 animate-pulse"
                                                            title="New/Updated"></span>
                                                    @endif
                                                    <a href="{{ route('tasks.details', $subtask->id) }}"
                                                        class="hover:text-primary hover:underline">{{ $subtask->title }}</a>
                                                </div>
                                            </td>

                                            {{-- assignee --}}
                                            <td class="w-[15%] py-3 px-3 text-sm text-center font-medium text-main">
                                                @forelse($subtask->assignedUsers as $user)
                                                    {{ $user->name }}
                                                @empty
                                                    Unassigned
                                                @endforelse
                                            </td>

                                            {{-- status --}}
                                            <td class="w-[14%] py-3 px-3">
                                                <div class="flex items-center justify-center">
                                                    <div
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $pillClass }}">
                                                        {{ __('tasks.' . $subtask->status) }}
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- start date --}}
                                            <td class="w-[11%] py-3 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                                {{ \Carbon\Carbon::parse($subtask->start_date)->format('d/m/Y') }}
                                            </td>

                                            {{-- due date --}}
                                            <td class="w-[11%] py-3 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                                {{ \Carbon\Carbon::parse($subtask->due_date)->format('d/m/Y') }}
                                                @if($overdue)
                                                    <span class="text-red-500 ml-1">⚠</span>
                                                @endif
                                            </td>

                                            {{-- Progress Bar --}}
                                            <td class="w-[16%] py-3 px-3">
                                                <div class="h-5 w-full bg-muted-100 rounded overflow-hidden border border-muted-200">
                                                    @php
                                                        $percentage = $subtask->percentage ?? 0;
                                                        $progressColor = 'bg-gradient-to-r from-green-400 to-green-500';
                                                    @endphp
                                                    <div class="h-full {{ $progressColor }} transition-all duration-500"
                                                        style="width: {{ $percentage }}%">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach {{-- End of tasks loop within phase --}}
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-muted-400 text-sm">
                                        {{ __('tasks.no_tasks') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-center mt-4 px-2">
                    {{ $tasks->links('vendor.pagination.tailwind') }}
                </div>
            </div>
        </div>
    </div>
@endsection