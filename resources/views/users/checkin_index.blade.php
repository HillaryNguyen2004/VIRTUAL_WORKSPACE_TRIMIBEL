@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
@role('admin')
<div class="container py-4">
    <h2 class="mb-4 fw-bold">All Check-In Logs</h2>

    <!-- Search + Filter -->
    <form method="GET" action="{{ route('users.checkin_index') }}" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" name="username" value="{{ request('username') }}" class="form-control" placeholder="Search by username...">
        </div>
        <div class="col-md-4">
            <input type="date" name="date" value="{{ request('date') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Filter</button>
        </div>
    </form>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>User Name</th>
                        <th>Date</th>
                        <th>Check In Time</th>
                        <th>Check Out Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checkIns as $index => $log)
                        <tr>
                            <td>{{ $checkIns->firstItem() + $index }}</td>
                            <td>{{ $log->user_name }}</td>
                            <td>{{ $log->date }}</td>
                            <td>{{ $log->check_in_time ?? '-' }}</td>
                            <td>{{ $log->check_out_time ?? '-' }}</td>
                            <td>
                                @if($log->check_in_time && !$log->check_out_time)
                                    <span class="badge bg-warning text-dark">Checked In</span>
                                @elseif($log->check_in_time && $log->check_out_time)
                                    <span class="badge bg-success">Checked Out</span>
                                @else
                                    <span class="badge bg-secondary">Not Checked In</span>
                                @endif
                            </td>
                            <td>
                                @if($log->is_late)
                                    <span class="badge bg-danger">Late</span>
                                @else
                                    <span class="badge bg-success">On Time</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No check-in records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $checkIns->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@else
<div class="container py-4">
    <h4 class="text-danger">Access Denied</h4>
    <p>You do not have permission to view this page.</p>
</div>
@endrole
@endsection
