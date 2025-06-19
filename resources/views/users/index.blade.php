@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">User Management</h1>

    <div class="card p-4 mb-4">
        <form class="row g-3 mb-3" method="GET" action="{{ route('users.index') }}">
            <div class="col-md-4">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search username or email">
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>User</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>

        <table class="table align-middle">
            <thead>
                <tr>
                    <th>USERNAME</th>
                    <th>EMAIL</th>
                    <th>ROLE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    @php
                        $role = $user->getRoleNames()->first(); // Spatie method
                        $isStaff = $role === 'staff';
                        // Find team leader for 'user' role
                        $teamLeader = $user->team_leader_id ? $users->firstWhere('id', $user->team_leader_id) : null;
                        // Find team members for 'staff' role
                        $teamMembers = $users->filter(fn($u) => $u->team_leader_id === $user->id);
                    @endphp
                    <tr>
                        <td>
                            {{ $user->name }}

                            <!-- Edit Form -->
                            <form id="edit-form-{{ $user->id }}" action="{{ route('users.update', $user->id) }}" method="POST" class="mt-2 d-none">
                                @csrf
                                @method('PUT')

                                <input type="text" name="name" value="{{ $user->name }}" class="form-control mb-2">

                                <select name="role" class="form-select mb-2" onchange="toggleTeamSelect(this, {{ $user->id }})">
                                    <option value="user" {{ $role == 'user' ? 'selected' : '' }}>User</option>
                                    <option value="staff" {{ $role == 'staff' ? 'selected' : '' }}>Staff</option>
                                </select>

                                <div id="team-select-{{ $user->id }}" class="{{ $isStaff ? '' : 'd-none' }}">
                                    <label class="form-label">Assign Team Members</label>
                                    <div id="team-members-wrapper-{{ $user->id }}">
                                        @foreach($teamMembers as $member)
                                            <div class="d-flex mb-2 align-items-center team-member-select">
                                                <select name="team_members[]" class="form-select me-2">
                                                    <option value="">-- Select Member --</option>
                                                    @foreach($users as $option)
                                                        @if($option->getRoleNames()->first() === 'user' && $option->id !== $user->id)
                                                            <option value="{{ $option->id }}" {{ $option->id === $member->id ? 'selected' : '' }}>
                                                                {{ $option->name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTeamMemberField(this)">🗑</button>
                                            </div>
                                        @endforeach

                                        @if($teamMembers->isEmpty())
                                            <div class="d-flex mb-2 align-items-center team-member-select">
                                                <select name="team_members[]" class="form-select me-2">
                                                    <option value="">-- Select Member --</option>
                                                    @foreach($users as $option)
                                                        @if($option->getRoleNames()->first() === 'user' && $option->id !== $user->id)
                                                            <option value="{{ $option->id }}">{{ $option->name }}</option>
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

                            <!-- View Team Details Section -->
                            <div id="view-team-{{ $user->id }}" class="mt-2 d-none">
                                @if($isStaff)
                                    <strong>Team Members:</strong>
                                    @if($teamMembers->isEmpty())
                                        <p>No team members assigned.</p>
                                    @else
                                        <ul>
                                            @foreach($teamMembers as $member)
                                                <li>{{ $member->name }} ({{ $member->email }})</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @else
                                    <strong>Team Leader:</strong>
                                    @if($teamLeader)
                                        <p>{{ $teamLeader->name }} ({{ $teamLeader->email }})</p>
                                    @else
                                        <p>No team leader assigned.</p>
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $role ?? 'None' }}</span>
                        </td>
                        <td>
                            <a href="javascript:void(0)" onclick="toggleViewTeam({{ $user->id }})" class="text-info me-2"><i class="bi bi-eye"></i></a>
                            <a href="javascript:void(0)" onclick="toggleEditForm({{ $user->id }})" class="text-primary me-2"><i class="bi bi-pencil-square"></i></a>
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link text-danger p-0" onclick="return confirm('Delete user?')"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleEditForm(id) {
    const editForm = document.getElementById(`edit-form-${id}`);
    const viewTeam = document.getElementById(`view-team-${id}`);
    editForm.classList.toggle('d-none');
    // Hide view section if edit form is shown
    if (!editForm.classList.contains('d-none')) {
        viewTeam.classList.add('d-none');
    }
}

function toggleViewTeam(id) {
    const viewTeam = document.getElementById(`view-team-${id}`);
    const editForm = document.getElementById(`edit-form-${id}`);
    viewTeam.classList.toggle('d-none');
    // Hide edit form if view section is shown
    if (!viewTeam.classList.contains('d-none')) {
        editForm.classList.add('d-none');
    }
}

function toggleTeamSelect(select, userId) {
    const div = document.getElementById(`team-select-${userId}`);
    div.classList.toggle('d-none', select.value !== 'staff');
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
            @if($u->getRoleNames()->first() == 'user')
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endif
        @endforeach`;

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-outline-danger btn-sm';
    removeBtn.innerText = '🗑';
    removeBtn.onclick = () => container.remove();

    container.appendChild(select);
    container.appendChild(removeBtn);
    wrapper.appendChild(container);
}

function removeTeamMemberField(button) {
    button.closest('.team-member-select').remove();
}
</script>
@endsection