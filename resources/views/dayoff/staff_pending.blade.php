@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Pending Day-Off Requests</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @forelse($requests as $req)
        <div class="card mb-3">
            <div class="card-body">
                <p><strong>User:</strong> {{ $req->user->name }}</p>
                <p><strong>Date:</strong> {{ $req->date }}</p>
                <p><strong>Type:</strong> {{ $req->leave_type }}</p>
                <p><strong>Reason:</strong> {{ $req->reason ?? 'N/A' }}</p>

                <div class="d-flex">
                    <form action="{{ route('dayoff.approve', $req->id) }}" method="POST" class="me-2">
                        @csrf
                        <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form action="{{ route('dayoff.reject', $req->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <p class="text-muted">No pending requests.</p>
    @endforelse
</div>
@endsection
