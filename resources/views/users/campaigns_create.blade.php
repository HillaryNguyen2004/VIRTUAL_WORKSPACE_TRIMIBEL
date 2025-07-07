@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">
        {{ isset($campaign) ? 'Edit Campaign' : 'Create New Campaign' }}
    </h1>

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

    @if(isset($campaign) && $campaign->email_template_id)
        <form method="POST" action="{{ route('campaigns.sync-template', $campaign) }}" class="mb-3">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise"></i> Sync from Template
            </button>
        </form>
    @endif


    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ isset($campaign) ? route('campaigns.update', $campaign) : route('campaigns.store') }}">
                @csrf
                @if(isset($campaign))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">Campaign Name</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $campaign->name ?? '') }}"
                           placeholder="Enter campaign name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control"
                           value="{{ old('subject', $campaign->subject ?? '') }}"
                           placeholder="Enter email subject">
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="send_to_all" id="send_to_all" class="form-check-input"
                           {{ old('send_to_all') ? 'checked' : '' }}>
                    <label for="send_to_all" class="form-check-label">Send to all users</label>
                </div>

                <div id="user-select-wrapper" class="mb-3" style="{{ old('send_to_all') ? 'display: none;' : '' }}">
                    <label class="form-label">Select Users</label>
                    <select name="users[]" class="form-select select2" multiple>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ (collect(old('users', isset($campaign) ? $campaign->users->pluck('id')->toArray() : []))->contains($user->id)) ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Template</label>
                    <select name="email_template_id" class="form-select">
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}"
                                {{ old('email_template_id', $campaign->email_template_id ?? '') == $template->id ? 'selected' : '' }}>
                                {{ $template->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="scheduled_at" class="form-label">Schedule At (optional)</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control"
                           value="{{ old('scheduled_at', isset($campaign->scheduled_at) ? \Carbon\Carbon::parse($campaign->scheduled_at)->format('Y-m-d\TH:i') : '') }}">
                    <div class="form-text">Leave empty to send manually later.</div>
                </div>

                <button type="submit" class="btn btn-success mt-3">
                    <i class="bi bi-send"></i> {{ isset($campaign) ? 'Update Campaign' : 'Save & Schedule' }}
                </button>
            </form>

            @if(isset($campaign) && $campaign->email_template_id)
                <form action="{{ route('campaigns.syncTemplate', $campaign->id) }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-repeat"></i> Sync with Template
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Select users",
            width: '100%'
        });

        // Show/hide user selection on toggle
        $('#send_to_all').on('change', function () {
            $('#user-select-wrapper').toggle(!this.checked);
        });
    });
</script>
@endsection
