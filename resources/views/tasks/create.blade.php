@extends('layout_dashboard')
@section('title', 'Create Task')
@php
    $projectId = request('project_id');
    $phaseId = request('phase_id');
    $parentId = request('parent_id');

    // Preserve existing role logic for the back button
    if ($parentId) {
        $dashRoute  = 'tasks.details';
        $dashParams = ['task' => $parentId];
    }
    else if ($projectId){
        $dashRoute = 'projects.details';
        $dashParams = ['id' => $projectId];
    } else {
        if (auth()->user()->hasRole('admin')) {
            $dashRoute = 'admin.back.projects.tasks';        
        } else {
            $dashRoute = 'tasks.index'; 
        }   
    }

    $projectOptions = $projects
        ->mapWithKeys(fn($p) => [$p->id => ($p->title ?? $p->name)])
        ->toArray();

    $priorityOptions = [
        'low' => __('task_edit.low'),
        'normal' => __('task_edit.normal'),
        'high' => __('task_edit.high'),
        'critical' => __('task_edit.critical'), 
    ];

    $assigneeOptions = [];

    if (is_scalar($defaultLeaderId) && $defaultLeaderId !== '') {
        $assigneeOptions = $assignees
            ->filter(fn($u) => (int)$u->id === (int)$defaultLeaderId || (int)$u->team_leader_id === (int)$defaultLeaderId)
            ->pluck('name', 'id')
            ->toArray();
    }

    // Map phases by project ID
    $phasesByProject = $projects->mapWithKeys(function($p) {
        return [$p->id => $p->phases->map(fn($ph) => ['id' => $ph->id, 'title' => $ph->title])->values()];
    });

    $currentProjectId = $taskData['project_id'] ?? $projectId ?? null;
    $currentPhasesOptions = [];
    $currentPhaseId = null;
    if ($currentProjectId && isset($phasesByProject[$currentProjectId])) {
        $currentPhasesOptions = collect($phasesByProject[$currentProjectId])
            ->pluck('title', 'id')
            ->toArray();
        // Pre-select phase if coming from Kanban board
        $currentPhaseId = $phaseId ?? null;
    }

    $tasksOld = old('tasks', [[]]);
@endphp


@section('content')
    @vite(['resources/js/task_assignee_filter.js'])
    {{-- Main Container matching userdashboard.blade.php --}}
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex gap-4 flex-row items-center w-full">
            @include('components.back-btn', ['route' => $dashRoute, 'params' => $dashParams ?? []])
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
                    @foreach($tasksOld as $index => $taskData)
                        {{-- Task Block Template --}}
                        <div class="task-block border border-muted-200 rounded-xl p-4 mb-4 relative">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-semibold text-main">Task {{ $index + 1 }}</h3>
                                <button type="button" class="remove-task text-danger hover:text-danger-hover" style="{{ $loop->first ? 'display: none;' : '' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                {{-- Parent task --}}
                                @if($parentId)
                                    <input type="hidden" name="tasks[{{ $index }}][parent_id]" value="{{ $parentId }}">
                                @endif

                                {{-- Task title --}}
                                <x-form.input
                                    label="task_create.task_name_label"
                                    name="tasks[{{ $index }}][title]"
                                    oldKey="tasks.{{ $index }}.title"
                                    placeholder="task_create.task_name_placeholder"
                                    class="col-span-2"
                                    :isRequired="true"
                                    :value="old('tasks.'.$index.'.title')"
                                />

                                {{-- Project --}}
                                @if (!$parentId)
                                    @if($projectId)   
                                        <input type="hidden" name="tasks[{{ $index }}][project_id]" value="{{ $projectId }}">
                                    @endif

                                    <x-form.select
                                        label="task_create.project_label"
                                        name="tasks[{{ $index }}][project_id]"
                                        oldKey="tasks.{{ $index }}.project_id"
                                        placeholder="task_create.select_project"
                                        class="col-span-2 md:col-span-1"
                                        :isRequired="true"
                                        :disabled="$projectId !== null"
                                        :value="old('tasks.'.$index.'.project_id', $projectId)"
                                        :options="$projectOptions"
                                    />

                                    <x-form.select
                                        label="task_create.phase_label"
                                        name="tasks[{{ $index }}][phase_id]"
                                        oldKey="tasks.{{ $index }}.phase_id"
                                        placeholder="task_create.select_phase"
                                        class="col-span-2 md:col-span-1"
                                        :isRequired="true" 
                                        :value="old('tasks.'.$index.'.phase_id', $currentPhaseId)"
                                        :options="$currentPhasesOptions"
                                    />

                                    {{-- Assignee --}}
                                    <x-form.select
                                        label="task_create.assignee_label"
                                        name="tasks[{{ $index }}][assignee]"
                                        oldKey="tasks.{{ $index }}.assignee"
                                        placeholder="task_create.select_assignee"
                                        class="col-span-2 md:col-span-1"
                                        :isRequired="true"
                                        :value="old('tasks.'.$index.'.assignee')"
                                        :options="$assigneeOptions"
                                    />
                                @else
                                    {{-- Hidden Project ID for submission --}}
                                    <input type="hidden" name="tasks[{{ $index }}][project_id]" value="{{ $parentTask->project_id }}">

                                    {{-- Disabled Project Select for display --}}
                                    <x-form.select
                                        label="task_create.project_label"
                                        name="project_display_only_{{ $index }}"
                                        placeholder="task_create.select_project"
                                        class="col-span-2 md:col-span-1"
                                        :isRequired="true"
                                        :value="$parentTask->project_id"
                                        :options="[$parentTask->project_id => $parentTask->project->title ?? $parentTask->project->name ?? 'Unknown Project']"
                                        :disabled="true"
                                    />

                                    <x-form.select
                                        label="task_create.assignee_label"
                                        name="tasks[{{ $index }}][assignee]"
                                        oldKey="tasks.{{ $index }}.assignee"
                                        placeholder="task_create.select_assignee"
                                        class="col-span-2 md:col-span-1"
                                        :isRequired="true"
                                        :value="old('tasks.'.$index.'.assignee', $defaultAssigneeId)"
                                        :options="$assigneeOptions"
                                    />
                                @endif

                                {{-- Start date --}}
                                <x-form.input
                                    type="date"
                                    label="task_create.start_date_label"
                                    name="tasks[{{ $index }}][start_date]"
                                    oldKey="tasks.{{ $index }}.start_date"
                                    :isRequired="true"
                                    :value="old('tasks.'.$index.'.start_date')"
                                />

                                {{-- Due date --}}
                                <x-form.input
                                    type="date"
                                    label="task_create.due_date_label"
                                    name="tasks[{{ $index }}][due_date]"
                                    oldKey="tasks.{{ $index }}.due_date"
                                    :isRequired="true"
                                    :value="old('tasks.'.$index.'.due_date')"
                                />

                                {{-- Priority --}}
                                <x-form.select
                                    label="task_create.priority_label"
                                    name="tasks[{{ $index }}][priority]"
                                    oldKey="tasks.{{ $index }}.priority"
                                    placeholder="task_create.select_priority"
                                    class="col-span-2 md:col-span-1"
                                    :isRequired="true"
                                    :value="old('tasks.'.$index.'.priority')"
                                    :options="$priorityOptions"
                                />

                                {{-- Estimated time --}}
                                <x-form.input
                                    type="number"
                                    label="task_create.estimated_time_label"
                                    name="tasks[{{ $index }}][estimated_time]"
                                    oldKey="tasks.{{ $index }}.estimated_time"
                                    placeholder="task_create.estimated_time_placeholder"
                                    :value="old('tasks.'.$index.'.estimated_time', 0)"
                                    class="col-span-2 md:col-span-1"
                                    min="0"
                                    step="1"
                                    oninput="if (this.value !== '' && this.value < 0) this.value = 0"
                                />

                                {{-- Description --}}
                                <div class="col-span-2">
                                    <label class="{{ $labelClass }}">{{ __('task_create.description_label') }}</label>
                                    <textarea name="tasks[{{ $index }}][description]" class="rich-text {{ $inputClass }} resize-none" placeholder="{{ __('task_create.description_placeholder') }}">{{ old('tasks.'.$index.'.description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
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
            <script src="https://cdn.tiny.cloud/1/nd84nj3gfbucyyfu3fobr8s8lgax9x00y378wncd82h3wwmr/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const container = document.getElementById('tasks-container');
                    const addTaskBtn = document.getElementById('add-task');

                    // Function to initialize TinyMCE on a specific element or all
                    function initTinyMCE(target) {
                        if (window.tinymce) {
                            // If target is provided, init only that. Otherwise init all uninitialized .rich-text
                            const config = {
                                target: target, // If null, this property is ignored by some versions, but selector is used
                                selector: target ? undefined : 'textarea.rich-text', 
                                height: 400,
                                menubar: false,
                                statusbar: false,
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
                                skin: 'oxide',
                                content_style: 'body { font-family: Inter, ui-sans-serif, system-ui, sans-serif; font-size:14px; color: #334155; }',
                                setup: function (editor) {
                                    editor.on('change', function () {
                                        editor.save();
                                    });
                                }
                            };
                            
                            // If target is passed, we use it specifically
                            if (target) {
                                config.target = target;
                                config.selector = undefined;
                                tinymce.init(config);
                            } else {
                                tinymce.init(config);
                            }
                        }
                    }

                    // Initial Init
                    initTinyMCE();

                    addTaskBtn.addEventListener('click', function() {
                        const taskBlocks = container.querySelectorAll('.task-block');
                        const newIndex = taskBlocks.length;

                        // Clone the first task block to use as template
                        // Ideally we clone the structure, but we need to be careful with TinyMCE
                        const sourceBlock = taskBlocks[0];
                        const template = sourceBlock.cloneNode(true);
                        
                        // Clean up TinyMCE artifacts from the clone
                        const renderers = template.querySelectorAll('.tox-tinymce');
                        renderers.forEach(el => el.remove());
                        
                        // Reset textareas that were hidden by TinyMCE
                        const textareas = template.querySelectorAll('textarea');
                        textareas.forEach(ta => {
                            ta.style.display = ''; // Visible again
                            ta.style.visibility = '';
                            ta.removeAttribute('id'); // Remove old ID if present
                            ta.value = ''; // Clear content
                        });

                        // Update the title
                        template.querySelector('h3').textContent = `Task ${newIndex + 1}`;
                        
                        // Update input names and values
                        const inputs = template.querySelectorAll('input, select, textarea');
                        inputs.forEach(input => {
                            if (input.name) {
                                // Dynamic replace of index: tasks[0] or tasks[x] -> tasks[newIndex]
                                input.name = input.name.replace(/tasks\[\d+\]/g, `tasks[${newIndex}]`);
                                
                                // Clear values ONLY if not hidden and not disabled
                                if (input.type !== 'checkbox' && input.type !== 'hidden' && !input.disabled) {
                                    input.value = ''; 
                                }
                            }
                        });
                        
                        // Show remove button
                        const removeBtn = template.querySelector('.remove-task');
                        if (removeBtn) {
                            removeBtn.style.display = 'block';
                            removeBtn.addEventListener('click', function() {
                                // If we remove a task, we should remove the editor instance first to avoid leaks
                                const ta = template.querySelector('textarea.rich-text');
                                if (ta && window.tinymce) {
                                    const editor = tinymce.get(ta);
                                    if (editor) editor.remove();
                                }
                                template.remove();
                                updateTaskNumbers();
                            });
                        }
                        
                        container.appendChild(template);
                        
                        // Re-init TinyMCE for the new textarea
                        const newTextarea = template.querySelector('textarea.rich-text');
                        if (newTextarea) {
                             // Give it a unique ID to ensure clean init
                            const uniqueId = 'editor_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                            newTextarea.id = uniqueId;
                            initTinyMCE(newTextarea);
                        }

                        updateTaskNumbers();
                    });

                    function updateTaskNumbers() {
                        const taskBlocks = document.querySelectorAll('.task-block');
                        taskBlocks.forEach((block, index) => {
                            block.querySelector('h3').textContent = `Task ${index + 1}`;
                            
                             const removeBtn = block.querySelector('.remove-task');
                             if (index === 0 && taskBlocks.length === 1) {
                                 removeBtn.style.display = 'none';
                             } else {
                                 removeBtn.style.display = 'block';
                             }

                            const inputs = block.querySelectorAll('input, select, textarea');
                            inputs.forEach(input => {
                                if (input.name) {
                                    input.name = input.name.replace(/tasks\[\d+\]/g, `tasks[${index}]`);
                                }
                            });
                        });
                    }
                    
                    // Attach remove event to existing remove buttons (from validation loop)
                    document.querySelectorAll('.remove-task').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const block = btn.closest('.task-block');
                            // Remove editor instance
                            const ta = block.querySelector('textarea.rich-text');
                            if (ta && window.tinymce && ta.id) {
                                const editor = tinymce.get(ta.id);
                                if (editor) editor.remove();
                            }
                            
                            block.remove();
                            updateTaskNumbers();
                        });
                    });

                    // Sync content on submit
                    const form = document.querySelector('form');
                    if(form) {
                        form.addEventListener('submit', function() {
                            if (window.tinymce) tinymce.triggerSave();
                        });
                    }
                });
            </script>
            <script>
                window.projectLeaderMap  = @json($projectLeaderMap);
                window.assigneesByLeader = @json($assigneesByLeader);
                window.__DEFAULT_LEADER_ID__ = @json($defaultLeaderId);
                window.__DEFAULT_ASSIGNEE_ID__ = @json($defaultAssigneeId);
                window.__ASSIGNEES_BY_LEADER__ = @json($assigneesByLeader);
                window.phasesByProject = @json($phasesByProject);

                document.addEventListener('DOMContentLoaded', function() {
                    function updatePhaseOptions(projectSelect, phaseSelect) {
                        const projectId = projectSelect.value;
                        const phases = window.phasesByProject[projectId] || [];
                        
                        // Save current value if any
                        const currentPhaseId = phaseSelect.getAttribute('data-value') || phaseSelect.value;
                        
                        phases.forEach(phase => {
                            const option = document.createElement('option');
                            option.value = phase.id;
                            option.textContent = phase.title;
                            if (currentPhaseId == phase.id) {
                                option.selected = true;
                            }
                            phaseSelect.appendChild(option);
                        });
                    }

                    // Event delegation for project selects
                    document.getElementById('tasks-container').addEventListener('change', function(e) {
                        if (e.target.matches('select[name*="[project_id]"]')) {
                            const block = e.target.closest('.task-block');
                            const phaseSelect = block.querySelector('select[name$="[phase_id]"]');
                            if (phaseSelect) {
                                updatePhaseOptions(e.target, phaseSelect);
                            }
                        }
                    });
                });
            </script>
        </div>
    </div>
@endsection