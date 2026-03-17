<div class="@4xl:col-span-5 flex flex-col justify-between h-full min-h-[280px] bg-primary-gradient border-muted-200 shadow-xl shadow-primary/20 rounded-2xl p-6 relative overflow-hidden group">
    {{-- Header: Title + Live Status --}}
    <div class="relative z-10 flex justify-between items-start">
        <div class="flex justify-between w-full">
            <h3 class="text-md md:text-lg font-semibold text-canvas override tracking-tight">
                {{ __('user_dashboard.check_attendence') }}
            </h3>
            <div
                class="p-3 rounded-xl bg-canvas/10 text-canvas override group-hover:scale-110 transition-transform duration-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 14a4 4 0 108 0" />
                    <path d="M9 10h.01" />
                    <path d="M15 10h.01" />
                    <path d="M4 7V5a2 2 0 012-2h2" />
                    <path d="M16 3h2a2 2 0 012 2v2" />
                    <path d="M20 17v2a2 2 0 01-2 2h-2" />
                    <path d="M8 21H6a2 2 0 01-2-2v-2" />
                </svg>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-4">
        {{-- Center: The "Hero" Time Display --}}
        <div class="relative z-10 flex flex-col justify-center">
            @if ($workingHour)
                <div class="space-y-2">
                    <div
                        class="inline-flex items-center gap-2 text-2xl md:text-3xl font-semibold text-canvas override">
                        <span>{{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->start_at)->format('H:i') }}</span>
                        <span class="font-light">-</span>
                        <span>{{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->end_at)->format('H:i') }}</span>
                    </div>
                    <p class="font-medium text-canvas/50 uppercase tracking-widest text-xs">
                        {{ __('user_dashboard.working_hour') }}</p>
                </div>
            @else
                <div class="text-center">
                    <p class="text-muted-400 text-sm md:text-base italic">
                        {{ __('user_dashboard.working_hour_unavailable') }}</p>
                </div>
            @endif
        </div>

        {{-- Bottom: Action Grid --}}
        <div class="relative z-10 mt-auto">
            <div class="grid grid-cols-2 gap-3">
                {{-- Check In Button --}}
                <a href="{{ route('checkin.face.page', 'checkin') }}"
                    class="relative flex flex-col items-center justify-center gap-2 p-4 rounded-xl bg-canvas/10 text-canvas override transition-all duration-300 hover:bg-canvas/20 group/btn">
                    <div class="p-2 rounded-full bg-canvas/20 group-hover/btn:bg-canvas/40 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    <span class="font-semibold text-xs md:text-sm">{{ __('user_dashboard.check_in') }}</span>
                </a>

                {{-- Check Out Button --}}
                <a href="{{ route('checkin.face.page', 'checkout') }}"
                    class="relative flex flex-col items-center justify-center gap-2 p-4 rounded-xl bg-canvas/10 text-canvas override transition-all duration-300 hover:bg-canvas/20 group/btn">
                    <div class="p-2 rounded-full bg-canvas/20 group-hover/btn:bg-canvas/40 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    <span class="font-semibold text-xs md:text-sm">{{ __('user_dashboard.check_out') }}</span>
                </a>
            </div>
        </div>
    </div>
</div>