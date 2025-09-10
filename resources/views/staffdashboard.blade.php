@extends('layout_dashboard')
@section('title', __('staff_dashboard.title'))

@section('content')
    @role('staff')
    <div class="flex flex-col gap-[25px] w-full h-fit">
        <!-- First section -->
        <div class="flex items-center gap-2">
            <h2 class="font-medium text-[28px] md:text-[32px]">{{ __('staff_dashboard.dashboard') }}</h2>
            <div class="bg-[#D6F5E3] text-[#5AE194] text-center text-sm md:text-base w-fit h-fit py-1 px-3 rounded-2xl">
                {{ __('staff_dashboard.staff') }}
            </div>
        </div>

        <!-- Second section -->
        <div class="flex flex-col sm:flex-row gap-5 w-full">
            <!-- Upcoming task -->
            <x-staff.view-block animationDelay="[animation-delay:150ms]">
                <x-staff.view-content title="{{ __('staff_dashboard.upcoming_tasks') }}"
                    subtitle="{{ __('staff_dashboard.upcoming_tasks_description') }}"></x-staff.view-content>
                <x-staff.view-btn href="{{ route('tasks.staff.index') }}" bgColor="bg-[#5D3FD3]"
                    content="{{ __('staff_dashboard.upcoming_tasks_btn') }}"></x-staff.view-btn>
            </x-staff.view-block>
            <!-- Review request day off -->
            <x-staff.view-block animationDelay="[animation-delay:200ms]">
                <x-staff.view-content title="{{ __('staff_dashboard.view_requests') }}"
                    subtitle="{{ __('staff_dashboard.view_requests_description') }}">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-[#5AE194]">
                            <path
                                d="M216 64C229.3 64 240 74.7 240 88L240 128L400 128L400 88C400 74.7 410.7 64 424 64C437.3 64 448 74.7 448 88L448 128L480 128C515.3 128 544 156.7 544 192L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 192C96 156.7 124.7 128 160 128L192 128L192 88C192 74.7 202.7 64 216 64zM480 496C488.8 496 496 488.8 496 480L496 416L408 416L408 496L480 496zM496 368L496 288L408 288L408 368L496 368zM360 368L360 288L280 288L280 368L360 368zM232 368L232 288L144 288L144 368L232 368zM144 416L144 480C144 488.8 151.2 496 160 496L232 496L232 416L144 416zM280 416L280 496L360 496L360 416L280 416zM216 176L160 176C151.2 176 144 183.2 144 192L144 240L496 240L496 192C496 183.2 488.8 176 480 176L216 176z" />
                        </svg>
                    </x-slot:icon>
                </x-staff.view-content>
                <x-staff.view-btn href="{{ route('dayoff.staff.pending') }}" bgColor="bg-[#5AE194]"
                    content="{{ __('staff_dashboard.view_requests_btn') }}"></x-staff.view-btn>
            </x-staff.view-block>
            <!-- Team overview -->
            <x-staff.view-block animationDelay="[animation-delay:250ms]">
                <x-staff.view-content title="{{ __('staff_dashboard.team_overview') }}"
                    subtitle="{{ __('staff_dashboard.team_overview_description') }}">
                    <x-slot:icon>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#CBEA8E]">
                            <path
                                d="M320 64C355.3 64 384 92.7 384 128C384 163.3 355.3 192 320 192C284.7 192 256 163.3 256 128C256 92.7 284.7 64 320 64zM416 376C416 401 403.3 423 384 435.9L384 528C384 554.5 362.5 576 336 576L304 576C277.5 576 256 554.5 256 528L256 435.9C236.7 423 224 401 224 376L224 336C224 283 267 240 320 240C373 240 416 283 416 336L416 376zM160 96C190.9 96 216 121.1 216 152C216 182.9 190.9 208 160 208C129.1 208 104 182.9 104 152C104 121.1 129.1 96 160 96zM176 336L176 368C176 400.5 188.1 430.1 208 452.7L208 528C208 529.2 208 530.5 208.1 531.7C199.6 539.3 188.4 544 176 544L144 544C117.5 544 96 522.5 96 496L96 439.4C76.9 428.4 64 407.7 64 384L64 352C64 299 107 256 160 256C172.7 256 184.8 258.5 195.9 262.9C183.3 284.3 176 309.3 176 336zM432 528L432 452.7C451.9 430.2 464 400.5 464 368L464 336C464 309.3 456.7 284.4 444.1 262.9C455.2 258.4 467.3 256 480 256C533 256 576 299 576 352L576 384C576 407.7 563.1 428.4 544 439.4L544 496C544 522.5 522.5 544 496 544L464 544C451.7 544 440.4 539.4 431.9 531.7C431.9 530.5 432 529.2 432 528zM480 96C510.9 96 536 121.1 536 152C536 182.9 510.9 208 480 208C449.1 208 424 182.9 424 152C424 121.1 449.1 96 480 96z" />
                        </svg>
                    </x-slot:icon>
                </x-staff.view-content>
                <x-staff.view-btn href="{{ route('team.overview') }}" bgColor="bg-[#CBEA8E]"
                    content="{{ __('staff_dashboard.team_overview_btn') }}"></x-staff.view-btn>
            </x-staff.view-block>
        </div>

        <!-- Second section -->
        <div
            class="flex flex-col w-full gap-8 bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-5 px-6 animate-fade-in-up [animation-delay:300ms]">
            <!-- Heading -->
            <div class="flex items-center justify-between w-full">
                <p class="text-[20px] font-medium">{{ __('staff_dashboard.upcoming_tasks') }}</p>
                <a href="{{ route('tasks.staff.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M408 64L552 64C565.3 64 576 74.7 576 88L576 232C576 241.7 570.2 250.5 561.2 254.2C552.2 257.9 541.9 255.9 535 249L496 210L409 297C399.6 306.4 384.4 306.4 375.1 297L343.1 265C333.7 255.6 333.7 240.4 343.1 231.1L430.1 144.1L391.1 105.1C384.2 98.2 382.2 87.9 385.9 78.9C389.6 69.9 398.3 64 408 64zM232 576L88 576C74.7 576 64 565.3 64 552L64 408C64 398.3 69.8 389.5 78.8 385.8C87.8 382.1 98.1 384.2 105 391L144 430L231 343C240.4 333.6 255.6 333.6 264.9 343L296.9 375C306.3 384.4 306.3 399.6 296.9 408.9L209.9 495.9L248.9 534.9C255.8 541.8 257.8 552.1 254.1 561.1C250.4 570.1 241.7 576 232 576z" />
                    </svg>
                </a>
            </div>
            <!-- Content -->
            @php
                $badgeBase = 'px-3 py-1 rounded-full text-sm text-center';
                $statusMap = [
                    'pending' => $badgeBase . ' bg-gray-100 text-gray-400',
                    'in_progress' => $badgeBase . ' bg-[#F2FBDF] text-[#CBEA8E]',
                    'completed' => $badgeBase . ' bg-[#D3FDE5] text-[#5AE194]',
                ];
            @endphp

            <div class="w-full">
                <div class="grid grid-cols-2 md:grid-cols-3 items-center text-gray-300">
                    <p>{{ __('staff_dashboard.tasks_label') }}</p>
                    <p class="text-center hidden md:block">{{ __('staff_dashboard.due_date') }}</p>
                    <p class="text-right">{{ __('staff_dashboard.tasks_status_label') }}</p>
                </div>
                <div class="h-px w-full bg-[#D9D9D9] mt-3"></div>

                @forelse ($tasks->take(3) as $task)
                    <div class="grid grid-cols-2 md:grid-cols-3 items-center pt-3">
                        <p class="truncate pr-4">{{ $task->title }}</p>

                        <p class="text-center hidden md:block">
                            {{ $task->due_date }}
                        </p>

                        <span class="justify-self-end {{ $statusMap[$task->status] }}">
                            {{ __('user_dashboard.status_' . $task->status) }}
                        </span>
                    </div>
                @empty
                    <p>{{ __('staff_dashboard.no_upcoming_tasks') ?? 'No data available' }}</p>
                @endforelse
            </div>
        </div>
        <!-- Third section -->
        <div
            class="flex flex-col w-full gap-8 bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-5 px-6 animate-fade-in-up [animation-delay:350ms]">
            <!-- Heading -->
            <div class="flex items-center justify-between w-full">
                <p class="text-[20px] font-medium">{{ __('staff_dashboard.recent_activity') }}</p>
                <a href="#">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                        <path
                            d="M408 64L552 64C565.3 64 576 74.7 576 88L576 232C576 241.7 570.2 250.5 561.2 254.2C552.2 257.9 541.9 255.9 535 249L496 210L409 297C399.6 306.4 384.4 306.4 375.1 297L343.1 265C333.7 255.6 333.7 240.4 343.1 231.1L430.1 144.1L391.1 105.1C384.2 98.2 382.2 87.9 385.9 78.9C389.6 69.9 398.3 64 408 64zM232 576L88 576C74.7 576 64 565.3 64 552L64 408C64 398.3 69.8 389.5 78.8 385.8C87.8 382.1 98.1 384.2 105 391L144 430L231 343C240.4 333.6 255.6 333.6 264.9 343L296.9 375C306.3 384.4 306.3 399.6 296.9 408.9L209.9 495.9L248.9 534.9C255.8 541.8 257.8 552.1 254.1 561.1C250.4 570.1 241.7 576 232 576z" />
                    </svg>
                </a>
            </div>

            <!-- Content -->
            @php
                $badgeBase = 'flex gap-1 items-center justify-center px-3 py-1 rounded-full text-sm';
                $statusMap = [
                    'add' => $badgeBase . ' bg-[#46AAF1] text-white',
                    'remove' => $badgeBase . ' bg-[#F14646] text-white',
                    'completed' => $badgeBase . ' bg-[#46F196] text-white',
                ];
            @endphp
            <div class="w-full">
                <div class="grid grid-cols-2 md:grid-cols-3 items-center text-gray-300">
                    <p>{{ __('staff_dashboard.activity_title') }}</p>
                    <p class="hidden md:block text-center">
                        {{ __('staff_dashboard.activity_time') }}
                    </p>
                    <p class="text-right">{{ __('staff_dashboard.activity_status_label') }}</p>
                </div>
                <div class="h-px w-full bg-[#D9D9D9] mt-3"></div>

                <div class="grid grid-cols-2 md:grid-cols-3 items-center pt-3">
                    <p class="break-all">Viết báo cáo</p>
                    <p class="text-center hidden md:block">2025-09-26</p>
                    <span class="justify-self-end {{ $statusMap['add'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h4 fill-white">
                            <path
                                d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z" />
                        </svg>
                        <p>Add</p>
                    </span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 items-center pt-3">
                    <p class="break-all">Viết báo cáo</p>
                    <p class="text-center hidden md:block">2025-09-26</p>
                    <span class="justify-self-end {{ $statusMap['remove'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-white">
                            <path
                                d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z" />
                        </svg>
                        <p>Remove</p>
                    </span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 items-center pt-3">
                    <p class="break-all">Viết báo cáo</p>
                    <p class="text-center hidden md:block">2025-09-26</p>
                    <span class="justify-self-end {{ $statusMap['completed'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 640 640"
                            class="w-4 h-4 fill-white"
                        >
                            <path
                                d="M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z" />
                        </svg>
                        <p>Done</p>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <!-- @else
                    <h4>{{ __('staff_dashboard.no_permission') }}</h4>
                @endrole -->
@endsection