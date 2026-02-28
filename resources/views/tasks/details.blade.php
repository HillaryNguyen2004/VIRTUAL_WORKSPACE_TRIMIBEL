@extends('layout_dashboard')

@section('content')
    @php
        $projectId = $task->project_id;
        $parentId = $task->parent_id;

        if ($parentId) {
            $dashRoute = 'tasks.details';
            $dashParams = ['task' => $parentId];
        } else if (auth()->user()->hasRole('user') || auth()->user()->hasRole('substaff')){
            $dashRoute = 'tasks.index';
        } else if ($projectId) {
            $dashRoute = 'projects.details';
            $dashParams = ['id' => $projectId];
        }

        // Status styling
        $statusConfig = [
            'pending' => ['class' => 'bg-muted-200 text-muted-700', 'label' => __('tasks.pending')],
            'in_progress' => ['class' => 'bg-blue-100 text-blue-700', 'label' => __('tasks.in_progress')],
            'completed' => ['class' => 'bg-green-100 text-green-700', 'label' => __('tasks.completed')]
        ];

        $priorityConfig = [
            'normal' => ['class' => 'bg-green-100 text-green-700', 'label' => __('tasks.normal')],
            'low' => ['class' => 'bg-blue-100 text-blue-700', 'label' => __('tasks.low')],
            'high' => ['class' => 'bg-amber-100 text-amber-700', 'label' => __('tasks.high')],
            'critical' => ['class' => 'bg-red-100 text-red-700', 'label' => __('tasks.high')],
        ];

        $currentStatus = $statusConfig[$task->status] ?? $statusConfig['pending'];
        $currentPriority = $priorityConfig[$task->priority] ?? $priorityConfig['normal'];
        $overdue = \Illuminate\Support\Carbon::parse($task->due_date)->isPast() && $task->status !== 'completed';
    @endphp

    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER WITH TASK ID AND ACTIONS --}}
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-4">
                @include('components.back-btn', ['route' => $dashRoute, 'params' => $dashParams ?? []])
                <h1 class="font-bold text-2xl text-main">
                    Task #{{ str_pad($task->id, 5, '0', STR_PAD_LEFT) }}
                </h1>
            </div>

            <div class="flex gap-2">
                @if(auth()->user()->hasRole('admin') || auth()->user()->hasDepartmentRolePermission('task.edit'))
                    <a href="{{ route('tasks.edit', $task->id) }}"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl transition-all shadow-lg text-sm border text-muted-400 hover:bg-secondary/10 hover:text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                            <path
                                d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7z" />
                        </svg>
                        {{ __('tasks.edit') }}
                    </a>
                @endif

                @if(auth()->user()->hasRole('admin') || auth()->user()->hasDepartmentRolePermission('task.delete'))
                    @if($task->subtasks()->count() === 0)
                        <form method="POST" action="{{ route('tasks.destroy', $task->id) }}"
                            onsubmit="return confirm('{{ __('tasks.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="flex items-center gap-2 px-5 py-2.5 rounded-xl transition-all shadow-lg text-sm bg-danger hover:bg-danger/80 text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                    <path
                                        d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
                                </svg>
                                {{ __('tasks.delete') }}
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        {{-- MAIN CONTENT CARD --}}
        <div
            class="w-full bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 relative overflow-hidden animate-fade-in-up">

            {{-- PROJECT INFO BAR --}}
            @if($task->project)
                <div class="flex gap-2 bg-primary/10 border-b border-primary/10 rounded-t-2xl px-6 py-3">
                    <div class="flex items-center gap-2 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <div class="font-semibold text-primary">
                            {{ $task->project->title ?? 'N/A' }} / {{ $task->phase->title ?? 'N/A' }}
                            @if($task->parent_id)
                                / {{ $task->parentTask->title ?? 'N/A' }}
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- TASK TITLE --}}
            <div class="px-6 py-4 border-b border-muted-200">
                <h2 class="text-xl font-semibold text-main">{{ $task->title }}</h2>
            </div>

            {{-- METADATA --}}
            <div class="px-6 py-3 bg-muted-50 border-b border-muted-200 flex gap-6 text-sm text-muted-600">
                <span>{{ __('tasks.created_at') }}:
                    {{ $task->created_at ? $task->created_at->format('d/m/Y') : 'N/A' }}</span>
                <span>{{ __('tasks.updated_at') }}:
                    {{ $task->updated_at ? $task->updated_at->format('d/m/Y') : 'N/A' }}</span>
            </div>

            {{-- TWO-COLUMN LAYOUT --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 px-6 py-6">

                {{-- LEFT COLUMN --}}
                <div class="space-y-3">
                    {{-- Status --}}
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">{{ __('tasks.status') }}:</div>
                        <div class="flex-1">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $currentStatus['class'] }}">
                                {{ $currentStatus['label'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Priority --}}
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">{{ __('tasks.priority') }}:</div>
                        <div class="flex-1">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $currentPriority['class'] }}">
                                {{ $currentPriority['label'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Active --}}
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">{{ __('tasks.active') }}:</div>
                        <div class="flex-1 text-sm">
                            <span class="{{ $task->active ? 'text-green-600' : 'text-muted-500' }}">
                                {{ $task->active ? __('tasks.active_yes') : __('tasks.active_no') }}
                            </span>
                        </div>
                    </div>

                    {{-- Project Leader --}}
                    @if($task->project->staffUser)
                        <div class="flex">
                            <div class="w-36 text-sm font-semibold text-muted-600">{{ __('tasks.project_leader') }}:</div>
                            <div class="flex-1 text-sm">
                                <a href="mailto:{{ $task->project->staffUser->email }}"
                                    class="hover:text-primary hover:underline">{{ $task->project->staffUser->name }}</a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- RIGHT COLUMN --}}
                <div class="space-y-3 mt-3 lg:mt-0">
                    {{-- Start Date --}}
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">{{ __('tasks.start_date') }}:</div>
                        <div class="flex-1 text-sm">
                            {{ \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') }}
                        </div>
                    </div>

                    {{-- Due Date --}}
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">{{ __('tasks.due_date') }}:</div>
                        <div class="flex-1 text-sm {{ $overdue ? 'text-danger font-medium' : '' }}">
                            {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                            @if($overdue)
                                @php
                                    $daysOverdue = abs(\Carbon\Carbon::parse($task->due_date)->diffInDays(now()));
                                @endphp
                                <span class="text-danger">({{ __('tasks.overdue_by', ['days' => $daysOverdue]) }})</span>
                            @else
                                @php
                                    $daysRemaining = \Carbon\Carbon::parse($task->due_date)->diffInDays(now());
                                @endphp
                                @if($task->status !== 'completed' && $daysRemaining <= 7)
                                    <span class="text-amber-600">({{ __('tasks.due_in_days', ['days' => $daysRemaining]) }})</span>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Progress --}}
                    @if($task->percentage !== null)
                        <div class="flex">
                            <div class="w-36 text-sm font-semibold text-muted-600">% {{ __('tasks.done') }}:</div>
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-5 bg-muted-100 rounded overflow-hidden border border-muted-200">
                                        <div class="h-full bg-gradient-to-r from-green-400 to-green-500 transition-all duration-500 flex items-center justify-center"
                                            style="width: {{ $task->percentage }}%">
                                            @if($task->percentage > 15)
                                                <span class="text-[10px] font-semibold text-white">{{ $task->percentage }}%</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($task->percentage <= 15)
                                        <span
                                            class="text-xs font-semibold text-muted-600 min-w-[35px]">{{ $task->percentage }}%</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Estimated time --}}
                    @php
                        $startDate = \Carbon\Carbon::parse($task->start_date);
                        $dueDate = \Carbon\Carbon::parse($task->due_date);
                        $duration = $startDate->diffInDays($dueDate);
                    @endphp
                    <div class="flex">
                        <div class="w-36 text-sm font-semibold text-muted-600">{{ __('tasks.estimated_time') }}:</div>
                        <div class="flex-1 text-sm">
                            {{ $task->estimated_time ?? 0 }} {{ __('tasks.hours') }}
                        </div>
                    </div>

                    {{-- Score --}}
                    @if($task->score !== null)
                        <div class="flex">
                            <div class="w-36 text-sm font-semibold text-muted-600">{{ __('tasks.score') }}:</div>
                            <div class="flex-1 text-sm font-bold text-primary">
                                {{ $task->score }}/100
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- DESCRIPTION SECTION --}}
            <div class="px-6 py-4 border-t border-muted-200">
                <h3 class="text-sm font-semibold text-muted-700 mb-3">{{ __('tasks.description') }}</h3>
                <div class="text-sm text-muted-600 leading-relaxed bg-muted-50 rounded p-4 border border-muted-200">
                    @if($task->description)
                        {!! $task->description !!}
                    @else
                        <p class="text-muted-400 italic">{{ __('tasks.no_description') }}</p>
                    @endif
                </div>
            </div>

            {{-- ASSIGNED USERS SECTION --}}
            @if($task->assignedUsers->count() > 0)
                <div class="px-6 py-4 border-t border-muted-200">
                    <h3 class="text-sm font-semibold text-muted-700 mb-3">{{ __('tasks.team_members') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($task->assignedUsers as $user)
                            <div
                                class="flex items-center gap-3 p-3 bg-muted-50 rounded border border-muted-200 hover:border-primary/30 transition-colors">
                                <img src="{{ getUserAvatar($user) }}" alt="{{ $user->name }}"
                                    class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm">
                                <div class="flex-1">
                                    <p class="font-medium text-sm text-main">{{ $user->name }}</p>
                                    <a href="mailto:{{ $user->email }}"
                                        class="text-xs text-muted-500 hover:text-primary transition-colors">
                                        {{ $user->email }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Subtask --}}
            @if($task->parent_id === null)
                <div class="px-6 py-4 border-t border-muted-200">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-muted-700">
                            {{ __('tasks.subtasks') }}
                            <span class="text-muted-500 font-normal text-xs">
                                {{ $task->subTasks->count() }}
                                ({{ $task->subTasks->where('status', '!=', 'completed')->count() }} {{ __('tasks.open') }} —
                                {{ $task->subTasks->where('status', 'completed')->count() }} {{ __('tasks.closed') }})
                            </span>
                        </h3>
                        @if(auth()->user()->hasRole('admin') || auth()->user()->hasDepartmentRolePermission('task.create'))
                            <a href="{{ route('tasks.create', ['parent_id' => $task->id]) }}"
                                class="text-sm text-primary hover:underline">
                                {{ __('tasks.add') }}
                            </a>
                        @endif
                    </div>

                    @if($task->subTasks->count() > 0)
                        <div class="w-full overflow-x-auto rounded border border-muted-200">
                            <table class="min-w-[900px] w-full table-fixed">
                                <thead class="bg-muted-50 border-b border-muted-200">
                                    <tr>
                                        <th
                                            class="w-[33%] py-3 pl-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                            {{ __('tasks.task_name') }}</th>
                                        <th
                                            class="w-[15%] py-3 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                            {{ __('tasks.assignee') }}</th>
                                        <th
                                            class="w-[12%] py-3 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                            {{ __('tasks.status') }}</th>
                                        <th
                                            class="w-[12%] py-3 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                            {{ __('tasks.start_date') }}</th>
                                        <th
                                            class="w-[12%] py-3 pr-6 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                            {{ __('tasks.due_date') }}</th>
                                        <th
                                            class="w-[16%] py-3 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider whitespace-nowrap">
                                            {{ __('tasks.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-muted-100 bg-white">
                                    @foreach($task->subTasks as $subTask)
                                        @php
                                            $subStartDate = \Carbon\Carbon::parse($subTask->start_date);
                                            $subDueDate = \Carbon\Carbon::parse($subTask->due_date);
                                            $overdue = \Illuminate\Support\Carbon::parse($subTask->due_date)->isPast() && $subTask->status !== 'completed';

                                            $pillClass = match ($subTask->status) {
                                                'pending' => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                                                'completed' => 'bg-accent/10 text-accent ring-accent/20',
                                                'in_progress' => 'bg-secondary/10 text-secondary ring-secondary/20',
                                                default => 'bg-primary/10 text-primary ring-primary/20',
                                            };
                                        @endphp
                                        <tr class="hover:bg-canvas transition-colors">
                                            {{-- title --}}
                                            <td
                                                class="w-[33%] py-3 pl-6 text-sm font-medium text-main hover:text-primary hover:underline">
                                                <a href="{{ route('tasks.details', $subTask->id) }}">{{ $subTask->title }}</a>
                                            </td>

                                            {{-- assignee --}}
                                            <td class="w-[15%] py-3 px-3 text-center text-sm font-medium text-main">
                                                @forelse($subTask->assignedUsers as $user)
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
                                                        {{ __('tasks.' . $subTask->status) }}
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- start date --}}
                                            <td class="w-[11%] py-3 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                                {{ $subStartDate->format('d/m/Y') }}
                                            </td>

                                            {{-- due date --}}
                                            <td class="w-[11%] py-3 px-3 text-center text-sm text-muted-500 whitespace-nowrap">
                                                {{ $subDueDate->format('d/m/Y') }}
                                                @if($overdue)
                                                    <span class="text-red-500 ml-1">⚠</span>
                                                @endif
                                            </td>

                                            {{-- Progress Bar --}}
                                            <td class="w-[16%] py-3 px-3">
                                                <div class="h-5 w-full bg-muted-100 rounded overflow-hidden border border-muted-200">
                                                    @php
                                                        $percentage = $subTask->percentage ?? 0;
                                                        $progressColor = 'bg-gradient-to-r from-green-400 to-green-500';
                                                    @endphp
                                                    <div class="h-full {{ $progressColor }} transition-all duration-500"
                                                        style="width: {{ $percentage }}%">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-muted-400 italic">{{ __('tasks.no_subtasks') }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection