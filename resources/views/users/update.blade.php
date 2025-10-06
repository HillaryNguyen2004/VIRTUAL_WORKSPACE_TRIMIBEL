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

<div id="update-user-dialog" class="hidden items-center justify-center fixed h-screen w-screen bg-black/20 z-50">

    <!-- panel -->
    <div
        class="flex flex-col items-center w-[280px] sm:w-[300px] md:w-[400px] lg:w-[500px] h-fit bg-[#FDFDFF] rounded-2xl shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] animate-fade-in-up [animation-delay:150ms]">
        <!-- title -->
        <div class="w-full py-3 text-center md:text-xl bg-[#F1EFFC] text-[#5D3FD3] font-medium rounded-t-2xl relative">
            <h2>{{ __('update_user.title') }}</h2>
            <button class="close-update-user absolute top-2 right-5 p-2 rounded-full hover:bg-violet-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                    class="w-4 h-4 md:w-5 md:h-5 fill-[#5D3FD3]">
                    <path
                        d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z" />
                </svg>
            </button>
        </div>

        <!-- Edit Form -->
        <form id="edit-form-{{ $userId }}" action="{{ route('users.update', $userId) }}" method="POST"
            class="flex flex-col gap-6 w-full p-6">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-2 w-full text-sm md:text-base">
                <label for="name">{{ __('update_user.full_name_label') }}</label>
                <input type="text" name="name" value="{{ $user->name }}"
                    class="rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
            </div>
            <div class="flex flex-col gap-2 w-full text-sm md:text-base">
                <label for="role">{{ __('update_user.role_label') }}</label>
                <select name="role"
                    class="rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                    <option value="user" {{ $role == 'user' ? 'selected' : '' }}>{{ __('user_row.user_role') }}</option>
                    <option value="staff" {{ $role == 'staff' ? 'selected' : '' }}>{{ __('user_row.staff_role') }}
                    </option>
                    <option value="admin" {{ $role == 'admin' ? 'selected' : '' }}>{{ __('user_row.admin_role') }}
                    </option>
                </select>
            </div>

            <div id="team-select-{{ $userId }}" class="flex flex-col gap-2 w-full text-sm md:text-base">
                <label class="form-label">{{ __('user_row.assign_team_members') }}</label>
                <div id="team-members-wrapper-{{ $userId }}"
                    class="flex flex-col gap-2 w-full max-h-36 overflow-y-auto">
                    @foreach($teamMembers as $member)
                        <div class="team-member-select flex gap-4 w-full">
                            <select name="team_members[]"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                                <option value="">{{ __('user_row.select_member') }}</option>
                                @foreach($users as $option)
                                    @if($option->getRoleNames()->first() === 'user' && $option->id !== $userId)
                                        <option value="{{ $option->id }}" {{ $option->id === $member->id ? 'selected' : '' }}>
                                            {{ $option->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <button id="remove-member-field-btn" type="button"
                                class="px-3 rounded-full hover:bg-red-100 transition" title="{{ __('tasks.delete') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-red-600">
                                    <path
                                        d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
                                </svg>
                            </button>
                        </div>
                    @endforeach

                    @if($teamMembers->isEmpty())
                        <div class="team-member-select flex gap-4 w-full">
                            <select name="team_members[]"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                                <option value="">{{ __('user_row.select_member') }}</option>
                                @foreach($users as $option)
                                    @if($option->getRoleNames()->first() === 'user' && $option->id !== $userId)
                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button id="remove-member-field-btn" type="button"
                                class="px-3 rounded-full hover:bg-red-100 transition" title="{{ __('tasks.delete') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-red-600">
                                    <path
                                        d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </form>

        <!-- footer -->
        <div id="footer-btn" class="flex flex-col md:flex-row justify-end gap-2 pl-6 pr-6 pb-6 w-full">
            <button id="add-member-btn" type="button"
                class="hidden items-center justify-center rounded-lg gap-2 px-4 py-2 border fill-blue-500 border-blue-500 text-blue-500 text-sm hover:fill-white hover:bg-blue-500 hover:text-white transition"
                onclick="addTeamMemberField({{ $userId }})">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4">
                    <path
                        d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                </svg>
                <span>{{ __('user_row.add_member') }}</span>
            </button>
            <div class="flex flex-col md:flex-row gap-2">
                <button id="update-user-submit" type="button"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm text-white bg-[#5D3FD3] shadow-[0_8px_24px_rgba(99,102,241,0.35)] hover:opacity-95">
                    <!-- spinner -->
                    <svg data-spinner class="hidden w-4 h-4 md:w-5 md:h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>{{ __('update_user.update_btn') }}</span>
                </button>
                <button type="button"
                    class="close-update-user px-4 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-200 transition">
                    {{ __('app.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.i18n = Object.assign(window.i18n || {}, {
        select_member: @json(__('user_row.select_member')),
    });
</script>