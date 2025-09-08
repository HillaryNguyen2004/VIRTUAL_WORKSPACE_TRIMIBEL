@props([
  'assignedTasks' => collect(),
])

@vite(['resources/utils/user_dashboard/task_dialog.js'])
@vite(['resources/utils/user_dashboard/show_task_description.js'])
@vite(['resources/utils/user_dashboard/update_status.js'])
<div class="hidden items-center justify-center fixed h-screen w-screen bg-black/20 z-50" id="task-dialog">
    <div class="flex flex-col gap-8 bg-[#FDFDFF] w-[300px] md:w-[600px] max-h-[400px] rounded-[20px] p-6 animate-fade-in-up [animation-delay:150ms]">
        <div class="flex justify-between">
            <p class="text-[20px]">{{ __('user_dashboard.assigned_projects') }}</p>
            <button id="close-task">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-[#5D3FD3]">
                    <path
                        d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z" />
                </svg>
            </button>
        </div>
        <div class="">
            <table class="w-full table-fixed">
                <thead class="text-sm text-[#D9D9D9] border-b border-[#D9D9D9]">
                    <tr>
                        <th scope="col" class="w-5/12 text-left font-medium py-2">
                            {{ __('user_dashboard.tasks') }}
                        </th>
                        <th scope="col" class="w-6/12 text-left font-medium py-2">
                            {{ __('user_dashboard.task_status') }}
                        </th>
                        <th scope="col" class="w-1/12 text-left font-medium py-2"></th>
                    </tr>
                </thead>

                <tbody id="task-tbody" class="divide-y divide-gray-100 text-sm">
                    @forelse($assignedTasks as $task)
                        @php
                            $statusClasses = [
                                'pending' => 'bg-[#FFF3CD] text-[#856404]',
                                'in_progress' => 'bg-[#F2FBDF] text-[#CBEA8E]',
                                'completed' => 'bg-[#D3FDE5] text-[#5AE194]',
                            ];

                            $cls = $statusClasses[$task->status];
                            $percent = $task->percentage ?? 0;
                        @endphp

                        <tr data-task-id="{{ $task->id }}" aria-expanded="false">
                            <td class="py-3 pr-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="break-all" title="{{ $task->title }}">{{ $task->title }}</span>
                                </div>
                            </td>

                            <td class="py-3 pr-2 relative">
                                <button class="status-btn flex items-center gap-3 w-fit px-2 rounded-2xl text-center {{ $cls }}"
                                    data-task-id="{{ $task->id }}" aria-haspopup="menu"
                                    aria-expanded="false" aria-controls="status-menu-{{ $task->id }}">
                                    <p>{{ __('user_dashboard.status_' . $task->status) }}</p>
                                    <p class="hidden {{ !($task->status == "in_progress") ? "md:hidden" : "md:inline" }}">
                                        {{ $percent ?? 0 }}%
                                    </p>
                                </button>
                                <!-- drop down status update -->
                                <div
                                    id="status-menu-{{ $task->id }}"
                                    class="status-menu hidden absolute left-0 mt-2 w-48 md:w-52 bg-[#FDFDFF] border border-gray-200 shadow-xl rounded-2xl p-4 z-50">
                                    <!-- In progress -->
                                    <div class="rounded-2xl">
                                        <div class="flex items-center justify-between">
                                            <div class="flex justify-between px-3 py-1 rounded-full bg-[#F2FBDF] text-[#CBEA8E] w-full"
                                                data-status="in_progress" role="menuitem">
                                                <p>{{ __('user_dashboard.status_in_progress') }}</p>
                                                <div class="flex items-center">
                                                    <p class="menu-pct">{{ $task->status === 'in_progress' ? $percent : 0 }}
                                                    </p>
                                                    <span>%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="range" min="0" max="99" step="1"
                                            value="{{ $task->status === 'in_progress' ? $percent : 0 }}"
                                            class="mt-3 w-full accent-[#CBEA8E] range">
                                        <div class="mt-2 text-right">
                                            <button type="button"
                                                class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200 transition">
                                                Apply
                                            </button>
                                        </div>
                                    </div>

                                    <hr class="mt-3">

                                    <!-- Done status -->
                                    <button type="button"
                                        class="mt-3 w-full text-left px-4 py-1 rounded-full bg-[#D3FDE5] text-[#5AE194] hover:bg-[#c5f8db] transition"
                                        data-status="completed" data-percentage="100" role="menuitem">
                                        {{ __('user_dashboard.status_completed') }}
                                    </button>
                                </div>
                            </td>

                            <td class="py-3 pr-2">
                                <button type="button" class="hover:bg-[#F1EFFC] rounded-full p-1 js-show-desc"
                                    aria-controls="desc-{{ $task->id }}" aria-expanded="false"
                                    title="{{ __('user_dashboard.view_details') ?? 'View details' }}">
                                    <!-- icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                        class="w-5 h-5 fill-[#5D3FD3]">
                                        <path
                                            d="M96 320C96 289.1 121.1 264 152 264C182.9 264 208 289.1 208 320C208 350.9 182.9 376 152 376C121.1 376 96 350.9 96 320zM264 320C264 289.1 289.1 264 320 264C350.9 264 376 289.1 376 320C376 350.9 350.9 376 320 376C289.1 376 264 350.9 264 320zM488 264C518.9 264 544 289.1 544 320C544 350.9 518.9 376 488 376C457.1 376 432 350.9 432 320C432 289.1 457.1 264 488 264z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <tr id="desc-{{ $task->id }}" class="desc-row hidden" role="region">
                            <td colspan="3" class="bg-[#F1EFFC]">
                                <div class="px-3 py-2 max-h-[120px] overflow-auto text-gray-700">
                                    {{ $task->description ?? __('user_dashboard.no_description') }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-6 text-center text-gray-400">
                                {{ __('user_dashboard.no_projects_assigned') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>