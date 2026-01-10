@extends('layout_dashboard')
@section('title', isset($emailTemplate) ? __('template_create.edit_template') : __('template_create.create_template'))

@section('content')
    {{-- Main Container --}}
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex gap-4 flex-row items-center w-full">
            @include('components.back-btn' , ['route' => 'email-templates.index'])
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">
                    {{ isset($emailTemplate) ? __('template_create.edit_template') : __('template_create.create_template') }}
                </h2>
                <p class="text-muted-500 text-sm mt-1">
                    {{ __('template_create.create_subtitle') ?? 'Design and format your email layout' }}
                </p>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="w-full bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 relative overflow-hidden animate-fade-in-up">
            
            {{-- Decorative background element --}}
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-primary/10 rounded-full blur-2xl opacity-50 pointer-events-none"></div>

            {{-- Error Handling --}}
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-danger/20 bg-danger/10 p-4 text-danger">
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="emailTemplateForm" action="{{ isset($emailTemplate) ? route('email-templates.update', $emailTemplate) : route('email-templates.store') }}" method="POST" class="relative z-10">
                @csrf
                @if(isset($emailTemplate)) 
                    @method('PUT') 
                @endif

                {{-- Reusable Classes --}}
                @php
                    $labelClass = "block text-sm font-semibold text-main mb-2";
                    $inputClass = "block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all";
                @endphp

                <div class="grid grid-cols-1 gap-6">
                    
                    {{-- Title & Subject Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Title --}}
                        <div>
                            <label class="{{ $labelClass }}">
                                {{ __('template_create.title') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="{{ $inputClass }}" 
                                value="{{ old('name', $emailTemplate->name ?? '') }}"
                                placeholder="{{ __('template_create.title_placeholder') }}" required>
                        </div>

                        {{-- Subject --}}
                        <div>
                            <label class="{{ $labelClass }}">
                                {{ __('template_create.subject') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="subject" class="{{ $inputClass }}"
                                value="{{ old('subject', $emailTemplate->subject ?? '') }}"
                                placeholder="{{ __('template_create.subject_placeholder') }}">
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="{{ $labelClass }}">{{ __('template_create.description') }}</label>
                        <textarea name="description" class="{{ $inputClass }} min-h-[80px] resize-none" placeholder="{{ __('template_create.description_placeholder') }}">{{ old('description', $emailTemplate->description ?? '') }}</textarea>
                    </div>

                    {{-- WYSIWYG Editor --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            {{ __('template_create.body') }} <span class="text-danger">*</span>
                        </label>
                        {{-- Wrapper to ensure rounded corners even with TinyMCE --}}
                        <div class="rounded-xl overflow-hidden border border-muted-200 focus-within:ring-2 focus-within:ring-accent/20 focus-within:border-accent transition-all">
                            <textarea id="content" name="content" class="rich-text w-full h-[400px]" required>{{ old('content', $emailTemplate->content ?? '') }}</textarea>
                        </div>
                    </div>

                    {{-- Variables Helper Section --}}
                    <div class="bg-muted-50 border border-muted-200 rounded-xl p-4">
                        <h5 class="font-semibold text-main text-sm mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ __('template_create.variables') }}
                        </h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                            {{-- Helper Function to create variable pills --}}
                            @foreach([
                                '{first_name}' => __('template_create.variable_first_name'),
                                '{total_of_times_late}' => __('template_create.variable_times_late'),
                                '{site_title}' => __('template_create.variable_site_title'),
                                '{birthday}' => __('template_create.variable_birthday')
                            ] as $code => $desc)
                                <div class="bg-white p-2 rounded-lg border border-muted-200 flex flex-col gap-1">
                                    <code class="text-accent text-xs font-bold bg-accent/5 px-2 py-1 rounded w-fit select-all">{{ $code }}</code>
                                    <span class="text-xs text-muted-500">{{ $desc }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- Footer / Submit --}}
                <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-muted-200">
                    <a href="{{ route('email-templates.index') }}" class="px-6 py-3 rounded-xl text-muted-500 font-medium hover:bg-muted-100 transition-colors">
                        {{ __('template_create.cancel') ?? 'Cancel' }}
                    </a>
                    <button type="submit" id="saveButton" class="group flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                        {{ __('template_create.save') }}
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
@endsection