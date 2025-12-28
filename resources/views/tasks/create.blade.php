@extends('layout_dashboard')
@section('title', 'Create Task')

@section('content')
    {{-- Main Container matching userdashboard.blade.php --}}
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex gap-4 flex-row items-center w-full">
            @include('components.back-btn')
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('task_create.title') }}</h2>
                <p class="text-muted-500 text-sm mt-1">{{ __('task_create.subtitle') }}</p>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="w-full bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 relative overflow-hidden animate-fade-in-up">
            
            {{-- Decorative background element --}}
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-primary/10 rounded-full blur-2xl opacity-50 pointer-events-none"></div>

            @if(session('success'))
                <div class="flex items-center gap-3 bg-accent/10 border border-accent/20 text-accent p-4 rounded-xl mb-6">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('tasks.store') }}" class="relative z-10">
                @csrf

                {{-- Reusable Classes --}}
                @php
                    $labelClass = "block text-sm font-semibold text-main mb-2";
                    $inputClass = "block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all";
                @endphp

                <div id="tasks-container">
                    {{-- Task Block Template --}}
                    <div class="task-block border border-muted-200 rounded-xl p-4 mb-4 relative">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-main">Task 1</h3>
                            <button type="button" class="remove-task text-danger hover:text-danger-hover" style="display: none;">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 @4xl:grid-cols-2 gap-6">
                            
                            {{-- Left Column --}}
                            <div class="flex flex-col gap-5">
                                {{-- Task title --}}
                                <div>
                                    <label class="{{ $labelClass }}">{{ __('task_create.task_name_label') }}</label>
                                    <input type="text" name="tasks[0][title]" class="{{ $inputClass }}" 
                                        placeholder="{{ __('task_create.task_name_placeholder') }}"
                                        value="{{ old('tasks.0.title') }}" required>
                                </div>

                                {{-- Project --}}
                                <div>
                                    <label class="{{ $labelClass }}">
                                        {{ __('task_create.project_label') }} <span class="text-danger">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="tasks[0][project_id]" class="{{ $inputClass }} appearance-none" required>
                                            <option value="" class="text-muted-400">-- {{ __('task_create.select_project') }} --</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}" @selected(old('tasks.0.project_id') == $project->id)>
                                                    {{ $project->title ?? $project->name }}
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
                                    <label class="{{ $labelClass }}">
                                        {{ __('task_create.assignee_label') }}
                                    </label>
                                    <select name="tasks[0][assignees][]" class="{{ $inputClass }} h-32" multiple required>
                                        @foreach($assignees as $user)
                                            <option value="{{ $user->id }}" @selected(collect(old('tasks.0.assignees'))->contains($user->id)) class="py-1">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-muted-400 mt-1.5 ml-1">{{ __('task_create.assignee_tip') }}</p>
                                </div>
                            </div>

                            {{-- Right Column --}}
                            <div class="flex flex-col gap-5">
                                {{-- Description --}}
                                <div class="h-full">
                                    <label class="{{ $labelClass }}">{{ __('task_create.description_label') }}</label>
                                    <textarea name="tasks[0][description]" class="{{ $inputClass }} h-full min-h-[150px] resize-none" 
                                        placeholder="{{ __('task_create.description_placeholder') }}">{{ old('tasks.0.description') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                            {{-- Start date --}}
                            <div>
                                <label class="{{ $labelClass }}">{{ __('task_create.start_date_label') }}</label>
                                <input type="date" name="tasks[0][start_date]" class="{{ $inputClass }}" value="{{ old('tasks.0.start_date') }}" required>
                            </div>

                            {{-- Due date --}}
                            <div>
                                <label class="{{ $labelClass }}">{{ __('task_create.due_date_label') }}</label>
                                <input type="date" name="tasks[0][due_date]" class="{{ $inputClass }}" value="{{ old('tasks.0.due_date') }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Add Task Button --}}
                <div class="flex justify-center mb-6">
                    <button type="button" id="add-task" class="flex items-center gap-2 px-4 py-2 bg-accent text-white rounded-xl hover:bg-accent-hover transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Another Task
                    </button>
                </div>

                {{-- Footer / Submit --}}
                <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-muted-200">
                    <a href="#" onclick="history.back(); return false;" class="px-6 py-3 rounded-xl text-muted-500 font-medium hover:bg-muted-100 transition-colors">
                        {{ __('task_create.cancel_button') }}
                    </a>
                    <button type="submit" class="group flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                        <!-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> -->
                        {{ __('task_create.save_button') }}
                    </button>
                </div>

            </form>

            {{-- JavaScript for dynamic tasks --}}
            <script>
                let taskIndex = 1;

                document.getElementById('add-task').addEventListener('click', function() {
                    const container = document.getElementById('tasks-container');
                    const taskBlocks = container.querySelectorAll('.task-block');
                    const newIndex = taskBlocks.length;

                    // Clone the first task block
                    const template = taskBlocks[0].cloneNode(true);
                    
                    // Update the title
                    template.querySelector('h3').textContent = `Task ${newIndex + 1}`;
                    
                    // Update input names
                    const inputs = template.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        if (input.name) {
                            input.name = input.name.replace(/tasks\[0\]/g, `tasks[${newIndex}]`);
                            input.value = ''; // Clear values
                        }
                    });
                    
                    // Show remove button
                    template.querySelector('.remove-task').style.display = 'block';
                    
                    // Add event listener to remove button
                    template.querySelector('.remove-task').addEventListener('click', function() {
                        template.remove();
                        updateTaskNumbers();
                    });
                    
                    container.appendChild(template);
                    updateTaskNumbers();
                });

                function updateTaskNumbers() {
                    const taskBlocks = document.querySelectorAll('.task-block');
                    taskBlocks.forEach((block, index) => {
                        block.querySelector('h3').textContent = `Task ${index + 1}`;
                        
                        // Update names if needed, but since we're using array indices, it's ok
                        const inputs = block.querySelectorAll('input, select, textarea');
                        inputs.forEach(input => {
                            if (input.name) {
                                const match = input.name.match(/tasks\[(\d+)\]/);
                                if (match) {
                                    input.name = input.name.replace(/tasks\[\d+\]/, `tasks[${index}]`);
                                }
                            }
                        });
                    });
                }
            </script>
        </div>
    </div>
@endsection