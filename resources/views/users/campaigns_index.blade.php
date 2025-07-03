@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">{{ __('Campaign List') }}</h1>

    <div class="mb-3">
        <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
            <i class="bi bi-envelope-plus"></i> Create New Campaign
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
        @if($campaigns->isEmpty())
            <p class="text-muted">No campaigns found.</p>
        @else
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Campaign Name</th>
                        <th>Subject</th>
                        <th>Scheduled At</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th>Actions</th>
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
                                    <span class="badge bg-success">Sent</span>
                                @elseif($campaign->scheduled_at && $campaign->scheduled_at->isFuture())
                                    <span class="badge bg-info">Scheduled</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td>
                                @foreach ($campaign->users as $user)
                                    <span class="badge bg-secondary">{{ $user->name }}</span>
                                @endforeach
                            </td>
                            <td class="d-flex gap-1">
                                <a href="{{ route('campaigns.edit', $campaign->id) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('campaigns.destroy', $campaign->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                                @if(!$campaign->sent)
                                    <form action="{{ route('campaigns.sendNow', $campaign->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success" onclick="return confirm('Send this campaign now?')">
                                            <i class="bi bi-send"></i> Send Now
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('campaigns.reset', $campaign->id) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Reset send status?')">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        </div>
    </div>
</div>
@endsection