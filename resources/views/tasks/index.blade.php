@extends('layout_dashboard')

@section('content')
    @vite(['resources/utils/toggle_view.js'])
    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }
    @endphp
    <x-action-layout :route="$dashRoute" :title="'profile.back_to_dashboard'">
        <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center w-full">
            <h2 class="font-medium text-[28px] md:text-[32px]">{{ __('tasks.upcoming_tasks') }}</h2>
            @can('task.create')
                <a href="{{ route('tasks.create') }}"
                    class="flex items-center justify-center gap-1 w-fit h-fit bg-[#5D3FD3] text-white px-3 py-1 rounded-xl hover:opacity-95">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h4 fill-white">
                        <path
                            d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                    </svg>
                    {{ __('staff_dashboard.new_task') }}
                </a>
            @endcan
        </div>

        {{-- Search & Filter Bar --}}
        <form class="flex flex-wrap gap-2 animate-fade-in-up [animation-delay:150ms]" method="GET">
            <input type="text" name="search" id="search" placeholder="{{ __('tasks.search_placeholder') }}"
                value="{{ request('search') }}"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
            <input type="date" name="due_date"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                value="{{ request('due_date') }}">
            <select name="assigned_user_id"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                <option value="">{{ __('admin_task.filter_by_assignee') }}</option>
                @foreach($allUsers as $user)
                    <option value="{{ $user->id }}" {{ request('assigned_user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            <select name="sort_by"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                <option value="">{{ __('admin_task.sort_by') }}</option>
                <option value="title" {{ request('sort_by') == 'title' ? 'selected' : '' }}>
                    {{ __('admin_task.sort_task_name') }}
                </option>
                <option value="due_date" {{ request('sort_by') == 'due_date' ? 'selected' : '' }}>
                    {{ __('admin_task.sort_due_date') }}
                </option>
            </select>
            <div class="flex gap-2">
                <!-- filter -->
                <button type="submit" title="{{ __('tasks.filter') }}"
                    class="border px-3 py-2 rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                    </svg>
                </button>
                <!-- reset -->
                <a href="{{ route('tasks.index') }}" title="{{ __('tasks.reset') }}"
                    class="flex items-center justify-center border px-3 py-2 rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                    </svg>
                </a>
            </div>
        </form>
        <div
            class="overflow-x-auto rounded-2xl border border-gray-300 shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] animate-fade-in-up [animation-delay:200ms]">
            <table class="w-full">
                <thead class="bg-gray-100 text-gray-500 w uppercase tracking-wide text-sm">
                    <tr>
                        <th class="py-3 pl-4 pr-3 text-left font-medium">ID</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('tasks.task_name') }}</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('tasks.due_date') }}</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('tasks.status') }}</th>
                        <th class="py-3 px-3 text-left font-medium">{{ __('tasks.active') }}</th>
                        <th class="py-3 pr-4 pl-3 text-right font-medium"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-[#FDFDFF] text-sm">
                    @forelse($tasks as $task)
                        @php
                            $overdue = \Illuminate\Support\Carbon::parse($task->due_date)->isPast() && $task->status !== 'completed';
                            $status = $task->status;
                        @endphp

                        <tr>
                            {{-- Name --}}
                            <td class="py-3 pl-4 pr-3">
                                <div class="max-w-xs truncate" title="{{ $task->task_id }}">{{ $task->task_id }}</div>
                            </td>

                            {{-- Name --}}
                            <td class="py-3 px-3">
                                <div class="max-w-xs truncate" title="{{ $task->title }}">{{ $task->title }}</div>
                            </td>

                            {{-- Due date + overdue --}}
                            <td class="py-3 px-3 whitespace-nowrap">
                                <p class="flex gap-2">
                                    {{ \Illuminate\Support\Carbon::parse($task->due_date)->toDateString() }}
                                    @if($overdue)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                            class="w-5 h-5 fill-red-500 align-middle">
                                            <path
                                                d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z" />
                                        </svg>
                                    @endif
                                </p>
                            </td>

                            {{-- Status pill --}}
                            <td class="py-3 px-3">
                                @if ($status === 'pending')
                                    <x-status-pill textColor="text-gray-500"
                                        bgColor="bg-gray-100">{{ __('tasks.pending') }}</x-status-pill>
                                @elseif ($status === 'completed')
                                    <x-status-pill textColor="text-[#5AE194]"
                                        bgColor="bg-[#D3FDE5]">{{ __('tasks.completed') }}</x-status-pill>
                                @else
                                    <x-status-pill textColor="text-[#CBEA8E]" bgColor="bg-[#F2FBDF]">
                                        <p>{{ __('tasks.in_progress') }}</p>
                                        <p>{{ $task->percentage ?? 0 }}%</p>
                                    </x-status-pill>
                                @endif
                            </td>

                            {{-- Active --}}
                            <td class="py-3 px-3">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300" {{ $task->active ? 'checked' : '' }} disabled aria-checked="{{ $task->active ? 'true' : 'false' }}">
                                <!-- <span
                                                                            class="text-gray-900">{{ $task->active ? __('tasks.active_yes') : __('tasks.active_no') }}</span> -->
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 pr-4 pl-3 text-right">
                                <div class="flex items-center justify-end gap-1 h-fit">
                                    {{-- View: toggle details row --}}
                                    <button type="button" class="toggle-row p-1.5 rounded-full hover:bg-gray-100"
                                        id="view-btn-{{ $task->task_id }}" data-target="taskDetails{{ $task->task_id }}"
                                        aria-controls="taskDetails{{ $task->task_id }}" aria-expanded="false"
                                        title="{{ __('tasks.view') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                            class="w-5 h-5 fill-[#5D3FD3]">
                                            <path
                                                d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z" />
                                        </svg>
                                    </button>

                                    {{-- Edit --}}
                                    @can('task.edit')
                                        <a href="{{ route('tasks.edit', $task->task_id) }}"
                                            class="p-1.5 rounded-full hover:bg-gray-100 transition" title="{{ __('tasks.edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                                class="w-5 h-5 fill-green-500">
                                                <path
                                                    d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7zM160 128C107 128 64 171 64 224L64 480C64 533 107 576 160 576L416 576C469 576 512 533 512 480L512 384C512 366.3 497.7 352 480 352C462.3 352 448 366.3 448 384L448 480C448 497.7 433.7 512 416 512L160 512C142.3 512 128 497.7 128 480L128 224C128 206.3 142.3 192 160 192L256 192C273.7 192 288 177.7 288 160C288 142.3 273.7 128 256 128L160 128z" />
                                            </svg>
                                        </a>
                                    @endcan

                                    {{-- Delete --}}
                                    @can('task.delete')
                                        <form action="{{ route('tasks.destroy', $task->task_id) }}" method="POST"
                                            onsubmit="return confirm('{{ __('tasks.confirm_delete') }}');" class="">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-full hover:bg-gray-100 transition"
                                                title="{{ __('tasks.delete') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                                    class="w-5 h-5 fill-red-600">
                                                    <path
                                                        d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>

                        {{-- Details row (toggle) --}}
                        <tr id="taskDetails{{ $task->task_id }}" class="detail-row hidden">
                            <td colspan="6" class="bg-gray-50 p-4">
                                <div class="grid gap-4">
                                    <div class="h-fit max-h-[200px] overflow-auto">
                                        <strong class="text-gray-600">{{ __('tasks.description') }}:</strong>
                                        <div class="mt-1 text-gray-400 break-all">
                                            {{ $task->description ?? __('tasks.no_description') }}
                                        </div>
                                    </div>
                                    <div>
                                        <strong class="text-gray-600">{{ __('tasks.tags') }}:</strong>
                                        <div class="mt-1 flex flex-wrap gap-2">
                                            @if(!empty($task->tags))
                                                @foreach(json_decode($task->tags, true) as $tag)
                                                    <span
                                                        class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs">{{ $tag }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-gray-400">{{ __('tasks.no_tags') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4 border-gray-200">

                                <strong class="text-gray-600">{{ __('tasks.assigned_users') }}:</strong>
                                <ul class="mt-2 grid sm:grid-cols-2 gap-1 text-gray-800">
                                    @forelse($task->assignedUsers as $user)
                                        <li>{{ $user->name }} <span class="text-gray-400">({{ $user->email }})</span></li>
                                    @empty
                                        <li class="text-gray-400">{{ __('tasks.no_users') }}</li>
                                    @endforelse
                                </ul>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400">
                                {{ __('tasks.no_tasks') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($tasks->hasPages())
                <div class="my-4 flex justify-center w-full">
                    {{ $tasks->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        </div>
    </x-action-layout>
@endsection