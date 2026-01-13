@props(['users' => collect(), 'user' => null])

@php
    $userId = optional($user)->id;
    $roles = $user ? $user->getRoleNames() : collect();
    if ($roles->contains('admin')) {
        $role = 'admin';
    } elseif ($roles->contains('staff')) {
        $role = 'staff';
    } else {
        $role = 'user'; // default
    }

    $isStaff = $role === 'staff';
    $teamLeader = $user?->team_leader_id ? $users->firstWhere('id', $user->team_leader_id) : null;
    $teamMembers = $user ? $users->filter(fn($u) => $u->team_leader_id == $userId) : collect();
    $teamOptions = $users
        ->filter(fn($u) => $u->getRoleNames()->first() === 'user' && $u->id !== $userId)
        ->mapWithKeys(fn($u) => [$u->id => $u->name])
        ->toArray();
@endphp

<div id="update-user-dialog" class="hidden items-center justify-center fixed h-screen w-screen bg-black/50 z-50">

    <div class="flex flex-col w-[320px] sm:w-[450px] lg:w-[500px] bg-white rounded-2xl shadow-xl animate-fade-in-up [animation-delay:150ms] overflow-hidden">
        
        <div class="w-full px-6 py-4 flex items-center justify-between border-b border-muted-200 bg-white">
            <h2 class="text-lg font-bold text-main">{{ __('update_user.title') }}</h2>
            <button class="close-update-user p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-form-{{ $userId }}" action="{{ route('users.update', $userId) }}" method="POST"
            class="flex flex-col w-full">
            @csrf
            @method('PUT')

            <div class="p-6 flex flex-col gap-5 w-full">
                
                {{-- Name Input --}}
                <x-form.input
                    label="update_user.full_name_label"
                    name="name"
                    id="name"
                    :value="$user->name"
                />

                {{-- Role Select --}}
                <x-form.select
                    label="update_user.role_label"
                    name="role"
                    id="role"
                    :value="$role"
                    :options="[
                        'user'  => __('user_row.user_role'),
                        'staff' => __('user_row.staff_role'),
                        'admin' => __('user_row.admin_role'),
                    ]"
                />

                {{-- Team Members --}}
                <div id="team-select-{{ $userId }}" class="flex flex-col gap-1.5 w-full">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-main">{{ __('user_row.assign_team_members') }}</label>
                        {{-- Add Member Button here if you have --}}
                    </div>

                    <div id="team-members-wrapper-{{ $userId }}" class="flex flex-col gap-3 w-full max-h-48 overflow-y-auto pr-1">

                        @forelse($teamMembers as $member)
                            <div class="team-member-select flex gap-2 w-full items-center">
                                <div class="w-full">
                                    <x-form.select
                                        name="team_members[]"
                                        placeholder="user_row.select_member"
                                        :value="$member->id"
                                        :options="$teamOptions"
                                    />
                                </div>

                                <button type="button"
                                    class="remove-member-field-btn p-2.5 rounded-xl text-muted-400 hover:text-danger hover:bg-danger/10 transition-colors flex-shrink-0"
                                    title="{{ __('tasks.delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <div class="team-member-select flex gap-2 w-full items-center">
                                <div class="w-full">
                                    <x-form.select
                                        name="team_members[]"
                                        placeholder="user_row.select_member"
                                        :value="null"
                                        :options="$teamOptions"
                                    />
                                </div>

                                <button type="button"
                                    class="remove-member-field-btn p-2.5 rounded-xl text-muted-400 hover:text-danger hover:bg-danger/10 transition-colors flex-shrink-0"
                                    title="{{ __('tasks.delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endforelse

                    </div>
                </div>

                {{-- Footer / Actions --}}
                <div id="footer-btn" class="flex flex-col sm:flex-row justify-between items-center gap-3 w-full pt-2">
                     <button id="add-member-btn" type="button"
                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-primary text-primary text-sm font-medium hover:bg-primary hover:text-white transition-all active:scale-95 flex items-center justify-center gap-2"
                        onclick="addTeamMemberField({{ $userId }})">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>{{ __('user_row.add_member') }}</span>
                    </button>

                    <div class="flex flex-col-reverse sm:flex-row gap-3 w-full sm:w-auto">
                        <button type="button"
                            class="close-update-user w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-medium text-muted-600 hover:bg-muted-100 transition-colors">
                            {{ __('app.cancel') }}
                        </button>
                        <button id="update-user-submit" type="button"
                            class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-bold rounded-xl shadow-lg shadow-primary/25 transition-all active:scale-95">
                            <svg data-spinner class="hidden w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span>{{ __('update_user.update_btn') }}</span>
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
    window.i18n = Object.assign(window.i18n || {}, {
        select_member: @json(__('user_row.select_member')),
    });
</script>