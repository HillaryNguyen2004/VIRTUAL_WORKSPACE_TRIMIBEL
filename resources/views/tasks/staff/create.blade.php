@extends('layout_dashboard')

@section('content')
    <x-action-layout :route="route('tasks.staff.index')" title="Create Task">

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
                <textarea name="description" class="rich-text {{ $inputClass }} h-[150px] resize-none" required>{{ old('description') }}</textarea>
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
                <input type="date" name="start_date" class="w-full border rounded-lg px-3 py-2"
                    value="{{ old('start_date') }}">
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
    </x-action-layout>
@endsection