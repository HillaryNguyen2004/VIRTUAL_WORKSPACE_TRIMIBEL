@extends('layout_dashboard')

@section('content')
<div class="flex flex-col w-full min-h-screen mx-auto text-main px-4 md:px-8 lg:px-16 py-8">
    {{-- HEADER SECTION --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('projects.details', $project->id) }}" 
                class="flex items-center justify-center p-2 hover:bg-muted-100 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 fill-current">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
            </a>
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">
                    {{ $project->title }}
                </h2>
                <p class="text-muted-500 text-sm mt-2">Kanban Board View</p>
            </div>
        </div>

        <div class="flex gap-3 items-center">
            <a href="{{ route('projects.details', $project->id) }}"
                class="flex items-center justify-center gap-2 px-4 py-2 border border-muted-200 hover:border-primary hover:bg-primary/5 rounded-xl transition-colors text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4 fill-current">
                    <path d="M3 13h2v8H3zm4-8h2v16H7zm4-2h2v18h-2zm4-2h2v20h-2zm4 4h2v16h-2z" />
                </svg>
                List View
            </a>

            @can('create', App\Models\Task::class)
            <button onclick="openCreateTaskModal()"
                class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-xl transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                    <path d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                </svg>
                Add Task
            </button>
            @endcan
        </div>
    </div>

    {{-- KANBAN BOARD --}}
    <div class="animate-fade-in-up">
        <div class="overflow-x-auto pb-8">
            <div class="flex gap-6 min-w-min" id="kanbanBoard">
                @forelse($phases as $phase)
                    <div class="flex-shrink-0 w-80">
                        {{-- COLUMN HEADER --}}
                        <div class="bg-muted-50 rounded-t-xl border border-b-0 border-muted-200 px-4 py-3 flex justify-between items-center">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="w-3 h-3 rounded-full {{ $phase->color_class ?? 'bg-blue-500' }}"></div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-main truncate">{{ $phase->title }}</h3>
                                    <p class="text-xs text-muted-500 phase-task-count" data-phase-id="{{ $phase->id }}">{{ $phase->tasks->count() }} tasks</p>
                                    @if($phase->start_date || $phase->due_date)
                                        <div class="text-xs text-muted-400 mt-1 space-y-0.5">
                                            @if($phase->start_date)
                                                <p>Start: {{ \Carbon\Carbon::parse($phase->start_date)->format('M d') }}</p>
                                            @endif
                                            @if($phase->due_date)
                                                <p>Due: {{ \Carbon\Carbon::parse($phase->due_date)->format('M d') }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="openEditPhaseModal({{ $phase->id }}, '{{ $phase->title }}', '{{ $phase->start_date }}', '{{ $phase->due_date }}')"
                                    class="p-1.5 hover:bg-white rounded transition-colors text-muted-500 hover:text-primary" title="Edit phase">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4 fill-current">
                                        <path d="M3 17.25V21h3.75L17.81 5.94l-3.75-3.75L3 17.25z"/>
                                    </svg>
                                </button>
                                
                                
                                <button onclick="deletePhase({{ $phase->id }}, '{{ $phase->title }}')"
                                    class="p-1.5 hover:bg-white rounded transition-colors text-muted-500 hover:text-red-500" title="Delete phase">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4 fill-current">
                                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-9l-1 1H5v2h14V4z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- TASKS CONTAINER (DROPPABLE) --}}
                        <div class="bg-white rounded-b-xl border border-muted-200 p-4 min-h-96 space-y-3 droppable-zone"
                            data-phase-id="{{ $phase->id }}"
                            ondrop="handleDrop(event)"
                            ondragover="handleDragOver(event)"
                            ondragleave="handleDragLeave(event)">

                            @forelse($phase->tasks()->whereNull('parent_id')->with('assignedUsers')->latest()->get() as $task)
                                <div class="bg-white border border-muted-200 rounded-lg p-3 cursor-move hover:shadow-md transition-shadow drag-item"
                                    draggable="true"
                                    data-task-id="{{ $task->id }}"
                                    ondragstart="handleDragStart(event)"
                                    ondragend="handleDragEnd(event)">
                                    
                                    {{-- Task Title --}}
                                    <a href="{{ route('tasks.details', $task->id) }}" class="block group">
                                        <h4 class="font-medium text-sm text-main group-hover:text-primary transition-colors line-clamp-2 mb-2">
                                            {{ $task->title }}
                                        </h4>
                                    </a>

                                    {{-- Task Meta --}}
                                    <div class="flex items-center justify-between text-xs text-muted-500 mb-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-muted-50">
                                            @switch($task->status)
                                                @case('pending')
                                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Pending
                                                    @break
                                                @case('in_progress')
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> In Progress
                                                    @break
                                                @case('completed')
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Completed
                                                    @break
                                            @endswitch
                                        </span>
                                        <span class="px-2 py-0.5 rounded bg-muted-50 font-medium">
                                            {{ $task->priority ?? 'normal' }}
                                        </span>
                                    </div>

                                    {{-- Progress Bar --}}
                                    <div class="mb-3">
                                        <div class="h-1.5 bg-muted-100 rounded overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all"
                                                style="width: {{ $task->percentage ?? 0 }}%"></div>
                                        </div>
                                        <p class="text-xs text-muted-500 mt-1">{{ $task->percentage ?? 0 }}%</p>
                                    </div>

                                    {{-- Assignees --}}
                                    @if($task->assignedUsers->count() > 0)
                                        <div class="flex items-center gap-1 flex-wrap">
                                            @foreach($task->assignedUsers->take(3) as $user)
                                                <img src="{{ getUserAvatar($user) }}" 
                                                    alt="{{ $user->name }}"
                                                    class="w-6 h-6 rounded-full border border-muted-200"
                                                    title="{{ $user->name }}">
                                            @endforeach
                                            @if($task->assignedUsers->count() > 3)
                                                <div class="w-6 h-6 rounded-full bg-muted-200 flex items-center justify-center text-xs font-medium text-muted-600">
                                                    +{{ $task->assignedUsers->count() - 3 }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Due Date --}}
                                    @if($task->due_date)
                                        @php
                                            $dueDate = \Carbon\Carbon::parse($task->due_date);
                                            $isOverdue = $dueDate->isPast() && $task->status !== 'completed';
                                        @endphp
                                        <div class="mt-3 text-xs {{ $isOverdue ? 'text-red-500' : 'text-muted-500' }}">
                                            {{ $isOverdue ? '⚠️ ' : '📅 ' }} {{ $dueDate->format('M d') }}
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center h-48 text-muted-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-12 h-12 fill-current opacity-50 mb-2">
                                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                                    </svg>
                                    <p class="text-sm">No tasks yet</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="w-full flex flex-col items-center justify-center h-96 text-muted-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-16 h-16 fill-current opacity-20 mb-4">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        <p class="text-lg font-medium">No phases created yet</p>
                        <p class="text-sm mb-4">Create a phase to get started</p>
                    </div>
                @endforelse

                {{-- ADD NEW PHASE COLUMN --}}
                <div class="flex-shrink-0 w-80">
                    <div class="bg-muted-50 rounded-t-xl border border-b-0 border-muted-200 px-4 py-3 flex justify-between items-center h-[60px]">
                        <h3 class="font-semibold text-muted-400">Add Phase</h3>
                    </div>
                    <div class="bg-white rounded-b-xl border border-muted-200 border-dashed p-4 min-h-96 flex flex-col items-center justify-center">
                        <button onclick="createNewPhase()"
                            class="flex flex-col items-center justify-center gap-3 p-6 hover:bg-primary/5 rounded-lg transition-colors group w-full">
                            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 fill-primary">
                                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                                </svg>
                            </div>
                            <div class="text-center">
                                <p class="font-medium text-main group-hover:text-primary transition-colors">Create Phase</p>
                                <p class="text-xs text-muted-500 mt-1">Add a new workflow phase</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CREATE TASK MODAL --}}
<div id="createTaskModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
        {{-- MODAL HEADER --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-muted-200 sticky top-0 bg-white">
            <h3 class="text-xl font-bold text-main">Create New Task</h3>
            <button onclick="closeCreateTaskModal()" class="text-muted-500 hover:text-main">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 fill-current">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                </svg>
            </button>
        </div>

        {{-- MODAL BODY --}}
        <form id="taskCreateForm" action="{{ route('tasks.store') }}" method="POST" onsubmit="handleTaskCreateSubmit(event)" class="p-6 space-y-4">
            @csrf
            
            {{-- Title --}}
            <div>
                <label class="block text-sm font-medium text-main mb-2">
                    Task Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" placeholder="Enter task title" required
                    class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-main mb-2">Description</label>
                <textarea name="description" placeholder="Enter task description" rows="3"
                    class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary resize-none"></textarea>
            </div>

            {{-- Project ID (hidden) --}}
            <input type="hidden" name="project_id" value="{{ $project->id }}">

            {{-- Phase Selection --}}
            <div>
                <label class="block text-sm font-medium text-main mb-2">
                    Phase <span class="text-red-500">*</span>
                </label>
                <select name="phase_id" required
                    class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="">-- Select Phase --</option>
                    @foreach($phases as $phase)
                        <option value="{{ $phase->id }}">{{ $phase->title }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Two Column Grid --}}
            <div class="grid grid-cols-2 gap-4">
                {{-- Start Date --}}
                <div>
                    <label class="block text-sm font-medium text-main mb-2">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" value="{{ now()->toDateString() }}" required
                        class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                </div>

                {{-- Due Date --}}
                <div>
                    <label class="block text-sm font-medium text-main mb-2">
                        Due Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="due_date" required
                        class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                </div>
            </div>

            {{-- Two Column Grid --}}
            <div class="grid grid-cols-2 gap-4">
                {{-- Assignee --}}
                <div>
                    <label class="block text-sm font-medium text-main mb-2">Assignee</label>
                    <select name="assignee"
                        class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">-- Unassigned --</option>
                        @php
                            $uniqueUsers = collect();
                            foreach ($phases as $phase) {
                                foreach ($phase->tasks as $task) {
                                    foreach ($task->assignedUsers as $user) {
                                        if (!$uniqueUsers->contains('id', $user->id)) {
                                            $uniqueUsers->push($user);
                                        }
                                    }
                                }
                            }
                        @endphp
                        @forelse($uniqueUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @empty
                        @endforelse
                    </select>
                </div>

                {{-- Priority --}}
                <div>
                    <label class="block text-sm font-medium text-main mb-2">Priority</label>
                    <select name="priority"
                        class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            {{-- Estimated Time --}}
            <div>
                <label class="block text-sm font-medium text-main mb-2">Estimated Hours</label>
                <input type="number" name="estimated_time" value="1" min="1" max="999"
                    class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
            </div>
        </form>

        {{-- MODAL FOOTER --}}
        <div class="flex gap-3 justify-end px-6 py-4 border-t border-muted-200 bg-muted-50 sticky bottom-0">
            <button onclick="closeCreateTaskModal()"
                class="px-4 py-2 border border-muted-200 hover:bg-muted-100 rounded-lg transition-colors font-medium">
                Cancel
            </button>
            <button onclick="document.getElementById('taskCreateForm').submit()"
                class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg transition-colors font-medium">
                Create Task
            </button>
        </div>
    </div>
</div>

{{-- EDIT PHASE MODAL --}}
<div id="editPhaseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
        {{-- MODAL HEADER --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-muted-200 sticky top-0 bg-white">
            <h3 class="text-xl font-bold text-main">Edit Phase</h3>
            <button onclick="closeEditPhaseModal()" class="text-muted-500 hover:text-main">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 fill-current">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                </svg>
            </button>
        </div>

        {{-- MODAL BODY --}}
        <form id="phaseEditForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            
            {{-- Title --}}
            <div>
                <label class="block text-sm font-medium text-main mb-2">
                    Phase Title <span class="text-red-500">*</span>
                </label>
                <input type="text" id="editPhaseTitle" name="title" placeholder="Enter phase title" required
                    class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
            </div>

            {{-- Two Column Grid --}}
            <div class="grid grid-cols-2 gap-4">
                {{-- Start Date --}}
                <div>
                    <label class="block text-sm font-medium text-main mb-2">Start Date</label>
                    <input type="date" id="editPhaseStartDate" name="start_date"
                        class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                </div>

                {{-- Due Date --}}
                <div>
                    <label class="block text-sm font-medium text-main mb-2">Due Date</label>
                    <input type="date" id="editPhaseDueDate" name="due_date"
                        class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                </div>
            </div>
        </form>

        {{-- MODAL FOOTER --}}
        <div class="flex gap-3 justify-end px-6 py-4 border-t border-muted-200 bg-muted-50 sticky bottom-0">
            <button onclick="closeEditPhaseModal()"
                class="px-4 py-2 border border-muted-200 hover:bg-muted-100 rounded-lg transition-colors font-medium">
                Cancel
            </button>
            <button onclick="document.getElementById('phaseEditForm').submit()"
                class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg transition-colors font-medium">
                Save Changes
            </button>
        </div>
    </div>
</div>

<script>
let draggedElement = null;
let sourcePhaseId = null;
const projectId = {{ $project->id }};

function updateTaskCount(phaseId, delta) {
    const countElement = document.querySelector(`.phase-task-count[data-phase-id="${phaseId}"]`);
    if (!countElement) return;
    
    const currentCount = parseInt(countElement.textContent);
    const newCount = currentCount + delta;
    countElement.textContent = `${newCount} task${newCount !== 1 ? 's' : ''}`;
}

function handleDragStart(e) {
    draggedElement = e.target.closest('.drag-item');
    sourcePhaseId = draggedElement?.closest('.droppable-zone')?.dataset.phaseId;
    draggedElement?.classList.add('opacity-50');
}

function handleDragEnd(e) {
    draggedElement?.classList.remove('opacity-50');
    document.querySelectorAll('.droppable-zone').forEach(zone => {
        zone.classList.remove('ring-2', 'ring-primary', 'bg-primary/5');
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('ring-2', 'ring-primary', 'bg-primary/5');
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('ring-2', 'ring-primary', 'bg-primary/5');
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('ring-2', 'ring-primary', 'bg-primary/5');
    
    if (!draggedElement) return;

    const taskId = draggedElement.dataset.taskId;
    const targetPhaseId = e.currentTarget.dataset.phaseId;

    if (sourcePhaseId === targetPhaseId) {
        return; // Same phase, just reorder if needed
    }

    // Optimistic update - move element immediately
    e.currentTarget.appendChild(draggedElement);
    
    // Update task counts immediately
    updateTaskCount(sourcePhaseId, -1);
    updateTaskCount(targetPhaseId, 1);

    // API call to update phase
    fetch(`/api/tasks/${taskId}/move`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({
            phase_id: targetPhaseId
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Failed to move task');
        return response.json();
    })
    .catch(error => {
        console.error('Error:', error);
        // Rollback - move back to original phase and revert counts
        document.querySelector(`[data-phase-id="${sourcePhaseId}"]`)?.appendChild(draggedElement);
        updateTaskCount(sourcePhaseId, 1);
        updateTaskCount(targetPhaseId, -1);
        alert('Failed to move task. Please try again.');
    });
}

function openCreateTaskModal() {
    document.getElementById('createTaskModal').classList.remove('hidden');
    document.getElementById('createTaskModal').classList.add('flex');
}

function closeCreateTaskModal() {
    document.getElementById('createTaskModal').classList.add('hidden');
    document.getElementById('createTaskModal').classList.remove('flex');
    document.getElementById('taskCreateForm').reset();
}

function handleTaskCreateSubmit(e) {
    e.preventDefault();
    
    const form = document.getElementById('taskCreateForm');
    const phaseId = new FormData(form).get('phase_id');
    
    // Submit form via fetch
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => {
        if (response.ok) {
            // Update task count for the phase
            updateTaskCount(phaseId, 1);
            // Close modal and reset form
            closeCreateTaskModal();
            showNotification('Task created successfully!', 'success');
        } else {
            throw new Error('Failed to create task');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to create task. Please try again.', 'error');
    });
}

function openEditPhaseModal(phaseId, title, startDate, dueDate) {
    document.getElementById('editPhaseTitle').value = title;
    document.getElementById('editPhaseStartDate').value = startDate || '';
    document.getElementById('editPhaseDueDate').value = dueDate || '';
    
    const form = document.getElementById('phaseEditForm');
    form.action = `/phases/${phaseId}`;
    
    document.getElementById('editPhaseModal').classList.remove('hidden');
    document.getElementById('editPhaseModal').classList.add('flex');
}

function closeEditPhaseModal() {
    document.getElementById('editPhaseModal').classList.add('hidden');
    document.getElementById('editPhaseModal').classList.remove('flex');
    document.getElementById('phaseEditForm').reset();
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const createModal = document.getElementById('createTaskModal');
    if (createModal) {
        createModal.addEventListener('click', function(e) {
            if (e.target === createModal) {
                closeCreateTaskModal();
            }
        });
    }
    
    const editModal = document.getElementById('editPhaseModal');
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) {
                closeEditPhaseModal();
            }
        });
    }
});

function deletePhase(phaseId, title) {
    // Get the phase column to check task count
    const phaseColumn = document.querySelector(`[data-phase-id="${phaseId}"]`);
    const taskElements = phaseColumn?.querySelectorAll('.drag-item') || [];
    const taskCount = taskElements.length;

    // Check if phase has tasks
    if (taskCount > 0) {
        // Show error notification
        showNotification(`Cannot delete phase "${title}" because it contains ${taskCount} task(s). Please move or delete all tasks first.`, 'error');
        return;
    }

    // Confirm deletion if no tasks
    if (!confirm(`Are you sure you want to delete the phase "${title}"?`)) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/phases/${phaseId}`;
    form.innerHTML = `
        @csrf
        @method('DELETE')
    `;
    document.body.appendChild(form);
    form.submit();
}

function showNotification(message, type = 'info') {
    const alertsContainer = document.getElementById('alerts');
    if (!alertsContainer) return;

    const bgColor = type === 'error' ? 'bg-red-500' : type === 'success' ? 'bg-green-500' : 'bg-blue-500';
    const alertEl = document.createElement('div');
    alertEl.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg animate-fade-in-up`;
    alertEl.textContent = message;

    alertsContainer.appendChild(alertEl);

    // Auto remove after 3 seconds
    setTimeout(() => {
        alertEl.style.opacity = '0';
        alertEl.style.transition = 'opacity 0.3s';
        setTimeout(() => alertEl.remove(), 300);
    }, 3000);
}

function createNewPhase() {
    const phaseTitle = prompt('Enter phase name:', '');
    if (!phaseTitle || phaseTitle.trim() === '') return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/projects/${projectId}/phases`;
    form.innerHTML = `
        @csrf
        <input type="hidden" name="title" value="${phaseTitle.trim()}">
        <input type="hidden" name="start_date" value="${new Date().toISOString().split('T')[0]}">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
