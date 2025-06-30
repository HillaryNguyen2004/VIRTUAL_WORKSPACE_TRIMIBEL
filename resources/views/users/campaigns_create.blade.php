@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">Create New Campaign</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('campaigns.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Campaign Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter campaign name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Enter email subject">
                </div>

                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea name="content" rows="5" class="form-control" placeholder="Write your email content"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assign Team Members</label>
                    <div id="user-select-wrapper">
                        <div class="d-flex mb-2 user-select-row">
                            <select name="users[]" class="form-select me-2 select2" required>
                                <option value="" disabled selected>Select a user</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-danger remove-member-btn">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary mt-2" id="add-user-btn">
                        <i class="bi bi-plus"></i> Add Member
                    </button>
                </div>
                <!-- <select name="email_template_id" class="form-select">
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" {{ old('email_template_id', $campaign->email_template_id ?? '') == $template->id ? 'selected' : '' }}>
                            {{ $template->name }}
                        </option>
                    @endforeach
                </select> -->

                <button type="submit" class="btn btn-success mt-3">
                    <i class="bi bi-send"></i> Save & Schedule
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        // Initial select2
        $('.select2').select2({
            placeholder: "Select user",
            width: '100%'
        });

        // Add new user row
        $('#add-user-btn').on('click', function () {
            let newRow = $('.user-select-row:first').clone();

            // Clear selected value
            newRow.find('select').val(null).trigger('change');

            // Destroy select2 before appending clone
            newRow.find('.select2').select2('destroy');

            // Append and re-apply select2
            $('#user-select-wrapper').append(newRow);
            $('#user-select-wrapper .select2').select2({
                placeholder: "Select user",
                width: '100%'
            });
        });

        // Remove user row
        $(document).on('click', '.remove-member-btn', function () {
            if ($('.user-select-row').length > 1) {
                $(this).closest('.user-select-row').remove();
            }
        });
    });
</script>

@endsection
