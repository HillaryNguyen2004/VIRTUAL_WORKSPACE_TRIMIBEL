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
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Member Name</th>
                    <th>Email</th>
                    <th>Assign Task</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teamMembers as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>
                            <form action="{{ route('team.assignTask') }}" method="POST" class="d-flex align-items-center">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $member->id }}">
                                <select name="task_id" class="form-select me-2" required>
                                    <option value="">Select a task</option>
                                    @foreach($staffTasks as $task)
                                        <option value="{{ $task->task_id }}">{{ $task->title }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">
                                    Assign
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@else
<div class="container py-4">
    <h3 class="text-danger">Access Denied</h3>
    <p>You do not have permission to view this page.</p>
</div>
@endrole

@endsection
