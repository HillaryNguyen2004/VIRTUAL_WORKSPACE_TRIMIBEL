@extends('layout_dashboard')
@section('title', __('projects.create_project'))

@section('content')
    @role('admin')
    {{-- Main Container --}}
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex gap-4 flex-row items-center w-full">
            @include('components.back-btn' , ['route' => 'projects.index'])
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('projects.create_project') }}</h2>
                <p class="text-muted-500 text-sm mt-1">{{ __('projects.create_subtitle') ?? 'Enter project details below' }}</p>
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

            {{-- Error Handling (Added for consistency) --}}
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-danger/20 bg-danger/10 p-4 text-danger">
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('projects.store') }}" method="POST" class="relative z-10">
                @csrf

                {{-- Reusable Classes --}}
                @php
                    $labelClass = "block text-sm font-semibold text-main mb-2";
                    $inputClass = "block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all";
                @endphp

                <div class="grid grid-cols-2 gap-3">
                    
                    {{-- Title --}}
                    <x-form.input
                        label="projects.title"
                        name="title"
                        placeholder="Enter project Name"
                        class="col-span-2"
                        :isRequired="true"
                    />

                    {{-- Assign Staff --}}
                    <x-form.select
                        label="projects.assign_staff"
                        name="staff_id"
                        placeholder="-- Select Staff --"
                        class="col-span-2 md:col-span-1"
                        :isRequired="true"
                        :options="$staffUsers->pluck('name','id')"
                    />

                    {{-- Status --}}
                    <x-form.select
                        label="projects.status"
                        name="status"
                        class="col-span-2 md:col-span-1"
                        :value="'active'"
                        :options="[
                            'active' => __('projects.active'),
                            'inactive' => __('projects.inactive')
                        ]"
                    />

                    {{-- Start date --}}
                    <x-form.input
                        type="date"
                        label="projects.start_date"
                        name="start_date"
                        :value="old('start_date')"
                        :isRequired="false"
                    />

                    {{-- Due date --}}
                    <x-form.input
                        type="date"
                        label="projects.due_date"
                        name="due_date"
                        :value="old('due_date')"
                        :isRequired="false"
                    />

                    {{-- Description --}}
                    <div class="col-span-2">
                        <label class="{{ $labelClass }}">{{ __('projects.description') }}</label>
                        <textarea name="description" class="rich-text {{ $inputClass }} h-[200px] resize-none" placeholder="{{ __('projects.description_placeholder') }}" required></textarea>
                    </div>
                </div>

                {{-- Footer / Submit --}}
                <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-muted-200">
                    <!-- <a href="{{ route('projects.index') }}" class="px-6 py-3 rounded-xl text-muted-500 font-medium hover:bg-muted-100 transition-colors">
                        {{ __('projects.cancel') }}
                    </a> -->
                    <button type="submit" class="group flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                        {{ __('projects.create') }}
                    </button>
                </div>
            </form>

            {{-- TinyMCE Script --}}
            <script src="https://cdn.tiny.cloud/1/nd84nj3gfbucyyfu3fobr8s8lgax9x00y378wncd82h3wwmr/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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

        </div>
    </div>
    @endrole
@endsection