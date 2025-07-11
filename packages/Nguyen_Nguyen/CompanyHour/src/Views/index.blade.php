@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold text-primary">Company Working Hours</h2>

        @if (!$hour)
            <a href="{{ route('companyhour.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Company Hours
            </a>
        @endif
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if ($hour)
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $hour->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($hour->start_at)->format('H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($hour->end_at)->format('H:i') }}</td>
                            <td class="text-center">
                                <a href="{{ route('companyhour.edit', $hour->id) }}" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <form action="{{ route('companyhour.destroy', $hour->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            @else
                <p class="text-muted text-center">No company hours set yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection
