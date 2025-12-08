@props([
  'teamMembers' => collect(),
])

@vite(['resources/js/user_dashboard/team_member_dialog.js'])

<div class="hidden items-center justify-center fixed h-screen w-screen bg-black/50 z-[100]" id="team-member-dialog">
    <div class="flex flex-col bg-white w-[340px] md:w-[650px] max-h-[500px] rounded-2xl shadow-2xl animate-fade-in-up [animation-delay:150ms] overflow-hidden">
        
        <div class="flex items-center justify-between px-6 py-4 border-b border-muted-200">
            <h3 class="text-lg font-bold text-main">{{ __('user_dashboard.team_members') }}</h3>
            <button id="close-team-member" class="p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-muted-400">
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