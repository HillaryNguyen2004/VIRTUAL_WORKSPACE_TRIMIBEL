@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">{{ isset($emailTemplate) ? 'Edit' : 'Create' }} Email Template</h1>

    <form action="{{ isset($emailTemplate) ? route('email-templates.update', $emailTemplate) : route('email-templates.store') }}"
          method="POST"
          class="bg-light p-4 border rounded"
          id="emailTemplateForm">
        @csrf
        @if(isset($emailTemplate)) @method('PUT') @endif

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Title *</label>
                <input name="name" class="form-control" value="{{ old('name', $emailTemplate->name ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Subject *</label>
                <input name="subject" class="form-control" value="{{ old('subject', $emailTemplate->subject ?? '') }}" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $emailTemplate->description ?? '') }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Body *</label>
            <textarea id="content" name="content" class="form-control rich-text" rows="10" required>{{ old('content', $emailTemplate->content ?? '') }}</textarea>
        </div>

        <button type="submit" class="btn btn-success" id="saveButton">Save</button>
    </form>

    {{-- Shortcode Variables --}}
    <div class="mt-5">
        <h5 class="fw-bold">Variables</h5>
        <div class="row text-muted small">
            <div class="col-md-3 mb-2">
                <code>{first_name}</code><br>First name of the user
            </div>
            <div class="col-md-3 mb-2">
                <code>{total_of_times_late}</code><br>Number of times user was late
            </div>
            <div class="col-md-3 mb-2">
                <code>{site_title}</code><br>Your site title
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- ✅ Load TinyMCE Script and Init Inline -->
    <script src="https://cdn.tiny.cloud/1/nd84nj3gfbucyyfu3fobr8s8lgax9x00y378wncd82h3wwmr/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                    setup: function(editor) {
                        editor.on('init', function() {
                            console.log('✅ TinyMCE editor ready:', editor.id);
                            editor.getBody().setAttribute('contenteditable', 'true');
                        });
                    },
                    // Ensure form submission works with TinyMCE
                    setup: function(editor) {
                        editor.on('change', function() {
                            // Sync the editor content back to the textarea before form submission
                            editor.save();
                        });
                    }
                }).then(function(editors) {
                    console.log('✅ TinyMCE editors initialized:', editors.length);
                }).catch(function(error) {
                    console.error('❌ TinyMCE initialization error:', error);
                });
            } else {
                console.error('❌ TinyMCE failed to load. Check the network tab or console for errors.');
            }
        });

        // Add a fallback to ensure form submission
        document.getElementById('emailTemplateForm').addEventListener('submit', function(e) {
            tinymce.triggerSave(); // Sync TinyMCE content to textarea
            console.log('Form submitted, content synced:', document.getElementById('content').value);
        });
    </script>
</div>
@endsection