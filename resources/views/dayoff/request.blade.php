@extends('layout_dashboard')

@section('content')
    <div class="flex flex-col gap-6 w-full">
        <a href="{{ route('user.dashboard') }}" class="text-[#5D3FD3] text-lg font-medium w-fit">
            &larr; {{ __('request_day_off.back_to_dashboard') }}
        </a>
        <div
            class="flex flex-col items-center w-full h-fit bg-[#FDFDFF] rounded-2xl shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] animate-fade-in-up [animation-delay:150ms]">
            <!-- title -->
            <div class="w-full py-3 text-center text-xl bg-[#F1EFFC] text-[#5D3FD3] font-medium rounded-t-2xl relative">
                <h2>{{ __('request_day_off.form_title') }}</h2>
            </div>
            <!-- form -->
            <div class="w-full">
                <form method="POST" action="{{ route('dayoff.request.store') }}" novalidate id="dayoff-form">
                    @csrf
                    <div class="p-6 flex flex-col gap-6">
                        <!-- Date Range Selection -->
                        <div class="flex flex-col gap-2 w-full">
                            <label for="start_date">{{ __('request_day_off.start_date_label') }}</label>
                            <input type="date" name="start_date" id="start_date"
                                class="block w-full rounded-xl border border-gray-300 px-4 py-3 cursor-pointer hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition @error('start_date') is-invalid @enderror"
                                min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}" 
                                value="{{ old('start_date') }}"
                                required>
                            @error('start_date')
                                <span id="error-start-date" class="text-red-400 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-2 w-full">
                            <label for="end_date">{{ __('request_day_off.end_date_label') }}</label>
                            <input type="date" name="end_date" id="end_date"
                                class="block w-full rounded-xl border border-gray-300 px-4 py-3 cursor-pointer hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition @error('end_date') is-invalid @enderror"
                                min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}" 
                                value="{{ old('end_date') }}"
                                required>
                            @error('end_date')
                                <span id="error-end-date" class="text-red-400 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Date Summary (Dynamic Display) -->
                        <div id="date-summary" class="hidden p-3 bg-blue-50 rounded-lg">
                            <p class="text-sm font-medium text-blue-800">{{ __('request_day_off.selected_dates') }}:</p>
                            <div id="selected-dates-list" class="mt-1 text-sm text-blue-600"></div>
                            <p id="total-days" class="mt-2 text-xs font-medium text-blue-700"></p>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="leave_type">{{ __('request_day_off.leave_type_label') }}</label>
                            <select name="leave_type" id="leave_type"
                                class="block w-full rounded-xl border border-gray-300 px-4 py-3 cursor-pointer hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition @error('leave_type') is-invalid @enderror">
                                <option value="OFF_FULL" {{ old('leave_type') == 'OFF_FULL' ? 'selected' : '' }}>
                                    {{ __('request_day_off.full_day') }}
                                </option>
                                <option value="OFF_HALF" {{ old('leave_type') == 'OFF_HALF' ? 'selected' : '' }}>
                                    {{ __('request_day_off.half_day') }}
                                </option>
                            </select>
                            @error('leave_type')
                                <span id="error-leave-type" class="text-red-400 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="reason">{{ __('request_day_off.reason_optional_label') }}</label>
                            <input name="reason" id="reason"
                                class="block w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                                placeholder="{{ __('request_day_off.reason_example') }}"
                                value="{{ old('reason') }}">
                        </div>

                        <!-- Hidden field for all dates -->
                        <input type="hidden" name="dates" id="dates-input">

                        @if (session('success'))
                            <span class="w-full text-center text-green-600">{{ session('success') }}</span>
                        @endif

                        <div class="text-center pt-2">
                            <button type="submit"
                                class="px-4 py-2 bg-[#5D3FD3] hover:opacity-95 text-white rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                                {{ __('request_day_off.submit_request') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const dateSummary = document.getElementById('date-summary');
            const selectedDatesList = document.getElementById('selected-dates-list');
            const totalDaysElement = document.getElementById('total-days');
            const datesInput = document.getElementById('dates-input');
            const form = document.getElementById('dayoff-form');

            // Function to generate dates between start and end date
            function generateDateRange(startDate, endDate) {
                const dates = [];
                const currentDate = new Date(startDate);
                const end = new Date(endDate);
                
                while (currentDate <= end) {
                    // Skip weekends (optional - remove if you want to include weekends)
                    // const dayOfWeek = currentDate.getDay();
                    // if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Skip Sunday (0) and Saturday (6)
                    //     dates.push(new Date(currentDate).toISOString().split('T')[0]);
                    // }
                    
                    dates.push(new Date(currentDate).toISOString().split('T')[0]);
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                
                return dates;
            }

            // Function to update date summary
            function updateDateSummary() {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;
                
                if (!startDate || !endDate) {
                    dateSummary.classList.add('hidden');
                    datesInput.value = '';
                    return;
                }

                const start = new Date(startDate);
                const end = new Date(endDate);

                if (start > end) {
                    dateSummary.classList.add('hidden');
                    datesInput.value = '';
                    endDateInput.setCustomValidity('End date must be after or equal to start date');
                    return;
                }

                endDateInput.setCustomValidity('');
                
                // Generate all dates
                const allDates = generateDateRange(startDate, endDate);
                
                if (allDates.length === 0) {
                    dateSummary.classList.add('hidden');
                    datesInput.value = '';
                    return;
                }

                // Show summary
                dateSummary.classList.remove('hidden');
                
                // Update selected dates list (show first 3 dates and count)
                let datesHtml = '';
                const displayCount = Math.min(3, allDates.length);
                
                for (let i = 0; i < displayCount; i++) {
                    datesHtml += `<div>${allDates[i]}</div>`;
                }
                
                if (allDates.length > displayCount) {
                    datesHtml += `<div class="text-blue-500">... and ${allDates.length - displayCount} more days</div>`;
                }
                
                selectedDatesList.innerHTML = datesHtml;
                totalDaysElement.textContent = `Total: ${allDates.length} day(s)`;
                
                // Store all dates in hidden input (comma-separated)
                datesInput.value = allDates.join(',');
            }

            // Event listeners
            startDateInput.addEventListener('change', updateDateSummary);
            endDateInput.addEventListener('change', updateDateSummary);

            // Initialize if there are old values
            if (startDateInput.value && endDateInput.value) {
                updateDateSummary();
            }

            // Form validation
            form.addEventListener('submit', function(e) {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;
                
                if (!startDate || !endDate) {
                    e.preventDefault();
                    alert('Please select both start and end dates.');
                    return;
                }

                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (start > end) {
                    e.preventDefault();
                    alert('End date must be after or equal to start date.');
                    return;
                }

                // Calculate difference in days
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                if (diffDays > 30) { // Limit to 30 days max (adjust as needed)
                    e.preventDefault();
                    if (!confirm(`You are requesting ${diffDays} days off. Are you sure you want to continue?`)) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
    @endpush
@endsection