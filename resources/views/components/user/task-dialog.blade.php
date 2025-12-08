@props([
  'assignedTasks' => collect(),
])

{{-- Load existing scripts --}}
@vite(['resources/js/user_dashboard/task_dialog.js'])
@vite(['resources/js/user_dashboard/show_task_description.js'])
@vite(['resources/js/user_dashboard/update_status.js'])

<script>
    window.updateStatusUrl = @json(route('tasks.updateStatus', ['task' => ':id']));
</script>

<div class="hidden items-center justify-center fixed h-screen w-screen bg-black/50 z-50" id="task-dialog">
    <div class="flex flex-col bg-white w-[95%] md:w-[900px] lg:w-[1100px] h-[85vh] rounded-2xl shadow-2xl animate-fade-in-up [animation-delay:150ms] overflow-hidden">
        
        {{-- Header --}}
        <div class="flex items-center justify-between px-8 py-5 border-b border-muted-200 bg-white z-20">
            <p class="text-xl font-bold text-main">{{ __('user_dashboard.assigned_projects') }}</p>
            <button id="close-task" class="p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Toolbar (Search) --}}
        <div class="px-8 py-3 bg-muted-50/50 border-b border-muted-200 flex items-center gap-4">
            <div class="relative w-full max-w-md group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-muted-400 group-focus-within:text-primary transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                {{-- Removed inline onkeyup --}}
                <input type="text" 
                       id="task-search-input" 
                       class="block w-full pl-10 pr-3 py-2 border border-muted-300 rounded-lg leading-5 bg-white placeholder-muted-400 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm transition-shadow" 
                       placeholder="{{ __('user_dashboard.task_search_placeholder') }}">
            </div>
        </div>

        {{-- Content --}}
        <div class="flex flex-col h-full">
            <div class="h-full overflow-auto custom-scrollbar">
                <table class="w-full text-left border-collapse" id="tasks-table">
                    <thead class="bg-muted-50 sticky top-0 z-10 shadow-sm text-xs font-semibold text-muted-500 uppercase tracking-wider">
                        <tr>
                            {{-- ID Header: Added data-sort-key="id" --}}
                            <th scope="col" class="w-[10%] px-6 py-4 cursor-pointer hover:bg-muted-100 transition-colors select-none group" data-sort-key="id">
                                <div class="flex items-center gap-1">
                                    ID
                                    <span class="sort-icon text-muted-300 group-hover:text-muted-500 transition-transform duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                                    </span>
                                </div>
                            </th>

                            {{-- Task Header: Added data-sort-key="title" --}}
                            <th scope="col" class="w-[38%] px-6 py-4 cursor-pointer hover:bg-muted-100 transition-colors select-none group" data-sort-key="title">
                                <div class="flex items-center gap-1">
                                    {{ __('user_dashboard.tasks') }}
                                    <span class="sort-icon text-muted-300 group-hover:text-muted-500 transition-transform duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                                    </span>
                                </div>
                            </th>

                            {{-- Status Header: Added data-sort-key="status" --}}
                            <th scope="col" class="w-[42%] px-6 py-4 cursor-pointer hover:bg-muted-100 transition-colors select-none group" data-sort-key="status">
                                <div class="flex items-center gap-1">
                                    {{ __('user_dashboard.task_status') }}
                                    <span class="sort-icon text-muted-300 group-hover:text-muted-500 transition-transform duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                                    </span>
                                </div>
                            </th>

                            <th scope="col" class="w-[10%] px-6 py-4"></th>
                        </tr>
                    </thead>

                    <tbody id="task-tbody" class="divide-y divide-muted-100 bg-white/0">
                        @forelse($assignedTasks as $task)
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-muted-100 text-muted-600 ring-1 ring-muted-200',
                                    'in_progress' => 'bg-secondary/10 text-secondary ring-1 ring-secondary/20',
                                    'completed' => 'bg-accent/10 text-accent ring-1 ring-accent/20',
                                ];

                                $cls = $statusClasses[$task->status] ?? $statusClasses['pending'];
                                $percent = $task->percentage ?? 0;
                            @endphp

                            <tr data-task-id="{{ $task->task_id }}" 
                                data-sort-id="{{ $task->task_id }}"
                                data-sort-title="{{ strtolower($task->title) }}"
                                data-sort-status="{{ $task->status }}"
                                aria-expanded="false" 
                                class="task-row group hover:bg-canvas transition-colors">
                                
                                <td class="px-6 py-5">
                                    <div class="flex items-center justify-start min-w-0">
                                        <span class="font-mono font-bold text-xs text-muted-400 bg-muted-100 px-2 py-1 rounded-md">#{{ $task->task_id }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="text-sm font-medium text-main block truncate max-w-[350px] task-title" title="{{ $task->title }}">{{ $task->title }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-5 relative">
                                    <button class="status-btn flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold transition-all {{ $cls }}"
                                        data-task-id="{{ $task->task_id }}" aria-haspopup="menu"
                                        aria-expanded="false" aria-controls="status-menu-{{ $task->task_id }}">
                                        <span class="uppercase tracking-wide">{{ __('user_dashboard.status_' . $task->status) }}</span>
                                        <span class="hidden {{ !($task->status == "in_progress") ? "md:hidden" : "md:inline" }} bg-white/50 px-1.5 rounded-md ml-1">
                                            {{ $percent ?? 0 }}%
                                        </span>
                                    </button>

                                    <div id="status-menu-{{ $task->task_id }}"
                                        class="status-menu hidden absolute left-0 mt-2 w-64 bg-white border border-muted-200 shadow-xl rounded-2xl p-3 z-[100] ring-1 ring-black/5 animate-fade-in-up">
                                        <button type="button"
                                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-bold uppercase text-muted-600 bg-muted-100 hover:bg-muted-200 transition-colors mb-3"
                                            data-status="pending" data-percentage="0" role="menuitem">
                                            <span>{{ __('user_dashboard.status_pending') }}</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </button>
                                        <div class="rounded-xl bg-secondary/5 mb-3 p-3 border border-secondary/10">
                                            <div class="flex items-center justify-between mb-2 text-secondary"
                                                data-status="in_progress" role="menuitem">
                                                <p class="text-xs font-bold uppercase">{{ __('user_dashboard.status_in_progress') }}</p>
                                                <div class="flex items-center font-bold text-xs">
                                                    <p class="menu-pct">{{ $task->status === 'in_progress' ? $percent : 0 }}</p>
                                                    <span>%</span>
                                                </div>
                                            </div>
                                            <input type="range" min="0" max="99" step="1"
                                                value="{{ $task->status === 'in_progress' ? $percent : 0 }}"
                                                class="w-full h-1.5 bg-secondary/20 rounded-lg appearance-none cursor-pointer accent-secondary range">
                                            <div class="mt-2 text-right">
                                                <button type="button"
                                                    class="text-xs font-bold text-secondary hover:underline px-2 py-1 rounded transition">
                                                    Apply
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button"
                                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-bold uppercase text-accent bg-accent/10 hover:bg-accent/20 transition-colors"
                                            data-status="completed" data-percentage="100" role="menuitem">
                                            <span>{{ __('user_dashboard.status_completed') }}</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </div>
                                </td>

                                <td class="px-6 py-5 text-right">
                                    <button type="button" class="text-muted-400 hover:text-primary hover:bg-primary/10 p-2 rounded-full transition-colors js-show-desc"
                                        aria-controls="desc-{{ $task->task_id }}" aria-expanded="false"
                                        title="{{ __('user_dashboard.view_details') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>

                            <tr id="desc-{{ $task->task_id }}" class="desc-row hidden transition-all" role="region">
                                <td colspan="4" class="bg-muted-50 p-0">
                                    <div class="px-8 py-6 border-l-4 border-primary">
                                        <div class="text-xs font-bold text-muted-500 uppercase tracking-wide mb-2">
                                            Task #{{ $task->task_id }} - {{ $task->title }}
                                        </div>
                                        <p class="text-base text-main leading-relaxed">
                                            {{ $task->description ?? __('user_dashboard.no_description') }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="no-results-row">
                                <td colspan="4" class="px-6 py-16 text-center text-muted-400">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="w-12 h-12 text-muted-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        <p class="text-lg">{{ __('user_dashboard.no_projects_assigned') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>