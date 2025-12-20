@extends('layout_dashboard')

@section('content')

    @php
        if (auth()->user()->hasRole('admin')) {
            $dashRoute = 'tasks.index';
        } else {
            $dashRoute = 'tasks.staff.index';
        }
    @endphp

    <x-action-layout :route="$dashRoute" title="Create Task">

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('tasks.store') }}">
            @csrf

            {{-- Task title --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">Title</label>
                <input type="text" name="title" class="w-full border rounded-lg px-3 py-2" value="{{ old('title') }}"
                    required>
            </div>

            {{-- Description --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">Description</label>
                <textarea name="description" class="w-full border rounded-lg px-3 py-2">{{ old('description') }}</textarea>
            </div>

            {{-- Project --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">
                    Project <span class="text-red-500">*</span>
                </label>
                <select name="project_id" class="w-full border rounded-lg px-3 py-2" required>
                    <option value="">-- Select Project --</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>
                            {{ $project->title ?? $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Assignees --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">
                    Assign Team Members
                </label>
                <select name="assignees[]" class="w-full border rounded-lg px-3 py-2" multiple required>
                    @foreach($assignees as $user)
                        <option value="{{ $user->id }}" @selected(collect(old('assignees'))->contains($user->id))>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Start date --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">Start Date</label>
                <input type="date" name="start_date" class="w-full border rounded-lg px-3 py-2" value="{{ old('start_date') }}">
            </div>

            {{-- Due date --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">Due Date</label>
                <input type="date" name="due_date" class="w-full border rounded-lg px-3 py-2" value="{{ old('due_date') }}">
            </div>

            <button class="bg-blue-600 text-white px-6 py-2 rounded-lg">
                Create Task
            </button>

        </form>
    </x-action-layout>
@endsection