@extends('layout_dashboard')

@section('content')
    @vite(['resources/js/toggle_view.js'])
    @vite(['resources/js/admin/toggle_update_user.js'])

    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }
    @endphp

    @if(auth()->user()->hasRole('admin') || auth()->user()->can('admin.users.view'))
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        
        {{-- HEADER SECTION --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full mb-8">
            <div class="flex items-center gap-4">
                @include('components.back-btn' , ['route' => $dashRoute])
                <div>
                    <h2 class="font-bold text-3xl text-main tracking-tight">
                        {{ __('user_management.title') }}
                    </h2>
                    <p class="text-muted-500 text-sm mt-2">{{ __('user_management.subtitle') ?? 'Manage system access and roles' }}</p>
                </div>
            </div>

            {{-- BUTTONS --}}
            <div class="flex items-center gap-4">
                {{-- Add User --}}
                <a href="{{ route('admin.users.create') }}"
                    class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-primary/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                        <path d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                    </svg>
                    <span class="font-medium">{{ __('user_row.add_member') }}</span>
                </a>
            </div>
        </div>

        {{-- CARD CONTAINER --}}
        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-hidden flex flex-col animate-fade-in-up">

            {{-- SEARCH & FILTER BAR --}}
            <form class="p-5 border-b border-muted-200 flex flex-wrap gap-4 bg-white" method="GET">
                {{-- Search --}}
                <x-form.search-input
                    name="search"
                    id="search"
                    placeholder="user_management.search_placeholder"
                    :value="request('search')"
                />

                {{-- Role Filter --}}
                <x-form.select
                    name="role"
                    id="role"
                    placeholder="user_management.all_roles"
                    :value="request('role')"
                    :options="[
                        'admin' => __('user_management.admin_role'),
                        'staff' => __('user_management.staff_role'),
                        'user'  => __('user_management.user_role'),
                    ]"
                />

                {{-- Sort Filter --}}
                <x-form.select
                    name="sort"
                    id="sort"
                    :value="request('sort', 'asc')"
                    :options="[
                        'asc'  => __('user_management.sort_asc'),
                        'desc' => __('user_management.sort_desc'),
                    ]"
                />

                <div class="flex gap-2">
                    {{-- Filter Button --}}
                    <button type="submit" title="{{ __('tasks.filter') }}"
                        class="border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-current">
                            <path d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                        </svg>
                    </button>

                    {{-- Reset Button --}}
                    <a href="{{ route('users.index') }}" title="{{ __('tasks.reset') }}"
                        class="flex items-center justify-center border border-muted-200 px-3 py-2.5 rounded-xl text-muted-500 hover:bg-primary/5 hover:text-primary hover:border-primary/30 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-current">
                            <path d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                        </svg>
                    </a>
                </div>
            </form>

            {{-- TABLE SECTION --}}
            <div class="overflow-x-auto w-full">
                <table class="w-full table-fixed">
                    <thead class="bg-muted-50 border-b border-muted-200">
                        <tr>
                            <th class="w-[5%] py-4 pl-6 pr-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">ID</th>
                            <th class="w-[20%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('user_management.username_column') }}</th>
                            <th class="w-[25%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('user_management.email_column') }}</th>
                            <th class="w-[15%] py-4 px-3 text-center text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('user_management.role_column') }}</th>
                            <th class="w-[20%] py-4 pr-6 pl-3 text-right text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('user_management.actions_column') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-muted-100">
                        @forelse($users as $user)
                            @php
                                $roles = $user->getRoleNames();
                                $role = null;
                                if ($roles->contains('admin')) $role = 'admin';
                                elseif ($roles->contains('staff')) $role = 'staff';
                                elseif ($roles->contains('user')) $role = 'user';
                                
                                $isStaff = $role === 'staff';
                                $teamLeader = $user->team_leader_id ? $users->firstWhere('id', $user->team_leader_id) : null;
                                $teamMembers = isset($user) ? $users->filter(fn($u) => $u->team_leader_id === $user->id) : collect();

                                // Badge Styles to match Index.blade pill styles
                                $badgeClass = match($role) {
                                    'admin' => 'bg-primary/10 text-primary ring-primary/20',
                                    'staff' => 'bg-secondary/10 text-secondary ring-secondary/20',
                                    'user' => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                                    default => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                                };
                            @endphp

                            <tr class="hover:bg-canvas transition-colors">
                                {{-- Id --}}
                                <td class="py-4 pl-6 pr-3 text-sm text-muted-500 truncate" title="{{ $user->id }}">
                                    {{ $user->id }}
                                </td>

                                {{-- Name --}}
                                <td class="py-4 px-3 text-sm font-medium text-main truncate" title="{{ $user->name }}">
                                    {{ $user->name }}
                                </td>

                                {{-- Email --}}
                                <td class="py-4 px-3 text-sm text-muted-500 hover:text-primary hover:underline truncate">
                                    <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                </td>

                                {{-- Role Pill --}}
                                <td class="py-4 px-3 text-center">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $badgeClass }}">
                                        {{ __('user_row.' . ($role ?? 'user') . '_role') }}
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="py-4 pr-6 pl-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        {{-- View: toggle details row --}}
                                        <button type="button" class="toggle-row p-1.5 rounded-lg text-muted-400 hover:bg-primary/5 hover:text-primary transition-colors"
                                            id="view-btn-{{ $user->id }}" data-target="taskDetails{{ $user->id }}"
                                            aria-controls="taskDetails{{ $user->id }}" aria-expanded="false"
                                            title="{{ __('tasks.view') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                                <path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z" />
                                            </svg>
                                        </button>

                                        {{-- Edit -- Only show if user has permission --}}
                                        <button class="open-update-user p-1.5 rounded-lg text-muted-400 hover:bg-secondary/10 hover:text-secondary transition-colors"
                                            title="{{ __('tasks.edit') }}"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                            data-user-role="{{ $role ?? 'user' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                                <path d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7zM160 128C107 128 64 171 64 224L64 480C64 533 107 576 160 576L416 576C469 576 512 533 512 480L512 384C512 366.3 497.7 352 480 352C462.3 352 448 366.3 448 384L448 480C448 497.7 433.7 512 416 512L160 512C142.3 512 128 497.7 128 480L128 224C128 206.3 142.3 192 160 192L256 192C273.7 192 288 177.7 288 160C288 142.3 273.7 128 256 128L160 128z" />
                                            </svg>
                                        </button>
                                        

                                        {{-- Delete -- Only show if user has permission --}}
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                            onsubmit="return confirm('{{ __('user_row.delete_confirm') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-muted-400 hover:bg-danger/10 hover:text-danger transition-colors"
                                                title="{{ __('tasks.delete') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                                    <path d="M232.7 69.9L224 96L128 96C110.3 96 96 110.3 96 128C96 145.7 110.3 160 128 160L512 160C529.7 160 544 145.7 544 128C544 110.3 529.7 96 512 96L416 96L407.3 69.9C402.9 56.8 390.7 48 376.9 48L263.1 48C249.3 48 237.1 56.8 232.7 69.9zM512 208L128 208L149.1 531.1C150.7 556.4 171.7 576 197 576L443 576C468.3 576 489.3 556.4 490.9 531.1L512 208z" />
                                                </svg>
                                            </button>
                                        </form>
                                        
                                    </div>
                                </td>
                            </tr>

                            {{-- Details row (toggle) --}}
                            <tr id="taskDetails{{ $user->id }}" class="detail-row hidden bg-canvas">
                                <td colspan="5" class="p-6 border-b border-muted-100 shadow-inner">
                                    <div class="grid md:grid-cols-2 gap-6">
                                        @if($isStaff)
                                            <div>
                                                <strong class="text-sm font-bold text-main">{{ __('user_row.team_members_label') }}</strong>
                                                <ul class="mt-2 space-y-2">
                                                    @forelse($teamMembers as $member)
                                                        <li class="flex items-center gap-2 text-sm text-muted-600">
                                                            <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold ring-1 ring-primary/20">
                                                                {{ substr($member->name, 0, 1) }}
                                                            </div>
                                                            <span>{{ $member->name }} <span class="text-muted-400">({{ $member->email }})</span></span>
                                                        </li>
                                                    @empty
                                                        <li class="text-muted-400 italic text-sm">{{ __('user_row.no_team_members') }}</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        @else
                                            <div>
                                                <strong class="text-sm font-bold text-main">{{ __('user_row.team_leader_label') }}</strong>
                                                <div class="mt-2 text-sm text-muted-600">
                                                    @if($teamLeader)
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-6 h-6 rounded-full bg-secondary/10 flex items-center justify-center text-secondary text-xs font-bold ring-1 ring-secondary/20">
                                                                {{ substr($teamLeader->name, 0, 1) }}
                                                            </div>
                                                            <span>{{ $teamLeader->name }} <span class="text-muted-400">({{ $teamLeader->email }})</span></span>
                                                        </div>
                                                    @else
                                                        <p class="text-muted-400 italic">{{ __('user_row.no_team_leader') }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-muted-400">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="p-3 rounded-full bg-muted-100 text-muted-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        </div>
                                        <p class="text-muted-500 font-medium">{{ __('tasks.no_tasks') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINATION --}}
        @if ($users->hasPages())
            <div class="mt-6 flex justify-center w-full">
                {{ $users->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>
    @else
    {{-- Show access denied message if user doesn't have permission --}}
    <div class="flex items-center justify-center min-h-[400px]">
        <div class="text-center">
            <div class="inline-block p-4 rounded-full bg-danger/10 text-danger mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h4 class="text-xl font-bold text-main">{{ __('user_dashboard.no_permission') ?? 'Access Denied' }}</h4>
            <p class="text-muted-500 mt-2">You do not have permission to view user management.</p>
        </div>
    </div>
    @endif
@endsection