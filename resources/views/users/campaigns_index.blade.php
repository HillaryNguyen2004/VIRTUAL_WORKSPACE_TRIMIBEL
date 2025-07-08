@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">{{ __('campaigns.title') }}</h1>

    <div class="mb-3">
        <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
            <i class="bi bi-envelope-plus"></i> {{ __('campaigns.create_new_campaign') }}
        </a>
    </div>

    <form method="GET" action="{{ route('campaigns.index') }}" class="mb-3 row">
        <div class="col-md-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="sort" class="form-control">
                <option value="desc" {{ request('sort') == 'desc' ? 'selected' : '' }}>Newest First</option>
                <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Oldest First</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>



    <div class="card shadow-sm">
        <div class="card-body">
        @if($campaigns->isEmpty())
            <p class="text-muted">{{ __('campaigns.no_campaigns_found') }}</p>
        @else
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('campaigns.id') }}</th>
                        <th>{{ __('campaigns.name') }}</th>
                        <th>{{ __('campaigns.subject') }}</th>
                        <th>{{ __('campaigns.scheduled_at') }}</th>
                        <th>{{ __('campaigns.status') }}</th>
                        <th>{{ __('campaigns.users') }}</th>
                        <th>{{ __('campaigns.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaigns as $campaign)
                        <tr>
                            <td>{{ $campaign->id }}</td>
                            <td>{{ $campaign->name }}</td>
                            <td>{{ $campaign->subject }}</td>
                            <td>{{ $campaign->scheduled_at ? $campaign->scheduled_at->format('Y-m-d H:i') : 'N/A' }}</td>
                            <td>
                                @if($campaign->sent)
                                    <span class="badge bg-success">{{ __('campaigns.sent') }}</span>
                                @elseif($campaign->scheduled_at && $campaign->scheduled_at->isFuture())
                                    <span class="badge bg-info">{{ __('campaigns.scheduled') }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ __('campaigns.pending') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="position-relative">
                                    <button type="button" class="btn btn-sm btn-outline-primary toggle-user-list" data-id="{{ $campaign->id }}">
                                        {{ $campaign->users->count() }} {{ Str::plural('User', $campaign->users->count()) }}
                                    </button>

                                    <div id="user-list-{{ $campaign->id }}" class="mt-2 border rounded bg-light p-2 shadow-sm user-list" style="display: none;">
                                        @foreach ($campaign->users as $user)
                                            <span class="badge bg-secondary me-1 mb-1">{{ $user->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                            <td class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('campaigns.edit', $campaign->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('campaigns.destroy', $campaign->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('{{ __('campaigns.confirm_delete') }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                                @if(!$campaign->sent)
                                    <form action="{{ route('campaigns.sendNow', $campaign->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success" onclick="return confirm('{{ __('campaigns.confirm_send') }}')">
                                            <i class="bi bi-send"></i> {{ __('campaigns.send_now') }}
                                        </button>
                                    </form>
                                @endif

                                @if($campaign->sent)
                                    <form method="POST" action="{{ route('campaigns.reset', $campaign->id) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('{{ __('campaigns.confirm_reset') }}')">
                                            <i class="bi bi-arrow-counterclockwise"></i> {{ __('campaigns.reset') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-center mt-3">
                {{ $campaigns->links() }}
            </div>
        @endif
        </div>
    </div>
</div>

{{-- Style for smooth appearance --}}
<style>
    .user-list {
        transition: all 0.3s ease-in-out;
        max-width: 300px;
        word-wrap: break-word;
    }
</style>

{{-- Toggle user list JS --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-user-list').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const id = this.dataset.id;
                const target = document.getElementById('user-list-' + id);

                // Hide others
                document.querySelectorAll('.user-list').forEach(div => {
                    if (div !== target) div.style.display = 'none';
                });

                // Toggle selected
                if (target) {
                    target.style.display = (target.style.display === 'none' || target.style.display === '') ? 'block' : 'none';
                }
            });
        });

        // Hide on click outside
        document.addEventListener('click', function () {
            document.querySelectorAll('.user-list').forEach(div => div.style.display = 'none');
        });
    });
</script>
@endsection