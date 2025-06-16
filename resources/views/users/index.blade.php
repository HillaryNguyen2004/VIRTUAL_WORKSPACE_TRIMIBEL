@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">User Management</h1>
    <div class="card p-4 mb-4">
        <form class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Search username or email">
            </div>
            <div class="col-md-2">
                <select class="form-select">
                    <option>All Roles</option>
                    <option>admin</option>
                    <option>staff</option>
                    <option>student</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select">
                    <option>All Status</option>
                    <option>Active</option>
                    <option>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Search</button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" type="reset">Reset</button>
            </div>
        </form>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>USERNAME</th>
                    <th>EMAIL</th>
                    <th>ROLE</th>
                    <th>STATUS</th>
                    <th>LAST LOGIN</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->roles == 'staff')
                            <span class="badge" style="background:#e0d7fb;color:#a259f7;">staff</span>
                        @else
                            <span class="badge" style="background:#e0f7e9;color:#3bb77e;">user</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                    </td>
                    <td>
                        {{-- Replace with your actual last login logic --}}
                        Never
                    </td>
                    <td>
                        <a href="#" class="text-primary me-2" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="#" class="text-danger" title="Delete"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>Showing 1 to {{ $users->count() }} of {{ $users->count() }} results</div>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item disabled"><a class="page-link">Previous</a></li>
                    <li class="page-item disabled"><a class="page-link">Next</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>
@endsection