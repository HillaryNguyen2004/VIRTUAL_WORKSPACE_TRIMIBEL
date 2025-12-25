@extends('layout_dashboard')
@section('title', __('task_edit.task_name_label'))

@section('content')
@php
    // Preserve existing role logic for the back button
    if (auth()->user()->hasRole('admin')) {
        $dashRoute = 'tasks.index';        
    } else {
        $dashRoute = 'tasks.staff.index'; 
    }
@endphp

{{-- Main Container --}}
<div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

    {{-- Header Section --}}
    <div class="flex gap-4 flex-row items-center w-full">
        @include('components.back-btn')
        <div>
            <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('task_edit.title') }}</h2>
            <p class="text-muted-500 text-sm mt-1">{{ __('task_edit.subtitle') }}</p>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="w-full bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 relative overflow-hidden animate-fade-in-up">
        
        {{-- Decorative background element --}}
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-primary/10 rounded-full blur-2xl opacity-50 pointer-events-none"></div>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="flex items-center gap-3 bg-accent/10 border border-accent/20 text-accent p-4 rounded-xl mb-6">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="bg-danger/10 border border-danger/20 text-danger p-4 rounded-xl mb-6">
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tasks.update', $task) }}" class="relative z-10">
            @csrf
            @method('PUT')

            {{-- Reusable Classes for consistency --}}
            @php
                $labelClass = "block text-sm font-semibold text-main mb-2";
                $inputClass = "block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all";
            @endphp

            <div class="grid grid-cols-1 @4xl:grid-cols-2 gap-6">
                
                {{-- Left Column --}}
                <div class="flex flex-col gap-5">
                    
                    {{-- Title --}}
                    <div>
                        <label class="{{ $labelClass }}">{{ __('task_edit.task_name_label') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $task->title) }}" class="{{ $inputClass }}" required>
                    </div>

                    {{-- Project --}}
                    <div>
                        <label class="{{ $labelClass }}">{{ __('task_edit.project_label') }} <span class="text-danger">*</span></label>
                        <div class="relative">
                            <select name="project_id" class="{{ $inputClass }} appearance-none" required>
                                <option value="" class="text-muted-400">Select project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" @selected(old('project_id', $task->project_id) == $project->id)>
                                        {{ $project->name ?? $project->title }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>

                    {{-- Assignees --}}
                    <div>
                        <label class="{{ $labelClass }}">{{ __('task_edit.assignee_label') }} <span class="text-danger">*</span></label>
                        <select name="assignees[]" multiple class="{{ $inputClass }} h-32">
                            @php
                                $assignedIds = old('assignees', $task->assignedUsers->pluck('id')->toArray());
                            @endphp
                            @foreach($assignees as $user)
                                <option value="{{ $user->id }}" @selected(in_array($user->id, $assignedIds)) class="py-1">
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-400 mt-1.5 ml-1">
                            {{ __('task_edit.assignee_tip') }}
                        </p>
                    </div>

                    {{-- Status & Active Row --}}
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Status --}}
                        <div>
                            <label class="{{ $labelClass }}">{{ __('task_edit.status_label') }}</label>
                            <div class="relative">
                                <select name="status" class="{{ $inputClass }} appearance-none">
                                    <option value="pending" @selected($task->status === 'pending')>
                                        {{ __('task_edit.status_pending') }}
                                    </option>
                                    <option value="in_progress" @selected($task->status === 'in_progress')>
                                        {{ __('task_edit.status_in_progress') }}
                                    </option>
                                    <option value="completed" @selected($task->status === 'completed')>
                                        {{ __('task_edit.status_completed') }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        {{-- Active Checkbox --}}
                        <div class="flex items-center h-full pt-6">
                            <label class="inline-flex items-center cursor-pointer group">
                                <input type="checkbox" name="active" value="1" id="active" 
                                    class="rounded border-muted-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary/20 focus:ring-opacity-50 w-5 h-5 transition-all"
                                    {{ old('active', $task->active) ? 'checked' : '' }}>
                                <span class="ml-3 font-semibold text-main group-hover:text-primary transition-colors">
                                    {{ __('task_edit.active_label') }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Right Column --}}
                <div class="flex flex-col gap-5">
                    
                    {{-- Dates Row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        {{-- Start date --}}
                        <div>
                            <label class="{{ $labelClass }}">{{ __('task_edit.start_date_label') }} <span class="text-danger">*</span></label>
                            {{-- Note: Fixed bug in original code where value was old('due_date') instead of start_date --}}
                            <input type="date" name="start_date" value="{{ old('start_date', $task->start_date) }}" class="{{ $inputClass }}" required>
                        </div>

                        {{-- Due date --}}
                        <div>
                            <label class="{{ $labelClass }}">{{ __('task_edit.due_date_label') }} <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" value="{{ old('due_date', $task->due_date) }}" class="{{ $inputClass }}" required>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="flex-1 flex flex-col">
                        <label class="{{ $labelClass }}">{{ __('task_edit.description_label') }}</label>
                        <textarea name="description" class="{{ $inputClass }} flex-1 min-h-[150px] resize-none">{{ old('description', $task->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Footer / Actions --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-muted-200">
                <a href="#" onclick="history.back(); return false;" class="px-6 py-3 rounded-xl text-muted-500 font-medium hover:bg-muted-100 transition-colors">
                    {{ __('task_edit.cancel_button') }}
                </a>
                
                <button type="submit" class="group flex items-center justify-center gap-2 rounded-xl bg-primary px-8 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    {{ __('task_edit.save_button') }}
                </button>
            </div>

        </form>
    </div>
</div>
@endsection