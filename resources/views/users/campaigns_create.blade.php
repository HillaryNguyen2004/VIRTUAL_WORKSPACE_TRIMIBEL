@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">
        {{ isset($campaign) ? __('campaigns_create.edit_campaign') : __('campaigns_create.create_new_campaign') }}
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

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ isset($campaign) ? route('campaigns.update', $campaign) : route('campaigns.store') }}">
                @csrf
                @if(isset($campaign))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">{{ __('campaigns_create.campaign_name') }}</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $campaign->name ?? '') }}"
                           placeholder="{{ __('campaigns_create.enter_campaign_name') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('campaigns_create.subject') }}</label>
                    <input type="text" name="subject" class="form-control"
                           value="{{ old('subject', $campaign->subject ?? '') }}"
                           placeholder="{{ __('campaigns_create.enter_email_subject') }}">
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="send_to_all" id="send_to_all" class="form-check-input"
                           {{ old('send_to_all') ? 'checked' : '' }}>
                    <label for="send_to_all" class="form-check-label">{{ __('campaigns_create.send_to_all_users') }}</label>
                </div>

                <div id="user-select-wrapper" class="mb-3" style="{{ old('send_to_all') ? 'display: none;' : '' }}">
                    <label class="form-label">{{ __('campaigns_create.select_users') }}</label>
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
                    <label class="form-label">{{ __('campaigns_create.email_template') }}</label>
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
                    <label for="scheduled_at" class="form-label">{{ __('campaigns_create.schedule_at') }}</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control"
                           value="{{ old('scheduled_at', isset($campaign->scheduled_at) ? \Carbon\Carbon::parse($campaign->scheduled_at)->format('Y-m-d\TH:i') : '') }}">
                    <div class="form-text">{{ __('campaigns_create.schedule_at_hint') }}</div>
                </div>

                <button type="submit" class="btn btn-success mt-3">
                    <i class="bi bi-send"></i> {{ isset($campaign) ? __('campaigns_create.update_campaign') : __('campaigns_create.save_and_schedule') }}
                </button>
            </form>

            @if(isset($campaign) && $campaign->email_template_id)
                <form action="{{ route('campaigns.syncTemplate', $campaign->id) }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-repeat"></i> {{ __('campaigns_create.sync_with_template') }}
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
            placeholder: "{{ __('campaigns_create.select_users_placeholder') }}",
            width: '100%'
        });

        // Show/hide user selection on toggle
        $('#send_to_all').on('change', function () {
            $('#user-select-wrapper').toggle(!this.checked);
        });
    });
</script>
@endsection