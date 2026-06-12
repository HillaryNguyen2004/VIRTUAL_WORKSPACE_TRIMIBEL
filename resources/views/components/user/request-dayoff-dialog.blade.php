@vite(['resources/js/request_dayoff/request_dayoff_dialog.js'])

<div id="request-dayoff-dialog"
     class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center">

    <div class="flex flex-col w-[320px] sm:w-[380px] md:w-[450px]
                bg-white rounded-2xl shadow-xl overflow-hidden">

        {{-- HEADER --}}
        <div class="px-6 py-4 flex justify-between items-center border-b bg-white">
            <h2 class="text-lg font-bold text-main">
                {{ __('request_day_off.form_title') }}
            </h2>
            <button class="close-request-dayoff p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50">
                ✕
            </button>
        </div>

        {{-- BODY --}}
        <form id="request-dayoff-form"
              method="POST"
              action="{{ route('dayoff.request.store') }}"
              novalidate>
            @csrf

            <div class="p-6 flex flex-col gap-5">

                {{-- START DATE --}}
                <div>
                    <label class="text-sm font-medium text-main">
                        {{ __('request_day_off.start_date_label') }} *
                    </label>
                    <input type="date"
                           id="start_date"
                           name="start_date"
                           min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}"
                           value="{{ old('start_date') }}"
                           class="w-full rounded-xl border px-4 py-3">
                </div>

                {{-- END DATE --}}
                <div>
                    <label class="text-sm font-medium text-main">
                        {{ __('request_day_off.end_date_label') }} *
                    </label>
                    <input type="date"
                           id="end_date"
                           name="end_date"
                           min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}"
                           value="{{ old('end_date') }}"
                           class="w-full rounded-xl border px-4 py-3">
                </div>

                {{-- LEAVE TYPE --}}
                <div>
                    <label class="text-sm font-medium text-main">
                        {{ __('request_day_off.leave_type_label') }} *
                    </label>
                    <select id="leave_type"
                            name="leave_type"
                            class="w-full rounded-xl border px-4 py-3">
                        <option value="OFF_FULL">
                            {{ __('request_day_off.full_day') }}
                        </option>
                        <option value="OFF_HALF">
                            {{ __('request_day_off.half_day') }}
                        </option>
                    </select>
                </div>

                {{-- HALF DAY --}}
                <div id="half-day-container" class="hidden">
                    <label class="text-sm font-medium text-main">
                        {{ __('request_day_off.half_day_period_label') }}
                    </label>

                    <select id="half_day_period"
                            name="half_day_period"
                            class="w-full rounded-xl border px-4 py-3">
                        <option value="" disabled selected>
                            {{ __('request_day_off.select_period') }}
                        </option>
                        <option value="AM">
                            {{ __('request_day_off.morning') }}
                        </option>
                        <option value="PM">
                            {{ __('request_day_off.afternoon') }}
                        </option>
                    </select>

                    {{-- REAL TIME PREVIEW --}}
                    <p id="half-day-preview"
                       class="mt-1 text-xs text-muted-500"></p>
                </div>

                {{-- REASON --}}
                <div>
                    <label class="text-sm font-medium text-main">
                        {{ __('request_day_off.reason_optional_label') }}
                    </label>
                    <textarea name="reason"
                              rows="3"
                              class="w-full rounded-xl border px-4 py-3 resize-none">{{ old('reason') }}</textarea>
                </div>

                {{-- ACTIONS --}}
                <div class="flex justify-end gap-3">
                    <button type="button"
                            class="close-request-dayoff px-4 py-2 text-muted-600 hover:bg-muted-100 rounded-xl">
                        {{ __('app.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-primary text-white rounded-xl">
                        {{ __('request_day_off.submit_request') }}
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- LOGIC SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const leaveType = document.getElementById('leave_type');
    const halfBox = document.getElementById('half-day-container');
    const halfSelect = document.getElementById('half_day_period');
    const preview = document.getElementById('half-day-preview');

    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const form = document.getElementById('request-dayoff-form');

    let holidays = [];

    // Fetch holidays when dialog opens
    async function fetchHolidays() {
        try {
            const res = await fetch("{{ route('holidays.index') }}", {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            if (res.ok) {
                const data = await res.json();
                // Assuming the response contains holidays array
                holidays = data.holidays || data.data || data;
            }
        } catch (error) {
            console.error('Failed to fetch holidays:', error);
        }
    }

    // Check if a date falls within any holiday
    function isHoliday(dateStr) {
        const checkDate = new Date(dateStr);
        
        for (const holiday of holidays) {
            const holidayStart = new Date(holiday.start_date);
            const holidayEnd = new Date(holiday.end_date);
            
            if (checkDate >= holidayStart && checkDate <= holidayEnd) {
                return holiday;
            }
        }
        return null;
    }

    // Validate dates against holidays
    function validateDates() {
        // Remove any existing error messages
        const existingErrors = document.querySelectorAll('.holiday-error-message');
        existingErrors.forEach(error => error.remove());
        
        if (!startDate.value || !endDate.value) {
            return true;
        }

        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        const conflictingHolidays = [];

        // Check each date in the range
        for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
            const dateStr = d.toISOString().split('T')[0];
            const holiday = isHoliday(dateStr);
            
            if (holiday && !conflictingHolidays.find(h => h.title === holiday.title)) {
                conflictingHolidays.push(holiday);
            }
        }

        if (conflictingHolidays.length > 0) {
            displayHolidayError(conflictingHolidays);
            return false;
        }

        return true;
    }

    // Display error message for holiday conflicts
    function displayHolidayError(conflictingHolidays) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'holiday-error-message mt-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700';
        
        const holidayList = conflictingHolidays.map(h => {
            const startDate = new Date(h.start_date);
            const formattedDate = startDate.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
            return `<strong>${h.title}</strong> (${formattedDate})`;
        }).join(', ');
        
        errorDiv.innerHTML = `
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <div class="font-medium">Cannot request day-off on holiday dates</div>
                    <div class="mt-1">${holidayList}</div>
                </div>
            </div>
        `;
        
        endDate.parentElement.appendChild(errorDiv);
    }

    function isSingleDay() {
        return startDate.value && endDate.value && startDate.value === endDate.value;
    }

    function toggleHalfDay() {
        const show = leaveType.value === 'OFF_HALF' && isSingleDay();
        halfBox.classList.toggle('hidden', !show);
        halfSelect.required = show;

        if (!show) {
            halfSelect.value = '';
            preview.textContent = '';
        }
    }

    async function loadPreview() {
        if (!halfSelect.value) {
            preview.textContent = '';
            return;
        }

        preview.textContent = 'Calculating...';

        try {
            const res = await fetch("{{ route('dayoff.halfday.preview') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    period: halfSelect.value
                })
            });

            const data = await res.json();
            preview.textContent =
                `Time: ${data.start_time} → ${data.end_time}`;
        } catch {
            preview.textContent = '';
        }
    }

    // Event listeners
    leaveType.addEventListener('change', toggleHalfDay);
    startDate.addEventListener('change', () => {
        toggleHalfDay();
        validateDates();
    });
    endDate.addEventListener('change', () => {
        toggleHalfDay();
        validateDates();
    });
    halfSelect.addEventListener('change', loadPreview);

    // Prevent form submission if there are holiday conflicts
    form.addEventListener('submit', (e) => {
        if (!validateDates()) {
            e.preventDefault();
            
            // Scroll to error message
            const errorMsg = document.querySelector('.holiday-error-message');
            if (errorMsg) {
                errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // Fetch holidays when page loads
    fetchHolidays();
    toggleHalfDay();
});
</script>
