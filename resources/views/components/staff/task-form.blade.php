@props(['title' => '', 'action' => null, 'staffUsers' => collect(), 'task' => null, 'taskName' => '', 'taskDueDate' => '', 'taskAssignee' => '', 'taskTag' => '', 'taskDescription' => '', 'taskActive' => true])

@php
    $isEdit = !is_null($task);
    $formAction = $action ?? ($isEdit ? route('tasks.update', $task) : route('tasks.store'));
@endphp

<x-form-layout :title="$title">
    {{-- form --}}
    <form action="{{ $formAction }}" method="POST" class="flex flex-col items-center gap-3 w-full py-6 px-8">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full">
            {{-- name --}}
            <div class="flex flex-col gap-1 text-sm xl:text-lg">
                <label class="">{{ __('task_create.task_name_label') }} <span class="text-red-600">*</span></label>
                <input type="text" name="title"
                    class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    placeholder="{{ __('task_create.task_name_placeholder') }}" value="{{ old('title', $task->title ?? '') }}" required>
            </div>
            <div class="flex flex-col gap-1 text-sm xl:text-lg">
                <label class="form-label fw-bold">{{ __('task_create.due_date_label') }} <span
                        class="text-red-600">*</span></label>
                <input type="date" name="due_date"
                    class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    value="{{ old('due_date', $task->due_date ?? '') }}" required>
            </div>
            <div class="flex flex-col gap-1 text-sm xl:text-lg">
                <label class="">{{ __('task_create.assignee_label') }} <span class="text-red-600">*</span></label>
                <select name="assignee"
                    class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    required>
                    <option value="">{{ __('task_create.select_assignee') }}</option>
                    @foreach($staffUsers as $user)
                        <option value="{{ $user->id }}" 
                            @selected(old('assigned_user_id', $task->assigned_user_id ?? null) == $user->id)
                        >
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($isEdit)
                <div class="flex flex-col gap-1 text-sm xl:text-lg">
                    <label class="">{{ __('task_edit.status_label') }} <span class="text-red-600">*</span></label>
                    <select name="status" class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition" required>
                        <option value="pending" {{ $task->status == 'pending' ? 'selected' : '' }}>{{ __('task_edit.status_pending') }}</option>
                        <option value="in_progress" {{ $task->status == 'in_progress' ? 'selected' : '' }}>{{ __('task_edit.status_in_progress') }}</option>
                        <option value="completed" {{ $task->status == 'completed' ? 'selected' : '' }}>{{ __('task_edit.status_completed') }}</option>
                    </select>
                </div>
            @endif
            <div class="flex flex-col gap-1 text-sm xl:text-lg {{ $isEdit ? 'md:col-span-2' : '' }}">
                <label class="">{{ __('task_create.tags_label') }}</label>
                <input type="text" name="tags[]"
                    class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    placeholder="{{ __('task_create.tag_placeholder') }}">
            </div>
            <div class="flex flex-col gap-1 text-sm xl:text-lg md:col-span-2">
                <label class="">{{ __('task_create.description_label') }}</label>
                <textarea name="description"
                    class="rounded-xl border border-gray-300 px-4 py-2 h-64 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition resize-none"
                    placeholder="{{ __('task_create.description_placeholder') }}">{{ old('description', $task->description ?? '') }}</textarea>
            </div>
            <div class="flex gap-2 items-center text-sm xl:text-lg">
                <input class="rounded border-gray-300" type="checkbox" name="active" id="active" value="1" checked>
                <label class="" for="active">
                    {{ __('task_create.active_label') }}
                </label>
            </div>
        </div>
        <button type="submit"
            class="px-4 py-2 mt-4 w-full sm:w-52 bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
            {{ __('task_create.save_button') }}
        </button>
    </form>
</x-form-layout>