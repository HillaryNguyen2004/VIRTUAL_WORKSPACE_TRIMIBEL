@props(['title' => '', 'emailTemplate' => null, 'users' => collect(), 'templates' => collect()])

@php
    $isEdit = isset($emailTemplate);
    $formAction = isset($emailTemplate) ? route('email-templates.update', $emailTemplate) : route('email-templates.store')
@endphp

<x-form-layout :title="$title">
    {{-- form --}}
    <form id="emailTemplateForm" action="{{ $formAction }}" method="POST"
        class="flex flex-col items-center gap-3 w-full py-6 px-8">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full">
            {{-- name --}}
            <div class="flex flex-col gap-1 text-sm xl:text-lg">
                <label class="">{{ __('template_create.title') }} <span class="text-red-600">*</span></label>
                <input type="text" name="name"
                    class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    placeholder="{{ __('template_create.title_placeholder') }}"
                    value="{{ old('name', $emailTemplate->name ?? '') }}" required>
            </div>

            <div class="flex flex-col gap-1 text-sm xl:text-lg">
                <label class="">{{ __('template_create.subject') }}</label>
                <input type="text" name="subject"
                    class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    value="{{ old('subject', $emailTemplate->subject ?? '') }}"
                    placeholder="{{ __('template_create.subject_placeholder') }}">
            </div>

            <div class="flex flex-col gap-1 text-sm xl:text-lg md:col-span-2">
                <label class="form-label">{{ __('template_create.description') }}</label>
                <textarea name="description"
                    class="rounded-xl border border-gray-300 px-4 py-2 h-64 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition resize-none"
                    placeholder="{{ __('template_create.description_placeholder') }}">{{ old('description', $emailTemplate->description ?? '') }}</textarea>
            </div>

            <div class="flex flex-col gap-1 text-sm xl:text-lg md:col-span-2">
                <label class="">{{ __('template_create.body') }} <span class="text-red-600">*</span></label>
                <textarea name="content"
                    class="rich-text rounded-xl border border-gray-300 px-4 py-2 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition resize-none"
                    required>{{ old('content', $emailTemplate->content ?? '') }}</textarea>
                <div class="flex flex-col">
                    <p class="font-medium text-sm md:text-base">{{ __('template_create.variables') }}</p>
                    <div class="flex flex-wrap gap-2 justify-between text-xs md:text-sm">
                        <div>
                            <code class="text-red-700">{first_name}</code><br>
                            <p class="text-gray-400">{{ __('template_create.variable_first_name') }}</p>
                        </div>
                        <div>
                            <code class="text-red-700">{total_of_times_late}</code><br>
                            <p class="text-gray-400">{{ __('template_create.variable_times_late') }}</p>
                        </div>
                        <div>
                            <code class="text-red-700">{site_title}</code><br>
                            <p class="text-gray-400">{{ __('template_create.variable_site_title') }}</p>
                        </div>
                        <div>
                            <code class="text-red-700">{birthday}</code><br>
                            <p class="text-gray-400">{{ __('template_create.variable_birthday') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit"
            class="px-4 py-2 mt-4 w-full sm:w-52 bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
            {{ __('template_create.save') }}
        </button>
    </form>

    <script src="https://cdn.tiny.cloud/1/nd84nj3gfbucyyfu3fobr8s8lgax9x00y378wncd82h3wwmr/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.tinymce) {
                tinymce.init({
                    selector: 'textarea.rich-text',
                    height: 400,
                    menubar: true,
                    plugins: [
                        'advlist autolink lists link image charmap preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime media table paste code help wordcount'
                    ],
                    toolbar: 'undo redo | formatselect | ' +
                        'bold italic underline strikethrough forecolor backcolor | ' +
                        'alignleft aligncenter alignright alignjustify | ' +
                        'bullist numlist outdent indent | removeformat | ' +
                        'link image media table | code fullscreen',
                    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                    setup: function (editor) {
                        editor.on('init', function () {
                            console.log('✅ TinyMCE editor ready:', editor.id);
                            editor.getBody().setAttribute('contenteditable', 'true');
                        });
                    },
                    // Ensure form submission works with TinyMCE
                    setup: function (editor) {
                        editor.on('change', function () {
                            // Sync the editor content back to the textarea before form submission
                            editor.save();
                        });
                    }
                }).then(function (editors) {
                    console.log('✅ TinyMCE editors initialized:', editors.length);
                }).catch(function (error) {
                    console.error('❌ TinyMCE initialization error:', error);
                });
            } else {
                console.error('❌ TinyMCE failed to load. Check the network tab or console for errors.');
            }
        });

        // Add a fallback to ensure form submission
        document.getElementById('emailTemplateForm').addEventListener('submit', function (e) {
            tinymce.triggerSave(); // Sync TinyMCE content to textarea
            console.log('Form submitted, content synced:', document.getElementById('content').value);
        });
    </script>
</x-form-layout>