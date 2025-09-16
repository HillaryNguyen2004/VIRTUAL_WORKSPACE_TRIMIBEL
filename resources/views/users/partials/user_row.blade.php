@php
    $roles = $user->getRoleNames();
    if ($roles->contains('admin')) {
        $role = 'admin';
    } elseif ($roles->contains('staff')) {
        $role = 'staff';
    } elseif ($roles->contains('user')) {
        $role = 'user';
    } else {
        $role = null;
    }
    $isStaff = $role === 'staff';
    $teamLeader = $user->team_leader_id ? $users->firstWhere('id', $user->team_leader_id) : null;
    $teamMembers = isset($user) ? $users->filter(fn($u) => $u->team_leader_id === $user->id) : collect();
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
                <option value="user" {{ $role == 'user' ? 'selected' : '' }}>{{ __('user_row.user_role') }}</option>
                <option value="staff" {{ $role == 'staff' ? 'selected' : '' }}>{{ __('user_row.staff_role') }}</option>
            </select>

            <div id="team-select-{{ $user->id }}" class="{{ $isStaff ? '' : 'd-none' }}">
                <label class="form-label">{{ __('user_row.assign_team_members') }}</label>
                <div id="team-members-wrapper-{{ $user->id }}">
                    @foreach($teamMembers as $member)
                        <div class="d-flex mb-2 align-items-center team-member-select">
                            <select name="team_members[]" class="form-select me-2">
                                <option value="">{{ __('user_row.select_member') }}</option>
                                @foreach($users as $option)
                                    @if($option->getRoleNames()->first() === 'user' && $option->id !== $user->id)
                                        <option value="{{ $option->id }}" {{ $option->id === $member->id ? 'selected' : '' }}>
                                            {{ $option->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTeamMemberField(this)">{{ __('user_row.remove_member') }}</button>
                        </div>
                    @endforeach

                    @if($teamMembers->isEmpty())
                        <div class="d-flex mb-2 align-items-center team-member-select">
                            <select name="team_members[]" class="form-select me-2">
                                <option value="">{{ __('user_row.select_member') }}</option>
                                @foreach($users as $option)
                                    @if($option->getRoleNames()->first() === 'user' && $option->id !== $user->id)
                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeTeamMemberField(this)">{{ __('user_row.remove_member') }}</button>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addTeamMemberField({{ $user->id }})">{{ __('user_row.add_member') }}</button>
            </div>

            <button type="submit" class="btn btn-sm btn-success mt-3">{{ __('user_row.save_button') }}</button>
        </form>

        <!-- View Team Details -->
        <div id="view-team-{{ $user->id }}" class="mt-2 d-none">
            @if($isStaff)
                <strong>{{ __('user_row.team_members_label') }}</strong>
                @if($teamMembers->isEmpty())
                    <p>{{ __('user_row.no_team_members') }}</p>
                @else
                    <ul>
                        @foreach($teamMembers as $member)
                            <li>{{ $member->name }} ({{ $member->email }})</li>
                        @endforeach
                    </ul>
                @endif
            @else
                <strong>{{ __('user_row.team_leader_label') }}</strong>
                @if($teamLeader)
                    <p>{{ $teamLeader->name }} ({{ $teamLeader->email }})</p>
                @else
                    <p>{{ __('user_row.no_team_leader') }}</p>
                @endif
            @endif
        </div>
    </td>
    <td>{{ $user->email }}</td>
    <td><span class="badge bg-secondary">{{ $role ? __('user_row.' . $role . '_role') : __('user_row.no_role') }}</span></td>
    <td>
        <a href="javascript:void(0)" onclick="toggleViewTeam({{ $user->id }})" class="text-info me-2"><i class="bi bi-eye"></i></a>
        <a href="javascript:void(0)" onclick="toggleEditForm({{ $user->id }})" class="text-primary me-2"><i class="bi bi-pencil-square"></i></a>
        <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <button class="btn btn-link text-danger p-0" onclick="return confirm('{{ __('user_row.delete_confirm') }}')"><i class="bi bi-trash"></i></button>
        </form>
    </td>
</tr>