@extends('layout_dashboard')
@section('title', __('user_dashboard.title'))

@section('content')
    @role('user')
    <div class="flex flex-col gap-[25px] w-full">
        <!-- First section -->
        <div class="flex flex-col gap-2 sm:flex-row sm:justify-between items-center w-full">
            <!-- Heading -->
            <h2 class="font-medium text-[32px]">{{ __('user_dashboard.heading') }}</h2>
            <!-- Request day off button -->
            <button
                id="open-request-dayoff"
                class="flex justify-center items-center rounded-[10px] w-40 h-fit p-3 bg-[#5D3FD3] text-[#FDFDFF] hover:opacity-95 shadow-[0_4px_30px_0_rgba(36,20,99,0.2)] text-[15px] transition">
                {{ __('user_dashboard.request_day_off') }}
            </button>
        </div>

        <!-- Second section -->
        <div class="flex flex-col sm:flex-row gap-4 w-full">
            <!-- Check attendence -->
            <div
                class="flex flex-col justify-between w-full sm:w-2/5 h-[320px] sm:h-[285px] bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-5 px-6 animate-fade-in-up [animation-delay:150ms]">
                <div class="flex flex-col gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-10 h-10 fill-[#5D3FD3]">
                        <path
                            d="M528 320C528 434.9 434.9 528 320 528C205.1 528 112 434.9 112 320C112 205.1 205.1 112 320 112C434.9 112 528 205.1 528 320zM64 320C64 461.4 178.6 576 320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320zM296 184L296 320C296 328 300 335.5 306.7 340L402.7 404C413.7 411.4 428.6 408.4 436 397.3C443.4 386.2 440.4 371.4 429.3 364L344 307.2L344 184C344 170.7 333.3 160 320 160C306.7 160 296 170.7 296 184z" />
                    </svg>
                    <p class="text-xl">{{ __('user_dashboard.check_attendence') }}</p>
                    @if ($workingHour)
                        <p class="text-[#D3D3D3]">{{ __('user_dashboard.working_hour') }}:
                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->start_at)->format('H:i') }} -
                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->end_at)->format('H:i') }}
                        </p>
                    @endif
                </div>
                <div class="flex flex-col gap-3">
                    <input type="text" id="usernameInput" placeholder="{{ __('user_dashboard.enter_username') }}"
                        class="block w-full bg-transparent placeholder-[#D3D3D3] border border-[#D3D3D3] py-2 px-[10px] rounded-lg hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] text-lg transition">
                    <div class="flex gap-[11px]">
                        <button id="checkInBtn"
                            class="w-full bg-[#5AE194] hover:bg-[#53d48b] rounded-[10px] p-[5px] text-lg text-[#FDFDFF] font-medium transition-all">Check
                            In</button>
                        <button id="checkOutBtn"
                            class="w-full bg-[#CBEA8E] hover:bg-[#c3e088] rounded-[10px] p-[5px] text-lg text-[#FDFDFF] font-medium transition-all">Check
                            Out</button>
                    </div>
                </div>
            </div>
            <!-- Summary -->
            <div
                class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full sm:w-3/5 sm:h-[285px] animate-fade-in-up [animation-delay:200ms]">
                <!-- Card 1 -->
                <div class="rounded-2xl bg-[#FDFDFF] p-6 shadow-[0_4px_40px_0_rgba(32,27,53,0.1)]">
                    <div class="flex items-center justify-center sm:justify-between w-full h-full">
                        <div class="flex flex-col items-center justify-center sm:items-start sm:justify-normal">
                            <p class="text-gray-600">{{ __('user_dashboard.earnings_monthly') }}</p>
                            <p class="mt-2 text-[20px] sm:text-[25px] font-bold">$40,000</p>
                        </div>
                        <!-- icon -->
                        <svg class="h-8 w-8 text-[#5D3FD3] hidden sm:block" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="rounded-2xl bg-[#FDFDFF] p-6 shadow-[0_4px_40px_0_rgba(32,27,53,0.1)]">
                    <div class="flex items-center justify-center sm:justify-between w-full h-full">
                        <div class="flex flex-col items-center justify-center sm:items-start sm:justify-normal">
                            <p class="text-gray-600">{{ __('user_dashboard.earnings_annual') }}</p>
                            <p class="mt-2 text-[20px] sm:text-[25px] font-bold">$215,000</p>
                        </div>
                        <svg class="h-8 w-8 text-[#5D3FD3] hidden sm:block" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M12 1v22M17 5H9a3 3 0 000 6h6a3 3 0 010 6H7" />
                        </svg>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="rounded-2xl bg-[#FDFDFF] p-6 shadow-[0_4px_40px_0_rgba(32,27,53,0.1)]">
                    <div class="flex items-center justify-center sm:justify-between w-full h-full">
                        <div class="flex flex-col items-center justify-center sm:items-start sm:justify-normal">
                            <p class="text-gray-600">{{ __('user_dashboard.tasks') }}</p>
                            <p class="mt-2 text-[20px] sm:text-[25px] font-bold">50%</p>
                        </div>
                        <svg class="h-8 w-8 text-[#5D3FD3] hidden sm:block" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M8 7h8M4 3h16v18H4z" />
                            <path d="m9 14 2 2 4-5" />
                        </svg>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="rounded-2xl bg-[#FDFDFF] p-6 shadow-[0_4px_40px_0_rgba(32,27,53,0.1)]">
                    <div class="flex items-center justify-center sm:justify-between w-full h-full">
                        <div class="flex flex-col items-center justify-center sm:items-start sm:justify-normal">
                            <p class="text-gray-600">{{ __('user_dashboard.pending_requests') }}</p>
                            <p class="mt-2 text-[20px] sm:text-[25px] font-bold">18</p>
                        </div>
                        <svg class="h-8 w-8 text-[#5D3FD3] hidden sm:block" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M7 8h10" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <!-- Third section -->
        <div class="flex flex-col sm:flex-row w-full h-fit gap-3">
            <!-- Team leader + team member -->
            <div class="flex flex-col sm:flex-row w-full {{ !empty($teamLeader) ? 'sm:w-3/5' : 'sm:w-2/5' }} gap-3">
                <!-- Team leader -->
                @if ($teamLeader)
                    <div
                        class="flex flex-col items-center justify-between w-full sm:w-2/5 bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-8 px-2 animate-fade-in-up [animation-delay:250ms]">
                        <div class="flex flex-col items-center justify-center gap-7">
                            <img src="/img/undraw_profile_2.svg" alt="leader_avatar" class="w-[100px] h-[100px] rounded-full">
                            <div class="flex flex-col items-center">
                                <p class="text-[20px] font-medium">{{ $teamLeader->name }}</p>
                                <p class="text-[#D3D3D3]">{{ __('user_dashboard.team_leader') }}</p>
                            </div>
                        </div>
                        <a href="mailto:{{ $teamLeader->email }}" class="truncate block text-[#D3D3D3] text-center w-full hover:underline"
                            title="{{ $teamLeader->email }}">
                            {{ $teamLeader->email }}
                        </a>
                    </div>
                @endif
                <!-- Team member -->
                <div
                    class="flex flex-col items-center gap-8 {{ !empty($teamLeader) ? 'sm:w-3/5 w-full' : 'w-full' }} bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] p-[24px] animate-fade-in-up {{ !empty($teamLeader) ? '[animation-delay:300ms]' : '[animation-delay:250ms]' }}">
                    <div class="flex items-center justify-between w-full">
                        <p class="text-[20px] font-medium">{{ __('user_dashboard.team_members') }}</p>
                        <button id="open-team-member">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                                <path
                                    d="M408 64L552 64C565.3 64 576 74.7 576 88L576 232C576 241.7 570.2 250.5 561.2 254.2C552.2 257.9 541.9 255.9 535 249L496 210L409 297C399.6 306.4 384.4 306.4 375.1 297L343.1 265C333.7 255.6 333.7 240.4 343.1 231.1L430.1 144.1L391.1 105.1C384.2 98.2 382.2 87.9 385.9 78.9C389.6 69.9 398.3 64 408 64zM232 576L88 576C74.7 576 64 565.3 64 552L64 408C64 398.3 69.8 389.5 78.8 385.8C87.8 382.1 98.1 384.2 105 391L144 430L231 343C240.4 333.6 255.6 333.6 264.9 343L296.9 375C306.3 384.4 306.3 399.6 296.9 408.9L209.9 495.9L248.9 534.9C255.8 541.8 257.8 552.1 254.1 561.1C250.4 570.1 241.7 576 232 576z" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex flex-col gap-4 w-full">
                        <p class="text-[#D9D9D9]">{{ __('user_dashboard.name_label') }}</p>
                        <div class="h-px w-full bg-[#D9D9D9]"></div>
                        @if($teamMembers->isNotEmpty())
                            <ul class="flex flex-col gap-4">
                                @foreach($teamMembers->take(3) as $member)
                                    <li>
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div
                                                class="h-8 w-8 rounded-full bg-gray-200 grid place-items-center text-gray-600 uppercase">
                                                {{ mb_substr($member->name ?? '', 0, 1) }}
                                            </div>
                                            <span class="break-all" title="{{ $member->name }}">{{ $member->name }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p>{{ __('user_dashboard.no_team_members') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <!-- Tasks -->
            <div
                class="flex flex-col items-center gap-8 w-full bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] p-[24px] animate-fade-in-up {{ !empty($teamLeader) ? 'sm:w-2/5 [animation-delay:350ms]' : 'sm:w-3/5 [animation-delay:300ms]' }}">
                <div class="flex items-center justify-between w-full">
                    <p class="text-[20px] font-medium">{{ __('user_dashboard.assigned_projects') }}</p>
                    <button id="open-task">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path
                                d="M408 64L552 64C565.3 64 576 74.7 576 88L576 232C576 241.7 570.2 250.5 561.2 254.2C552.2 257.9 541.9 255.9 535 249L496 210L409 297C399.6 306.4 384.4 306.4 375.1 297L343.1 265C333.7 255.6 333.7 240.4 343.1 231.1L430.1 144.1L391.1 105.1C384.2 98.2 382.2 87.9 385.9 78.9C389.6 69.9 398.3 64 408 64zM232 576L88 576C74.7 576 64 565.3 64 552L64 408C64 398.3 69.8 389.5 78.8 385.8C87.8 382.1 98.1 384.2 105 391L144 430L231 343C240.4 333.6 255.6 333.6 264.9 343L296.9 375C306.3 384.4 306.3 399.6 296.9 408.9L209.9 495.9L248.9 534.9C255.8 541.8 257.8 552.1 254.1 561.1C250.4 570.1 241.7 576 232 576z" />
                        </svg>
                    </button>
                </div>
                <div class="flex flex-col gap-4 w-full">
                    <div class="flex items-center justify-between gap-2 w-full text-[#D9D9D9]">
                        <p>{{ __('user_dashboard.tasks') }}</p>
                        <p>{{ __('user_dashboard.task_status') }}</p>
                    </div>
                    <div class="h-px w-full bg-[#D9D9D9]"></div>
                    @if ($assignedTasks->isNotEmpty())
                        @foreach ($assignedTasks->take(3) as $task)
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-gray-100 text-gray-400',
                                    'in_progress' => 'bg-[#F2FBDF] text-[#CBEA8E]',
                                    'completed' => 'bg-[#D3FDE5] text-[#5AE194]',
                                ];

                                $cls = $statusClasses[$task->status];
                            @endphp
                            <ul class="flex flex-col gap-4">
                                <li class="flex items-center justify-between gap-2">
                                    <p class="break-all">{{ $task->title }}</p>
                                    <div class="w-fit h-fit px-3 py-1 rounded-full text-sm text-center {{ $cls }}">
                                        {{ __('user_dashboard.status_' . $task->status) }}
                                    </div>
                                </li>
                            </ul>
                        @endforeach
                    @else
                        <p>{{ __('user_dashboard.no_projects_assigned') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
        <h4>{{ __('user_dashboard.no_permission') }}</h4>
    @endrole
@endsection