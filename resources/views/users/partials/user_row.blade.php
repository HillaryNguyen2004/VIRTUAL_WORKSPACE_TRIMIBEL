@php
    $role = $user->getRoleNames()->first();
    $isStaff = $role === 'staff';
    $teamLeader = $user->team_leader_id ? $users->firstWhere('id', $user->team_leader_id) : null;
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

        <!-- View Team Details -->
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
    <td><span class="badge bg-secondary">{{ $role ?? 'None' }}</span></td>
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
