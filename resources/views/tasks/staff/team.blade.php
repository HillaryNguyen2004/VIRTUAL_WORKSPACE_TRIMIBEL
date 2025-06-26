@extends('layouts.app')

@section('content')

@role('staff')
<div class="container py-4">
    <h2 class="mb-4">My Team</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($teamMembers->isEmpty())
        <p class="text-muted">You don't have any team members yet.</p>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    @foreach($teamMembers as $member)
        <div class="col">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-primary fw-bold mb-1">{{ $member->name }}</h5>
                    <p class="card-subtitle mb-3 text-muted">{{ $member->email }}</p>

                    <form action="{{ route('team.assignTask') }}" method="POST" class="mt-auto">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $member->id }}">
                        <div class="mb-2">
                            <select name="task_id" class="form-select" required>
                                <option value="">Select a task</option>
                                @foreach($staffTasks as $task)
                                    <option value="{{ $task->task_id }}">{{ $task->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            Assign Task
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

    @endif
</div>
@else
<div class="container py-4">
    <h3 class="text-danger">Access Denied</h3>
    <p>You do not have permission to view this page.</p>
</div>
@endrole

@endsection
