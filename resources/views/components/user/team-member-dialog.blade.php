@props([
  'teamMembers' => collect(),
])

@vite(['resources/utils/user_dashboard/team_member_dialog.js'])
<div class="hidden items-center justify-center fixed h-screen w-screen bg-black/20 z-50" id="team-member-dialog">
    <div class="flex flex-col gap-8 bg-[#FDFDFF] w-[300px] md:w-[600px] max-h-[400px] rounded-[20px] p-6 animate-fade-in-up [animation-delay:150ms]">
        <div class="flex justify-between">
            <p class="text-[20px]">{{ __('user_dashboard.team_members') }}</p>
            <button id="close-team-member">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-[#5D3FD3]">
                    <path
                        d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z" />
                </svg>
            </button>
        </div>
        <div class="">
            <table class="w-full table-fixed">
                <thead class="text-sm text-[#D9D9D9] border-b border-[#D9D9D9]">
                    <tr>
                        <th scope="col" class="w-1/2 md:w-1/3 text-left font-medium py-2">
                            {{ __('user_dashboard.name_label') }}
                        </th>
                        <th scope="col" class="hidden md:block md:w-1/3 text-left font-medium py-2">
                            Username
                        </th>
                        <th scope="col" class="w-1/2 md:w-1/3 text-left font-medium py-2">
                            Email
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 text-sm max-h-40 overflow-auto">
                    @forelse($teamMembers as $member)
                        <tr class="">
                            <td class="py-3 pr-2">
                                <span class="break-all" title="{{ $member->name }}">{{ $member->name }}</span>
                            </td>

                            <td class="py-3 pr-2 hidden md:block">
                                <span class="break-all" title="{{ $member->username }}">
                                    {{ $member->username }}
                                </span>
                            </td>

                            <td class="py-3">
                                <a href="mailto:{{ $member->email }}" class="break-all hover:underline"
                                    title="{{ $member->email }}">
                                    {{ $member->email }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-6 text-center text-gray-400">
                                {{ __('user_dashboard.no_team_members') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>