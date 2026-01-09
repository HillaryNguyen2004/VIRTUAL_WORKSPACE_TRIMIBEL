@vite(['resources/js/request_dayoff/request_dayoff_dialog.js'])

<div id="request-dayoff-dialog" class="hidden items-center justify-center fixed h-screen w-screen bg-black/50 z-50">
    <div
        class="flex flex-col w-[320px] sm:w-[380px] md:w-[450px] bg-white rounded-2xl shadow-xl animate-fade-in-up [animation-delay:150ms] overflow-hidden">
        
        <div class="w-full px-6 py-4 flex items-center justify-between border-b border-muted-200 bg-white">
            <h2 class="text-lg font-bold text-main">{{ __('request_day_off.form_title') }}</h2>
            <button class="close-request-dayoff p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="w-full">
            <form id="request-dayoff-form" method="POST" action="{{ route('dayoff.request.store') }}" novalidate>
                @csrf
                <div class="p-6 flex flex-col gap-5 w-full">
                    
                    {{-- General Error --}}
                    @error('general')
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm font-medium text-red-800">{{ $message }}</p>
                        </div>
                    @enderror
                    
                    {{-- Date Range Selection --}}
                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="start_date" class="text-sm font-medium text-main">
                            {{ __('request_day_off.start_date_label') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date"
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                            min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}" value="{{ old('start_date') }}">
                    </div>

                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="end_date" class="text-sm font-medium text-main">
                            {{ __('request_day_off.end_date_label') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="end_date"
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                            min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}" value="{{ old('end_date') }}">
                    </div>

                    {{-- Date Summary (Dynamic Display) --}}
                    <div id="date-summary" class="hidden p-3 bg-blue-50 rounded-lg">
                        <p class="text-sm font-medium text-blue-800">{{ __('request_day_off.selected_dates') }}:</p>
                        <div id="selected-dates-list" class="mt-1 text-sm text-blue-600"></div>
                        <p id="total-days" class="mt-2 text-xs font-medium text-blue-700"></p>
                    </div>

                    {{-- Leave Type Select --}}
                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="leave_type" class="text-sm font-medium text-main">
                            {{ __('request_day_off.leave_type_label') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="leave_type" id="leave_type"
                                class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('leave_type') border-danger text-danger @enderror">
                                <option value="OFF_FULL" {{ old('leave_type') == 'OFF_FULL' ? 'selected' : '' }}>
                                    {{ __('request_day_off.full_day') }}
                                </option>
                                <option value="OFF_HALF" {{ old('leave_type') == 'OFF_HALF' ? 'selected' : '' }}>
                                    {{ __('request_day_off.half_day') }}
                                </option>
                            </select>
                            {{-- Custom Chevron --}}
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>

                    {{-- Half Day Period --}}
                    <div id="half-day-container" class="flex flex-col gap-1.5 w-full hidden">
                        <label for="half_day_period" class="text-sm font-medium text-main">
                            {{ __('request_day_off.half_day_period_label') }}
                        </label>
                        <select name="half_day_period" id="half_day_period"
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('half_day_period') border-danger text-danger @enderror">
                            <option value="" disabled selected>{{ __('request_day_off.select_period') }}</option>
                            <option value="AM">Morning (09:00 - 13:00)</option>
                            <option value="PM">Afternoon (13:00 - 17:00)</option>
                        </select>
                        @error('half_day_period')
                            <span class="text-red-400 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Reason Input --}}
                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="reason" class="text-sm font-medium text-main">{{ __('request_day_off.reason_optional_label') }}</label>
                        <textarea name="reason" id="reason" rows="3"
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main placeholder-muted-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all resize-none"
                            placeholder="{{ __('request_day_off.reason_example') }}">{{ old('reason') }}</textarea>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 w-full">
                        <button type="button" class="close-request-dayoff w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-medium text-muted-600 hover:bg-muted-100 transition-colors">
                            {{ __('app.cancel') }}
                        </button>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-bold rounded-xl shadow-lg shadow-primary/25 transition-all active:scale-95">
                            {{ __('request_day_off.submit_request') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>