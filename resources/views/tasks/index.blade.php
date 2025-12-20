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

    {{-- TABLE --}}
    <div class="overflow-x-auto rounded-2xl border border-gray-300 shadow mt-6">
        <table class="w-full">
            <thead class="bg-gray-100 text-gray-500 uppercase text-sm">
                <tr>
                    <th class="py-3 pl-4 text-left">ID</th>
                    <th class="py-3 text-left">Project</th>
                    <th class="py-3 text-left">{{ __('tasks.task_name') }}</th>
                    <th class="py-3 text-left">{{ __('tasks.due_date') }}</th>
                    <th class="py-3 text-left">{{ __('tasks.status') }}</th>
                    <th class="py-3 text-left">{{ __('tasks.active') }}</th>
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
                        </span>
                    </td>
                </tr>

                @forelse($project->tasks as $task)
                    @php
                        $overdue = \Carbon\Carbon::parse($task->due_date)->isPast()
                                   && $task->status !== 'completed';
                    @endphp

                    <tr>
                        <td class="py-3 pl-4">{{ $task->task_id }}</td>
                        <td class="py-3">{{ $project->title }}</td>
                        <td class="py-3">{{ $task->title }}</td>

                        <td class="py-3">
                            {{ \Carbon\Carbon::parse($task->due_date)->toDateString() }}
                            @if($overdue)
                                <span class="text-red-500 ml-1">⚠</span>
                            @endif
                        </td>

                        <td class="py-3">
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

                        <td class="py-3">
                            <input type="checkbox" disabled {{ $task->active ? 'checked' : '' }}>
                        </td>

                        {{-- ACTIONS --}}
                        <td class="py-3 pr-4 text-right">
                            <div class="flex justify-end gap-2">

                                {{-- VIEW --}}
                                <button type="button"
                                    class="toggle-row"
                                    data-target="taskDetails{{ $task->task_id }}">
                                    👁
                                </button>

                                {{-- EDIT --}}
                                @can('task.edit')
                                    <a href="{{ route('tasks.edit', $task->task_id) }}">✏️</a>
                                @endcan

                                {{-- DELETE --}}
                                @can('task.delete')
                                    <form method="POST"
                                          action="{{ route('tasks.destroy', $task->task_id) }}"
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
                    <tr id="taskDetails{{ $task->task_id }}" class="hidden bg-gray-50">
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
                        <td colspan="7" class="py-4 text-center text-gray-400">
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
