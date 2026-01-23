@extends('layout_dashboard')
@section('title', __('task_edit.task_name_label'))

@section('content')
@vite(['resources/js/task_assignee_filter.js'])
@php
    $projectOptions = $projects
        ->mapWithKeys(fn($p) => [$p->id => ($p->name ?? $p->title)])
        ->toArray();

    $assigneeOptions = $assignees
        ->mapWithKeys(fn($u) => [$u->id => $u->name])
        ->toArray();

    $selectedAssignee = old('assignee', $task->assignedUsers->first()->id ?? '');

    $percentageOptions = collect(range(0, 100, 10))
        ->mapWithKeys(fn ($v) => [$v => $v . '%'])
        ->toArray();

    $statusOptions = [
        'pending' => __('task_edit.status_pending'),
        'in_progress' => __('task_edit.status_in_progress'),
        'completed' => __('task_edit.status_completed'),
    ];

    $priorityOptions = [
        'low' => __('task_edit.low'),
        'normal' => __('task_edit.normal'),
        'high' => __('task_edit.high'),
        'critical' => __('task_edit.critical'),
    ];

    $startDateValue = $task->start_date ? $task->start_date->format('Y-m-d') : '';
    $dueDateValue   = $task->due_date ? $task->due_date->format('Y-m-d') : '';

    // Map phases by project ID
    $phasesByProject = $projects->mapWithKeys(function($p) {
        return [$p->id => $p->phases->map(fn($ph) => ['id' => $ph->id, 'title' => $ph->title])->values()];
    });

    $currentProjectId = old('project_id', $task->project_id);
    $currentPhasesOptions = [];
    if ($currentProjectId && isset($phasesByProject[$currentProjectId])) {
        $currentPhasesOptions = collect($phasesByProject[$currentProjectId])
            ->pluck('title', 'id')
            ->toArray();
    }
@endphp

{{-- Main Container --}}
<div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

    {{-- Header Section --}}
    <div class="flex gap-4 flex-row items-center w-full">
        @include('components.back-btn', [
            'route' => 'back.tasks.details',
            'params' => ['task' => $task->id],
        ])
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

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                
                {{-- Title --}}
                <x-form.input
                    label="task_edit.task_name_label"
                    name="title"
                    class="col-span-2 md:col-span-4"
                    :value="$task->title"
                    :isRequired="true"
                />

                {{-- Project --}}
                @if($task->parent_id)
                    <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                @endif
                <x-form.select
                    label="task_edit.project_label"
                    name="project_id"
                    placeholder="Select project"
                    class="col-span-2"
                    :isRequired="true"
                    :value="$task->project_id"
                    :options="$projectOptions"
                    :disabled="!!$task->parent_id"
                />

                {{-- Phase --}}
                <x-form.select
                    label="task_create.phase_label"
                    name="phase_id"
                    placeholder="task_create.select_phase"
                    class="col-span-2"
                    :isRequired="true" 
                    :value="$task->phase_id"
                    :options="$currentPhasesOptions"
                />

                {{-- Assignee --}}
                <x-form.select
                    label="task_edit.assignee_label"
                    name="assignee"
                    placeholder="task_create.select_assignee"
                    class="col-span-2"
                    :isRequired="true"
                    :value="optional($task->assignedUsers->first())->id"
                    :options="[]"
                />

                {{-- Start Date --}}
                <x-form.input
                    type="date"
                    label="task_edit.start_date_label"
                    name="start_date"
                    class="col-span-2"
                    :isRequired="true"
                    :value="$startDateValue"
                />

                {{-- Due Date --}}
                <x-form.input
                    type="date"
                    label="task_edit.due_date_label"
                    name="due_date"
                    class="col-span-2"
                    :isRequired="true"
                    :value="$dueDateValue"
                />

                <div class="grid grid-cols-2 gap-3 col-span-2">
                    {{-- Status --}}
                    <x-form.select
                        label="task_edit.status_label"
                        name="status"
                        class="{{ $task->subTasks()->count() > 0 ? 'col-span-2' : 'col-span-1' }}"
                        :value="old('status', $task->status)"
                        :options="$statusOptions"
                        :isRequired="true"
                    />

                    {{-- Percentage --}}
                    @if ($task->subTasks()->count() === 0)
                        <x-form.select
                            label="task_edit.percentage_label"
                            name="percentage"
                            :value="old('percentage', $task->percentage)"
                            :options="$percentageOptions"
                        />
                    @else
                        <input type="hidden" name="percentage" value="{{ old('percentage', $task->percentage) }}">
                    @endif
                </div>

                {{-- Priority --}}
                <x-form.select
                    label="task_edit.priority_label"
                    placeholder="task_create.select_priority"
                    name="priority"
                    class="col-span-1"
                    :value="old('priority', $task->priority)"
                    :options="$priorityOptions"
                    :isRequired="true"
                />

                {{-- Active Checkbox --}}
                <div class="flex items-center h-full pt-6 col-span-1">
                    <label class="inline-flex items-center cursor-pointer group">
                        {{-- IMPORTANT: hidden input --}}
                        <input type="hidden" name="active" value="0">
                        <input
                            type="checkbox"
                            name="active"
                            value="1"
                            id="active"
                            class="rounded border-muted-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary/20 focus:ring-opacity-50 w-5 h-5 transition-all"
                            {{ old('active', $task->active) ? 'checked' : '' }}
                        >

                        <span class="ml-3 font-semibold text-main group-hover:text-primary transition-colors">
                            {{ __('task_edit.active_label') }}
                        </span>
                    </label>
                </div>

                {{-- Estimated time --}}
                <x-form.input
                    type="number"
                    label="task_edit.estimated_time_label"
                    name="estimated_time"
                    placeholder="task_edit.estimated_time_placeholder"
                    :value="old('estimated_time', $task->estimated_time)"
                    class="col-span-2"
                    min="0"
                    step="1"
                    oninput="if (this.value !== '' && this.value < 0) this.value = 0"
                />

                {{-- Score (Admin/Staff only when task is 100% complete) --}}
                @if((auth()->user()->hasRole('admin') || auth()->user()->hasRole('staff')) && $task->percentage == 100)
                    <x-form.input
                        type="number"
                        label="task_edit.score_label"
                        name="score"
                        placeholder="task_edit.score_placeholder"
                        :value="old('score', $task->score)"
                        class="col-span-2"
                        min="0"
                        max="100"
                        step="1"
                        oninput="if (this.value !== '' && this.value < 0) this.value = 0; if (this.value > 100) this.value = 100;"
                    />
                @endif

                {{-- Description --}}
                <div class="col-span-2 md:col-span-4">
                    <label class="{{ $labelClass }}">{{ __('task_edit.description_label') }}</label>
                    <textarea name="description" class="rich-text {{ $inputClass }} !h-[100px] resize-none" placeholder="{{ __('task_create.description_placeholder') }}">{{ old('description', $task->description) }}</textarea>
                </div>
            </div>

            {{-- Footer / Actions --}}
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-muted-200">
                <!-- <a href="#" onclick="history.back(); return false;" class="px-6 py-3 rounded-xl text-muted-500 font-medium hover:bg-muted-100 transition-colors">
                    {{ __('task_edit.cancel_button') }}
                </a> -->
                <button type="submit" class="group flex items-center justify-center gap-2 rounded-xl bg-primary px-8 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                    <!-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg> -->
                    {{ __('task_edit.save_button') }}
                </button>
            </div>
        </form>
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

            // Current edit values for preselecting
            window.currentEditProjectId = @json($task->project_id);
            window.currentEditAssigneeId = @json(optional($task->assignedUsers->first())->id);
            window.phasesByProject = @json($phasesByProject);

            document.addEventListener('DOMContentLoaded', function() {
                const projectSelect = document.querySelector('select[name="project_id"]');
                const phaseSelect = document.querySelector('select[name="phase_id"]');

                function updatePhaseOptions() {
                    if (!projectSelect || !phaseSelect) return;
                    
                    const projectId = projectSelect.value;
                    const phases = window.phasesByProject[projectId] || [];
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

                if (projectSelect) {
                    projectSelect.addEventListener('change', updatePhaseOptions);
                }
            });
        </script>
    </div>
</div>
@endsection