@props([
  'teamMembers' => collect(),
])

@vite(['resources/js/user_dashboard/team_dialog.js'])

<div class="hidden items-center justify-center fixed h-screen w-screen bg-black/50 z-[100]" id="team-dialog">
    <div class="flex flex-col bg-white w-[460px] md:w-[720px] max-h-[500px] rounded-2xl shadow-2xl animate-fade-in-up [animation-delay:150ms] overflow-hidden">
        
        <div class="flex items-center justify-between px-6 py-4 border-b border-muted-200">
            <h3 class="text-lg font-bold text-main">{{ __('user_dashboard.team_members') }}</h3>
            <button id="close-team" class="p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="p-0 overflow-hidden flex flex-col h-full">
            <div class="overflow-auto max-h-[400px]">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-muted-50 sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-xs font-semibold text-muted-500 uppercase tracking-wider w-1/3">
                                {{ __('user_dashboard.name_label') }}
                            </th>
                            <th scope="col" class="hidden md:table-cell px-6 py-3 text-xs font-semibold text-muted-500 uppercase tracking-wider w-1/3">
                                Username
                            </th>
                            <th scope="col" class="px-6 py-3 text-xs font-semibold text-muted-500 uppercase tracking-wider w-1/3">
                                Email
                            </th>
                            <th scope="col" class="hidden md:table-cell px-6 py-3 text-xs font-semibold text-muted-500 uppercase tracking-wider">
                                Role
                            </th>
                            <th scope="col" class="px-6 py-3 text-xs font-semibold text-muted-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-muted-100 bg-white">
                        @forelse($teamMembers as $member)
                            <tr class="group hover:bg-canvas transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold uppercase ring-2 ring-white group-hover:ring-primary/20 transition-all">
                                            {{ mb_substr($member->name ?? '', 0, 1) }}
                                        </div>
                                        <span class="text-sm font-medium text-main truncate max-w-[120px]" title="{{ $member->name }}">
                                            {{ $member->name }}
                                        </span>
                                    </div>
                                </td>

                                <td class="hidden md:table-cell px-6 py-4">
                                    <span class="text-sm text-muted-500 truncate max-w-[150px] block" title="{{ $member->username }}">
                                        {{ $member->username }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <a href="mailto:{{ $member->email }}" class="text-sm text-primary hover:text-primary-hover hover:underline truncate max-w-[150px] block transition-colors"
                                        title="{{ $member->email }}">
                                        {{ $member->email }}
                                    </a>
                                </td>

                                <td class="hidden md:table-cell px-6 py-4">
                                    @php 
                                        $role = $member->getRoleNames()->first(); 
                                        $role_color = $role == 'substaff' 
                                            ? 'border-primary/50 bg-primary/10 text-primary' 
                                            : 'border-muted-200 bg-muted-200 text-muted-500';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $role_color }} capitalize">
                                        {{ $role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasDepartmentRolePermission('staff.substaff.create'))
                                        @if($role == 'substaff')
                                            <form method="POST" action="{{ route('staff.substaff.make', $member) }}" class="flex items-center gap-2">
                                                @csrf
                                                <button title="{{ __('staff_dashboard.edit_permissions') }}" class="px-2 py-2 rounded-lg text-muted-500 hover:text-secondary hover:bg-secondary/10 transition-all" type="submit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 lucide lucide-user-pen-icon lucide-user-pen">
                                                        <path d="M11.5 15H7a4 4 0 0 0-4 4v2"/>
                                                        <path d="M21.378 16.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/>
                                                        <circle cx="10" cy="7" r="4"/>
                                                    </svg>
                                                </button>
                                                <button title="{{ __('staff_dashboard.demote') }}" class="px-2 py-2 rounded-lg text-muted-500 hover:text-danger hover:bg-danger/10 transition-all" type="submit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 lucide lucide-user-minus-icon lucide-user-minus">
                                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                                        <circle cx="9" cy="7" r="4"/>
                                                        <line x1="22" x2="16" y1="11" y2="11"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('staff.substaff.make', $member) }}">
                                                @csrf
                                                <button title="{{ __('staff_dashboard.promote') }}" class="px-2 py-2 rounded-lg text-muted-500 hover:text-primary hover:bg-primary/10 transition-all" type="submit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 lucide lucide-user-star-icon lucide-user-star">
                                                        <path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z"/>
                                                        <path d="M8 15H7a4 4 0 0 0-4 4v2"/>
                                                        <circle cx="10" cy="7" r="4"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-muted-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-muted-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        <p>{{ __('user_dashboard.no_team_members') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>