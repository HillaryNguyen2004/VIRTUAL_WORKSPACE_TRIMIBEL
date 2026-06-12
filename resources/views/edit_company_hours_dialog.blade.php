{{-- Company Hours Dialog --}}
<div id="edit-company-hours-dialog" class="hidden items-center justify-center fixed h-screen w-screen bg-black/50 z-[60]">
    <div
        class="flex flex-col w-[320px] sm:w-[380px] md:w-[450px] bg-white rounded-2xl shadow-xl animate-fade-in-up [animation-delay:150ms] overflow-hidden">
        
        {{-- Header --}}
        <div class="w-full px-6 py-4 flex items-center justify-between border-b border-muted-200 bg-white">
            <h3 class="text-lg font-bold text-main">
                {{ __('company_hour.update_title') }}
            </h3>
            <button type="button" class="close-company-hours p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="w-full">
            <form id="edit-company-hours-form" action="{{ route('admin.company_hours.update') }}" method="POST">
                @csrf
                
                <div class="p-6 flex flex-col gap-5 w-full">
                    
                    {{-- Start Time --}}
                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="start_at" class="text-sm font-medium text-main">
                            {{ __('company_hour.start_time') }}
                        </label>
                        <input type="time" name="start_at" id="start_at"
                            value="{{ isset($startHour) ? sprintf('%02d:00', $startHour) : '09:00' }}"
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>

                    {{-- End Time --}}
                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="end_at" class="text-sm font-medium text-main">
                            {{ __('company_hour.end_time') }}
                        </label>
                        <input type="time" name="end_at" id="end_at"
                            value="{{ isset($endHour) ? sprintf('%02d:00', $endHour) : '17:00' }}"
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>

                    {{-- Lunch Break Toggle --}}
                    <div class="flex items-center gap-3 py-1">
                        <input type="checkbox" id="has_lunch_break" name="has_lunch_break" 
                               class="w-5 h-5 rounded border-muted-300 text-primary focus:ring-primary cursor-pointer">
                        <label for="has_lunch_break" class="text-sm font-medium text-main select-none cursor-pointer">
                            {{ __('company_hour.lunch_break_checkbox') }}
                        </label>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-muted-200 "></div>

                    {{-- Working Days Section --}}
                    <div class="flex flex-col gap-3 w-full">
                        <label class="text-sm font-medium text-main">{{ __('company_hour.working_days') ?? 'Working Days' }}</label>
                        <div class="flex flex-wrap gap-2">
                            @php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                $dayAbbr = ['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'];
                                $selectedDays = isset($companyHour) && $companyHour->working_days 
                                    ? (is_string($companyHour->working_days) ? json_decode($companyHour->working_days, true) : $companyHour->working_days)
                                    : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                            @endphp
                            @foreach($days as $day)
                                <label class="working-day-toggle cursor-pointer group">
                                    <input 
                                        type="checkbox" 
                                        name="working_days[]" 
                                        value="{{ $day }}"
                                        {{ in_array($day, $selectedDays) ? 'checked' : '' }}
                                        class="hidden peer">
                                    <span class="inline-block px-3 py-2 rounded-lg font-medium text-sm transition-all border border-muted-300 bg-white text-main peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary hover:border-primary/50">
                                        {{ $dayAbbr[$day] ?? substr($day, 0, 3) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-muted-200 "></div>

                    {{-- Option A: Lunch Fields (Hidden by default via JS) --}}
                    <div id="lunch_fields" class="hidden flex-col gap-5 border-muted-100">
                        <div class="flex flex-col gap-1.5 w-full">
                            <label for="lunch_start" class="text-sm font-medium text-main">
                                {{ __('company_hour.lunch_start') }}
                            </label>
                            <input type="time" name="lunch_start" id="lunch_start"
                                value="{{ isset($lunchStartHour) ? sprintf('%02d:00', $lunchStartHour) : '12:00' }}"
                                class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>
                        <div class="flex flex-col gap-1.5 w-full">
                            <label for="lunch_end" class="text-sm font-medium text-main">
                                {{ __('company_hour.lunch_end') }}
                            </label>
                            <input type="time" name="lunch_end" id="lunch_end"
                                value="{{ isset($lunchEndHour) ? sprintf('%02d:00', $lunchEndHour) : '13:00' }}"
                                class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>
                    </div>

                    {{-- Option B: Mid-day Field (Visible by default) --}}
                    <div id="mid_day_field" class="flex flex-col gap-1.5 w-full">
                        <label for="mid_day" class="text-sm font-medium text-main">
                            {{ __('company_hour.midday') }}
                        </label>
                        <input type="time" name="mid_day" id="mid_day"
                            value="{{ isset($midDayHour) ? sprintf('%02d:00', $midDayHour) : '12:00' }}"
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        <p class="text-xs text-muted-500">{{ __('company_hour.midday_help') }}</p>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 w-full">
                        <button type="button" class="close-company-hours w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-medium text-muted-600 hover:bg-muted-100 transition-colors">
                            {{ __('app.cancel') }}
                        </button>
                        <button id="submit-company-hours-btn" type="submit"
                            class="w-full sm:w-auto px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-bold rounded-xl shadow-lg shadow-primary/25 transition-all active:scale-95">
                            {{ __('company_hour.save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('has_lunch_break');
        const lunchFields = document.getElementById('lunch_fields');
        const midDayField = document.getElementById('mid_day_field');

        function toggleFields() {
            if (checkbox.checked) {
                // Show Lunch, Hide Mid-day
                lunchFields.classList.remove('hidden');
                midDayField.classList.add('hidden');
            } else {
                // Hide Lunch, Show Mid-day
                lunchFields.classList.add('hidden');
                midDayField.classList.remove('hidden');
            }
        }

        if(checkbox && lunchFields && midDayField) {
            // Run on change
            checkbox.addEventListener('change', toggleFields);
            // Run on load to ensure correct state
            toggleFields();
        }
    });
</script>