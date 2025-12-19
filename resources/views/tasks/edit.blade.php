@extends('layout_dashboard')

@section('content')
@php
    if (auth()->user()->hasRole('admin')) {
        $dashRoute = 'tasks.index';        
    } else {
        $dashRoute = 'tasks.staff.index'; 
    }
@endphp

<x-action-layout :route="$dashRoute" :title="__('task_edit.back_to_task')">

    {{-- Success --}}
    @if(session('success'))
        <div class="bg-[#D6F5E3] text-[#5AE194] border border-[#5AE194]
                    text-lg text-center px-3 py-2 rounded-2xl w-full mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- Errors --}}
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Title --}}
        <div>
            <label class="block font-semibold mb-1">
                {{ __('task_edit.task_name_label') }} *
            </label>
            <input
                type="text"
                name="title"
                value="{{ old('title', $task->title) }}"
                class="w-full rounded-xl border px-3 py-2"
                required
            >
        </div>

        {{-- Project --}}
        <div>
            <label class="block font-semibold mb-1">
                {{ __('task_edit.project_label') }} *
            </label>
            <select
                name="project_id"
                class="w-full rounded-xl border px-3 py-2"
                required
            >
                <option value="">Select project</option>
                @foreach($projects as $project)
                    <option
                        value="{{ $project->id }}"
                        @selected(old('project_id', $task->project_id) == $project->id)
                    >
                        {{ $project->name ?? $project->title }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Assignees --}}
        <div>
            <label class="block font-semibold mb-1">
                {{ __('task_edit.assignee_label') }} *
            </label>

            <select
                name="assignees[]"
                multiple
                class="w-full rounded-xl border px-3 py-2 h-40"
            >
                @php
                    $assignedIds = old(
                        'assignees',
                        $task->assignedUsers->pluck('id')->toArray()
                    );
                @endphp

                @foreach($assignees as $user)
                    <option
                        value="{{ $user->id }}"
                        @selected(in_array($user->id, $assignedIds))
                    >
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>

            <p class="text-sm text-gray-500 mt-1">
                Hold <strong>Ctrl</strong> (Windows) or <strong>Cmd</strong> (Mac) to select multiple users
            </p>
        </div>

        {{-- Due date --}}
        <div>
            <label class="block font-semibold mb-1">
                {{ __('task_edit.due_date_label') }} *
            </label>
            <input
                type="date"
                name="due_date"
                value="{{ old('due_date', $task->due_date) }}"
                class="w-full rounded-xl border px-3 py-2"
                required
            >
        </div>

        {{-- Description --}}
        <div>
            <label class="block font-semibold mb-1">
                {{ __('task_edit.description_label') }}
            </label>
            <textarea
                name="description"
                rows="4"
                class="w-full rounded-xl border px-3 py-2"
            >{{ old('description', $task->description) }}</textarea>
        </div>

        {{-- Status --}}
        <div>
            <label class="block font-semibold mb-1">
                {{ __('task_edit.status_label') }}
            </label>
            <select
                name="status"
                class="w-full rounded-xl border px-3 py-2"
            >
                <option value="pending"     @selected($task->status === 'pending')>
                    {{ __('task_edit.status_pending') }}
                </option>
                <option value="in_progress" @selected($task->status === 'in_progress')>
                    {{ __('task_edit.status_in_progress') }}
                </option>
                <option value="completed"   @selected($task->status === 'completed')>
                    {{ __('task_edit.status_completed') }}
                </option>
            </select>
        </div>

        {{-- Active --}}
        <div class="flex items-center gap-2">
            <input
                type="checkbox"
                name="active"
                value="1"
                id="active"
                class="rounded"
                {{ old('active', $task->active) ? 'checked' : '' }}
            >
            <label for="active" class="font-semibold">
                {{ __('task_edit.active_label') }}
            </label>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 pt-4">
            <a href="{{ route($dashRoute) }}"
               class="px-4 py-2 rounded-xl border">
                {{ __('task_edit.cancel_button') }}
            </a>

            <button
                type="submit"
                class="px-6 py-2 rounded-xl text-white bg-blue-600 hover:bg-blue-700"
            >
                {{ __('task_edit.save_button') }}
            </button>
        </div>

    </form>
</x-action-layout>
@endsection
