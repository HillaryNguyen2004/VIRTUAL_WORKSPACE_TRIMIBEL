@extends('layout_dashboard')
@section('title', 'Create Task')
@php
    // Preserve existing role logic for the back button
    if (auth()->user()->hasRole('admin')) {
        $dashRoute = 'admin.back.projects.tasks';        
    } else {
        $dashRoute = 'tasks.index'; 
    }

    $projectOptions = $projects
        ->mapWithKeys(fn($p) => [$p->id => ($p->title ?? $p->name)])
        ->toArray();

    $assigneeOptions = $assignees
        ->mapWithKeys(fn($u) => [$u->id => $u->name])
        ->toArray();
@endphp


@section('content')
    @vite(['resources/js/task_assignee_filter.js'])
    {{-- Main Container matching userdashboard.blade.php --}}
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex gap-4 flex-row items-center w-full">
            @include('components.back-btn', ['route' => $dashRoute])
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
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-danger/20 bg-danger/10 p-4 text-danger">
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('tasks.store') }}" class="relative z-10">
                @csrf

                {{-- Reusable Classes --}}
                @php
                    $labelClass = "block text-sm font-semibold text-main mb-2";
                    $inputClass = "block w-full bg-canvas border border-muted-200 text-main h-[50px] px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all";
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

                        <div class="grid grid-cols-2 gap-3">
                            {{-- Task title --}}
                            <x-form.input
                                label="task_create.task_name_label"
                                name="tasks[0][title]"
                                oldKey="tasks.0.title"
                                placeholder="task_create.task_name_placeholder"
                                class="col-span-2"
                                :isRequired="true"
                            />

                            {{-- Project --}}
                            <x-form.select
                                label="task_create.project_label"
                                name="tasks[0][project_id]"
                                oldKey="tasks.0.project_id"
                                placeholder="task_create.select_project"
                                class="col-span-2 md:col-span-1"
                                :isRequired="true"
                                :value="null"
                                :options="$projectOptions"
                            />

                            {{-- Assignee --}}
                            <x-form.select
                                label="task_create.assignee_label"
                                name="tasks[0][assignee]"
                                oldKey="tasks.0.assignee"
                                placeholder="task_create.select_assignee"
                                class="col-span-2 md:col-span-1"
                                :isRequired="true"
                                :value="null"
                                :options="[]"
                            />


                            {{-- Start date --}}
                            <x-form.input
                                type="date"
                                label="task_create.start_date_label"
                                name="tasks[0][start_date]"
                                oldKey="tasks.0.start_date"
                                :isRequired="true"
                                :value="null"
                            />

                            {{-- Due date --}}
                            <x-form.input
                                type="date"
                                label="task_create.due_date_label"
                                name="tasks[0][due_date]"
                                oldKey="tasks.0.due_date"
                                :isRequired="true"
                                :value="null"
                            />

                            {{-- Description --}}
                            <div class="col-span-2">
                                <label class="{{ $labelClass }}">{{ __('task_create.description_label') }}</label>
                                <textarea name="description" class="rich-text {{ $inputClass }} resize-none" placeholder="{{ __('task_create.description_placeholder') }}" required>{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Add Task Button --}}
                <div class="flex justify-center mb-6">
                    <button type="button" id="add-task" class="flex items-center gap-2 px-4 py-2 bg-accent text-white rounded-xl hover:bg-accent-hover shadow-lg shadow-accent/20 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Another Task
                    </button>
                </div>

                {{-- Footer / Submit --}}
                <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-muted-200">
                    <!-- <a href="#" onclick="history.back(); return false;" class="px-6 py-3 rounded-xl text-muted-500 font-medium hover:bg-muted-100 transition-colors">
                        {{ __('task_create.cancel_button') }}
                    </a> -->
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
                            if (input.type !== 'checkbox') {
                                input.value = ''; // Clear values
                            }
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
            {{-- TinyMCE Script --}}
            <script src="https://cdn.tiny.cloud/1/nd84nj3gfbucyyfu3fobr8s8lgax9x00y378wncd82h3wwmr/tinymce/6/tinymce.min.js"
                referrerpolicy="origin"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (window.tinymce) {
                        tinymce.init({
                            selector: 'textarea.rich-text',
                            height: 400,
                            menubar: false, // Cleaner look
                            statusbar: false, // Cleaner look
                            plugins: [
                                'advlist autolink lists link image charmap preview anchor',
                                'searchreplace visualblocks code fullscreen',
                                'insertdatetime media table paste code help wordcount'
                            ],
                            toolbar: 'undo redo | formatselect | ' +
                                'bold italic underline forecolor | ' +
                                'alignleft aligncenter alignright | ' +
                                'bullist numlist | removeformat | ' +
                                'code',
                            skin: 'oxide', // Use standard skin
                            content_style: 'body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; font-size:14px; color: #334155; }', // Matches Tailwind text-slate-700
                            setup: function (editor) {
                                editor.on('change', function () {
                                    editor.save();
                                });
                            }
                        });

                        // Ensure content is synced on submit
                        document.getElementById('emailTemplateForm').addEventListener('submit', function (e) {
                            tinymce.triggerSave();
                        });
                    }
                });
            </script>
            <script>
                window.projectLeaderMap  = @json($projectLeaderMap);
                window.assigneesByLeader = @json($assigneesByLeader);
            </script>
        </div>
    </div>
@endsection