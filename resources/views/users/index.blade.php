@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">User Management</h1>

    <div class="card p-4 mb-4">
        <!-- FILTER FORM -->
        <form class="row g-3 mb-3" method="GET" action="{{ route('users.index') }}">
            <div class="col-md-4">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search username or email">
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>staff</option>
                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>user</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>

        <!-- USER TABLE -->
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
                    <td>
                        {{ $user->name }}
                        <form id="edit-form-{{ $user->id }}" action="{{ route('users.update', $user->id) }}" method="POST" class="mt-2 d-none">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $user->name }}" class="form-control mb-2" placeholder="Change name">

                            <select name="roles" class="form-select mb-2" onchange="toggleTeamSelect(this, {{ $user->id }})">
                                <option value="user" {{ $user->roles == 'user' ? 'selected' : '' }}>user</option>
                                <option value="staff" {{ $user->roles == 'staff' ? 'selected' : '' }}>staff</option>
                            </select>

                            <div id="team-select-{{ $user->id }}" class="{{ $user->roles == 'staff' ? '' : 'd-none' }}">
                                <label class="form-label">Assign Team Members</label>
                                <div id="team-members-wrapper-{{ $user->id }}">
                                    @php
                                        $assignedMembers = $users->filter(fn($u) => $u->team_leader_id == $user->id);
                                    @endphp
                                    @foreach($assignedMembers as $member)
                                        <div class="d-flex mb-2 align-items-center team-member-select">
                                            <select name="team_members[]" class="form-select me-2">
                                                <option value="">-- Select Member --</option>
                                                @foreach($users as $potentialMember)
                                                    @if($potentialMember->roles == 'user' && $potentialMember->id != $user->id)
                                                        <option value="{{ $potentialMember->id }}" {{ $member->id == $potentialMember->id ? 'selected' : '' }}>
                                                            {{ $potentialMember->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTeamMemberField(this)">🗑</button>
                                        </div>
                                    @endforeach

                                    @if($assignedMembers->isEmpty())
                                        <div class="d-flex mb-2 align-items-center team-member-select">
                                            <select name="team_members[]" class="form-select me-2">
                                                <option value="">-- Select Member --</option>
                                                @foreach($users as $potentialMember)
                                                    @if($potentialMember->roles == 'user' && $potentialMember->id != $user->id)
                                                        <option value="{{ $potentialMember->id }}">{{ $potentialMember->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTeamMemberField(this)">🗑</button>
                                        </div>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addTeamMemberField({{ $user->id }})">➕ Add Member</button>
                            </div>

                            <button type="submit" class="btn btn-sm btn-success mt-3">Save</button>
                        </form>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge" style="background:#{{ $user->roles == 'staff' ? 'e0d7fb' : 'e0f7e9' }};color:#{{ $user->roles == 'staff' ? 'a259f7' : '3bb77e' }};">
                            {{ $user->roles }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                    </td>
                    <td>Never</td>
                    <td>
                        <a href="javascript:void(0)" onclick="toggleEditForm({{ $user->id }})" class="text-primary me-2" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>

                        <a href="javascript:void(0)" onclick="toggleUserDetails({{ $user->id }})" class="text-info me-2" title="View Details">
                            <i class="bi bi-info-circle"></i>
                        </a>

                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link text-danger p-0" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                <!-- DROPDOWN INFO ROW -->
                <tr id="user-details-{{ $user->id }}" class="d-none">
                    <td colspan="6">
                        <div class="bg-light p-3 rounded">
                            @if($user->roles === 'user')
                                @php
                                    $leader = $users->firstWhere('id', $user->team_leader_id);
                                @endphp
                                <strong>Team Leader:</strong>
                                @if($leader)
                                    {{ $leader->name }} ({{ $leader->email }})
                                @else
                                    <span class="text-muted">No leader assigned</span>
                                @endif
                            @elseif($user->roles === 'staff')
                                @php
                                    $members = $users->filter(fn($u) => $u->team_leader_id == $user->id);
                                @endphp
                                <strong>Team Members:</strong>
                                @if($members->isNotEmpty())
                                    <ul class="mb-0">
                                        @foreach($members as $member)
                                            <li>{{ $member->name }} ({{ $member->email }})</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted">No members assigned</span>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- PAGINATION -->
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

<!-- JAVASCRIPT -->
<script>
    function toggleEditForm(userId) {
        const form = document.getElementById(`edit-form-${userId}`);
        form.classList.toggle('d-none');
    }

    function toggleTeamSelect(select, userId) {
        const teamDiv = document.getElementById(`team-select-${userId}`);
        if (select.value === 'staff') {
            teamDiv.classList.remove('d-none');
        } else {
            teamDiv.classList.add('d-none');
        }
    }

    function addTeamMemberField(userId) {
        const wrapper = document.getElementById(`team-members-wrapper-${userId}`);
        const container = document.createElement('div');
        container.className = 'd-flex mb-2 align-items-center team-member-select';

        const select = document.createElement('select');
        select.name = 'team_members[]';
        select.className = 'form-select me-2';
        select.innerHTML = `<option value="">-- Select Member --</option>
            @foreach($users as $u)
                @if($u->roles == 'user')
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endif
            @endforeach`;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-outline-danger btn-sm';
        removeBtn.innerText = '🗑';
        removeBtn.onclick = function () {
            container.remove();
        };

        container.appendChild(select);
        container.appendChild(removeBtn);
        wrapper.appendChild(container);
    }

    function removeTeamMemberField(button) {
        button.closest('.team-member-select').remove();
    }

    function toggleUserDetails(userId) {
        const row = document.getElementById(`user-details-${userId}`);
        row.classList.toggle('d-none');
    }
</script>
@endsection
