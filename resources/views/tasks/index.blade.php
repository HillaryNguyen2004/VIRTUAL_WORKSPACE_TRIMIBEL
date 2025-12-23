@extends('layout_dashboard')

@section('content')
    @vite(['resources/js/toggle_view.js'])

    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = auth()->user()->hasRole('admin') && Route::has('admin.dashboard')
            ? 'admin.dashboard'
            : 'user.dashboard';
    @endphp

    <x-action-layout :route="$dashRoute" :title="'profile.back_to_dashboard'">

        {{-- Header --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center w-full">
            <h2 class="font-medium text-[28px] md:text-[32px]">
                {{ __('tasks.all_tasks') }}
            </h2>

            @can('task.create')
                <a href="{{ route('tasks.create') }}"
                    class="flex items-center gap-1 bg-[#5D3FD3] text-white px-3 py-1 rounded-xl hover:opacity-95">
                    ➕ {{ __('staff_dashboard.new_task') }}
                </a>
            @endcan
        </div>

        {{-- Search & Filter Bar --}}
        <form class="mt-4 flex flex-wrap gap-2 animate-fade-in-up [animation-delay:150ms]" method="GET"
            action="{{ url()->current() }}">

            {{-- Search by Task Name --}}
            <input type="text" name="search" id="search" placeholder="{{ __('tasks.search_placeholder') }}"
                value="{{ request('search') }}"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">

            <select name="project_id"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                <option value="">{{ __('tasks.all_projects') ?? 'All Projects' }}</option>

                @foreach($projectOptions as $p)
                    <option value="{{ $p->id }}" {{ (string) request('project_id') === (string) $p->id ? 'selected' : '' }}>
                        {{ $p->title }}
                    </option>
                @endforeach
            </select>

            {{-- Sort: name asc/desc --}}
            <select name="sort_dir"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                <option value="">{{ __('template.default_sort') }}</option>
                <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>Name A → Z</option>
                <option value="desc" {{ request('sort_dir') === 'desc' ? 'selected' : '' }}>Name Z → A</option>
            </select>

            {{-- Task Start Date (from) --}}
            <input type="date" name="start_date" value="{{ request('start_date') }}"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">

            {{-- Task Due Date (to) --}}
            <input type="date" name="due_date" value="{{ request('due_date') }}"
                class="rounded-xl text-sm md:text-base border border-gray-300 px-4 py-2 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">

            <div class="flex gap-2">
                {{-- Filter button --}}
                <button type="submit" title="{{ __('tasks.filter') }}"
                    class="border px-3 py-2 rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                    </svg>
                </button>

                {{-- Reset button --}}
                <a href="{{ url()->current() }}" title="{{ __('tasks.reset') }}"
                    class="flex items-center justify-center border px-3 py-2 rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                    </svg>
                </a>
            </div>
        </form>

        {{-- TABLE --}}
        <div class="overflow-x-auto rounded-2xl border border-gray-300 shadow mt-6">
            <table class="w-full">
                <thead class="bg-gray-100 text-gray-500 uppercase text-sm">
                    <tr>
                        <th class="py-3 pl-4 text-left">ID</th>
                        <th class="py-3 text-center">Project</th>
                        <th class="py-3 text-center">{{ __('tasks.task_name') }}</th>
                        <th class="py-3 text-center">{{ __('tasks.status') }}</th>
                        <th class="py-3 text-center">{{ __('tasks.active') }}</th>
                        <th class="py-3 text-center">{{ __('tasks.start_date') }}</th>
                        <th class="py-3 text-center">{{ __('tasks.due_date') }}</th>
                        <th class="py-3 pr-4 text-right"></th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y">

                    @forelse($projects as $project)

                        {{-- PROJECT HEADER --}}
                        <tr class="bg-gray-50">
                            <td colspan="7" class="px-4 py-3 font-semibold text-gray-700">
                                📁 {{ $project->title }}
                                <span class="text-sm text-gray-400">
                                    — {{ $project->staff->name ?? 'Unassigned' }}
                                    <span class="ml-1">
                                        ({{ $project->tasks->count() }} {{ __('tasks.tasks') }})
                                    </span>
                                </span>
                            </td>
                        </tr>

                        @forelse($project->tasks as $task)
                            @php
                                $overdue = \Carbon\Carbon::parse($task->due_date)->isPast()
                                    && $task->status !== 'completed';
                            @endphp

                            <tr>
                                <td class="py-3 pl-4">{{ $task->id }}</td>
                                <td class="py-3 text-center">{{ $project->title }}</td>
                                <td class="py-3 text-center">{{ $task->title }}</td>

                                <td class="flex justify-center py-3">
                                    @if($task->status === 'pending')
                                        <x-status-pill bgColor="bg-gray-100" textColor="text-gray-500">
                                            {{ __('tasks.pending') }}
                                        </x-status-pill>
                                    @elseif($task->status === 'completed')
                                        <x-status-pill bgColor="bg-green-100" textColor="text-green-600">
                                            {{ __('tasks.completed') }}
                                        </x-status-pill>
                                    @else
                                        <x-status-pill bgColor="bg-yellow-100" textColor="text-yellow-700">
                                            {{ __('tasks.in_progress') }} ({{ $task->percentage ?? 0 }}%)
                                        </x-status-pill>
                                    @endif
                                </td>

                                <td class="py-3 text-center">
                                    <input type="checkbox" disabled {{ $task->active ? 'checked' : '' }}>
                                </td>
                                <td class="py-3 text-center">
                                    {{ \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') }}
                                </td>

                                <td class="py-3 text-center">
                                    {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                                    @if($overdue)
                                        <span class="text-red-500 ml-1">⚠</span>
                                    @endif
                                </td>

                                {{-- ACTIONS --}}
                                <td class="py-3 pr-4 text-right">
                                    <div class="flex justify-end gap-2">

                                        {{-- VIEW --}}
                                        <button type="button" class="toggle-row" data-target="taskDetails{{ $task->id }}">
                                            👁
                                        </button>

                                        {{-- EDIT --}}
                                        @can('task.edit')
                                            <a href="{{ route('tasks.edit', $task->id) }}">✏️</a>
                                        @endcan

                                        {{-- DELETE --}}
                                        @can('task.delete')
                                            <form method="POST" action="{{ route('tasks.destroy', $task->id) }}"
                                                onsubmit="return confirm('{{ __('tasks.confirm_delete') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button>🗑</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>

                            {{-- DETAILS --}}
                            <tr id="taskDetails{{ $task->id }}" class="hidden bg-gray-50">
                                <td colspan="7" class="p-4">

                                    <p class="text-gray-600">
                                        <strong>{{ __('tasks.description') }}:</strong>
                                        {{ $task->description ?? __('tasks.no_description') }}
                                    </p>

                                    <hr class="my-3">

                                    <strong>{{ __('tasks.assigned_users') }}:</strong>
                                    <ul class="mt-2">
                                        @forelse($task->assignedUsers as $u)
                                            <li>{{ $u->name }} ({{ $u->email }})</li>
                                        @empty
                                            <li class="text-gray-400">{{ __('tasks.no_users') }}</li>
                                        @endforelse
                                    </ul>

                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="9" class="py-4 text-center text-gray-400">
                                    {{ __('tasks.no_tasks') }}
                                </td>
                            </tr>
                        @endforelse

                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400">
                                {{ __('tasks.no_projects') }}
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

    </x-action-layout>
@endsection