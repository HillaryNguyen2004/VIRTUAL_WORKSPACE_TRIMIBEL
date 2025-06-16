@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">My Tasks</h1>
    <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>All Tasks Assigned to Me</strong>
        </div>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>TASK ID</th>
                    <th>TASK NAME</th>
                    <th>DUE DATE</th>
                    <th>STATUS</th>
                    <th>ACTIVE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                <tr data-bs-toggle="collapse" data-bs-target="#taskDetails{{ $task->task_id }}" aria-expanded="false" style="cursor:pointer;">
                    <td>{{ $task->task_id }}</td>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->due_date }}</td>
                    <td>
                        @if($task->status === 'pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($task->status === 'in_progress')
                            <span class="badge bg-info text-dark">In Progress</span>
                        @elseif($task->status === 'completed')
                            <span class="badge bg-success">Completed</span>
                        @endif
                    </td>
                    <td>
                        @if($task->active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="#" title="View">
                            <i class="bi bi-eye text-primary fs-5"></i>
                        </a>
                    </td>
                </tr>
                <tr class="collapse" id="taskDetails{{ $task->task_id }}">
                    <td colspan="6" class="bg-light">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Description:</strong>
                                <div>{{ $task->description ?? 'No description' }}</div>
                            </div>
                            <div class="col-md-6">
                                <strong>Tags:</strong>
                                <div>
                                    @if(!empty($task->tags))
                                        @foreach(json_decode($task->tags, true) as $tag)
                                            <span class="badge bg-primary">{{ $tag }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No tags</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection