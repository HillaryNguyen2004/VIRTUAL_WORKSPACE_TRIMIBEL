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
    $teamMembers = isset($user) ? $users->filter(fn($u) => $u->team_leader_id === $userId) : collect();
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
                <div class="flex flex-col gap-1.5 w-full">
                    <label for="name" class="text-sm font-medium text-main">{{ __('update_user.full_name_label') }}</label>
                    <input type="text" name="name" value="{{ $user->name }}"
                        class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main placeholder-muted-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>

                {{-- Role Select --}}
                <div class="flex flex-col gap-1.5 w-full">
                    <label for="role" class="text-sm font-medium text-main">{{ __('update_user.role_label') }}</label>
                    <div class="relative">
                        <select name="role"
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                            <option value="user" {{ $role == 'user' ? 'selected' : '' }}>{{ __('user_row.user_role') }}</option>
                            <option value="staff" {{ $role == 'staff' ? 'selected' : '' }}>{{ __('user_row.staff_role') }}</option>
                            <option value="admin" {{ $role == 'admin' ? 'selected' : '' }}>{{ __('user_row.admin_role') }}</option>
                        </select>
                        {{-- Custom Chevron --}}
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                {{-- Team Members --}}
                <div id="team-select-{{ $userId }}" class="flex flex-col gap-1.5 w-full">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-main">{{ __('user_row.assign_team_members') }}</label>
                        {{-- Add Member Button (moved to label line or kept at bottom - kept logic same but styled) --}}
                    </div>
                    
                    <div id="team-members-wrapper-{{ $userId }}" class="flex flex-col gap-3 w-full max-h-48 overflow-y-auto pr-1">
                        @foreach($teamMembers as $member)
                            <div class="team-member-select flex gap-2 w-full items-center">
                                <div class="relative w-full">
                                    <select name="team_members[]"
                                        class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                        <option value="">{{ __('user_row.select_member') }}</option>
                                        @foreach($users as $option)
                                            @if($option->getRoleNames()->first() === 'user' && $option->id !== $userId)
                                                <option value="{{ $option->id }}" {{ $option->id === $member->id ? 'selected' : '' }}>
                                                    {{ $option->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                                <button id="remove-member-field-btn" type="button"
                                    class="p-2.5 rounded-xl text-muted-400 hover:text-danger hover:bg-danger/10 transition-colors flex-shrink-0" 
                                    title="{{ __('tasks.delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach

                        @if($teamMembers->isEmpty())
                            <div class="team-member-select flex gap-2 w-full items-center">
                                <div class="relative w-full">
                                    <select name="team_members[]"
                                        class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                        <option value="">{{ __('user_row.select_member') }}</option>
                                        @foreach($users as $option)
                                            @if($option->getRoleNames()->first() === 'user' && $option->id !== $userId)
                                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>
                                <button id="remove-member-field-btn" type="button"
                                    class="p-2.5 rounded-xl text-muted-400 hover:text-danger hover:bg-danger/10 transition-colors flex-shrink-0" 
                                    title="{{ __('tasks.delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endif
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