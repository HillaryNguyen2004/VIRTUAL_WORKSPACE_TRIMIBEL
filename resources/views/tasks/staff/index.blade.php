@extends('layout_dashboard')

@section('content')
    @vite(['resources/js/toggle_view.js'])

    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = auth()->user()->hasRole('staff')
            ? 'staff.dashboard'
            : 'user.dashboard';
    @endphp

    @role('staff')
    <x-action-layout :route="$dashRoute" :title="'profile.back_to_dashboard'">

        {{-- HEADER --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center w-full">
            <h2 class="font-medium text-[28px] md:text-[32px]">
                {{ __('tasks.upcoming_tasks') }}
            </h2>

            @can('task.create')
                <a href="{{ route('tasks.create') }}"
                    class="flex items-center gap-2 bg-[#5D3FD3] text-white px-4 py-2 rounded-xl hover:opacity-95">
                    <span>＋</span> {{ __('staff_dashboard.new_task') }}
                </a>
            @endcan
        </div>

        {{-- TABLE --}}
        <div class="mt-6 overflow-x-auto rounded-2xl border border-gray-300 shadow-lg">
            <table class="w-full">
                <thead class="bg-gray-100 text-gray-500 text-sm uppercase">
                    <tr>
                        <th class="py-3 pl-4 text-left">{{ __('tasks.task_name') }}</th>
                        <th class="py-3 px-3 text-center">{{ __('tasks.status') }}</th>
                        <th class="py-3 px-3 text-center">{{ __('tasks.active') }}</th>
                        <th class="py-3 text-center">{{ __('tasks.start_date') }}</th>
                        <th class="py-3 text-center">{{ __('tasks.due_date') }}</th>
                        <th class="py-3 pr-4 text-right"></th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y">

                    {{-- ================= PROJECT LOOP ================= --}}
                    @forelse($projects as $project)

                        {{-- PROJECT ROW --}}
                        <tr class="bg-indigo-50">
                            <td colspan="6" class="py-3 px-4 font-semibold flex justify-between items-center">
                                <div>
                                    📁 {{ $project->title }}
                                    <span class="ml-2 text-xs text-gray-500">
                                        ({{ $project->tasks->count() }} {{ __('tasks.tasks') }})
                                    </span>
                                </div>

                                <button class="toggle-row text-indigo-600 hover:underline"
                                    data-target="project{{ $project->id }}">
                                    {{ __('tasks.view') }}
                                </button>
                            </td>
                        </tr>

                        {{-- PROJECT TASKS --}}
                        <tr id="project{{ $project->id }}" class="hidden">
                            <td colspan="6" class="p-0">

                                <table class="w-full">
                                    <tbody class="divide-y bg-[#FDFDFF]">

                                        @forelse($project->tasks as $task)
                                            @php
                                                $overdue = \Carbon\Carbon::parse($task->due_date)->isPast()
                                                    && $task->status !== 'completed';
                                            @endphp

                                            {{-- TASK ROW --}}
                                            <tr>
                                                <td class="py-3 pl-8">
                                                    {{ $task->title }}
                                                </td>

                                                <td class="flex justify-center py-3 px-3">
                                                    <x-status-pill textColor="text-gray-600" bgColor="bg-gray-100">
                                                        {{ __('tasks.' . $task->status) }}
                                                    </x-status-pill>
                                                </td>

                                                <td class="py-3 px-3 text-center">
                                                    <input type="checkbox" disabled {{ $task->active ? 'checked' : '' }}>
                                                </td>

                                                <td class="py-3 px-3text-center">
                                                    {{ \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') }}
                                                </td>

                                                <td class="py-3 px-3 text-center">
                                                    {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                                                    @if($overdue)
                                                        <span class="text-red-500 ml-1">●</span>
                                                    @endif
                                                </td>

                                                <td class="py-3 pr-4 text-right">
                                                    <div class="flex justify-end gap-1">

                                                        {{-- VIEW DETAILS --}}
                                                        <button class="toggle-row p-1 rounded hover:bg-gray-100"
                                                            data-target="taskDetails{{ $task->id }}">
                                                            👁
                                                        </button>

                                                        @can('task.edit')
                                                            <a href="{{ route('tasks.edit', $task->id) }}"
                                                                class="p-1 rounded hover:bg-gray-100">
                                                                ✏️
                                                            </a>
                                                        @endcan

                                                        @can('task.delete')
                                                            <form method="POST" action="{{ route('tasks.destroy', $task->id) }}"
                                                                onsubmit="return confirm('{{ __('tasks.confirm_delete') }}')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="p-1 rounded hover:bg-gray-100">
                                                                    🗑
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- TASK DETAILS --}}
                                            <tr id="taskDetails{{ $task->id }}" class="hidden bg-gray-50">
                                                <td colspan="5" class="p-4 text-sm">

                                                    <strong>{{ __('tasks.description') }}:</strong>
                                                    <p class="text-gray-500 mt-1">
                                                        {{ $task->description ?? __('tasks.no_description') }}
                                                    </p>

                                                    <hr class="my-3">

                                                    <strong>{{ __('tasks.assigned_users') }}:</strong>
                                                    <ul class="mt-1">
                                                        @forelse($task->assignedUsers as $user)
                                                            <li>• {{ $user->name }} ({{ $user->email }})</li>
                                                        @empty
                                                            <li class="text-gray-400">
                                                                {{ __('tasks.no_users') }}
                                                            </li>
                                                        @endforelse
                                                    </ul>

                                                </td>
                                            </tr>

                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-4 text-center text-gray-400">
                                                    {{ __('tasks.no_tasks') }}
                                                </td>
                                            </tr>
                                        @endforelse

                                    </tbody>
                                </table>

                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400">
                                {{ __('projects.no_projects') }}
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

    </x-action-layout>
    @endrole
@endsection