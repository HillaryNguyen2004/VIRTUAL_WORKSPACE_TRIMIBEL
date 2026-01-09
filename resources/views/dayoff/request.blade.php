@extends('layout_dashboard')

@section('content')
<div class="flex flex-col gap-6 w-full">
    <a href="{{ route('user.dashboard') }}" class="text-[#5D3FD3] text-lg font-medium w-fit">
        &larr; {{ __('request_day_off.back_to_dashboard') }}
    </a>

    <div class="flex flex-col items-center w-full bg-[#FDFDFF] rounded-2xl shadow-[0_4px_40px_0_rgba(32,27,53,0.1)]">
        <!-- Title -->
        <div class="w-full py-3 text-center text-xl bg-[#F1EFFC] text-[#5D3FD3] font-medium rounded-t-2xl">
            <h2>{{ __('request_day_off.form_title') }}</h2>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('dayoff.request.store') }}" id="dayoff-form" class="w-full">
            @csrf

            <div class="p-6 flex flex-col gap-6">

                <!-- Start Date -->
                <div class="flex flex-col gap-2">
                    <label for="start_date">{{ __('request_day_off.start_date_label') }}</label>
                    <input type="date" name="start_date" id="start_date"
                        min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}"
                        value="{{ old('start_date') }}"
                        required
                        class="rounded-xl border px-4 py-3">
                    @error('start_date')
                        <span class="text-red-400 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <!-- End Date -->
                <div class="flex flex-col gap-2">
                    <label for="end_date">{{ __('request_day_off.end_date_label') }}</label>
                    <input type="date" name="end_date" id="end_date"
                        min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}"
                        value="{{ old('end_date') }}"
                        required
                        class="rounded-xl border px-4 py-3">
                    @error('end_date')
                        <span class="text-red-400 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Leave Type -->
                <div class="flex flex-col gap-2">
                    <label for="leave_type">{{ __('request_day_off.leave_type_label') }}</label>
                    <select name="leave_type" id="leave_type" class="rounded-xl border px-4 py-3">
                        <option value="OFF_FULL" {{ old('leave_type','OFF_FULL') === 'OFF_FULL' ? 'selected' : '' }}>
                            {{ __('request_day_off.full_day') }}
                        </option>
                        <option value="OFF_HALF" {{ old('leave_type') === 'OFF_HALF' ? 'selected' : '' }}>
                            {{ __('request_day_off.half_day') }}
                        </option>
                    </select>
                </div>

                <!-- Half Day Period -->
                <div id="half-day-container"
                     class="flex flex-col gap-2 {{ old('leave_type') === 'OFF_HALF' ? '' : 'hidden' }}">
                    <label for="half_day_period">{{ __('request_day_off.half_day_period_label') }}</label>

                    <select name="half_day_period" id="half_day_period"
                        class="rounded-xl border px-4 py-3">
                        <option value="" disabled {{ old('half_day_period') ? '' : 'selected' }}>
                            {{ __('request_day_off.select_period') }}
                        </option>
                        <option value="AM" {{ old('half_day_period') === 'AM' ? 'selected' : '' }}>
                            Morning (09:00 - 13:00)
                        </option>
                        <option value="PM" {{ old('half_day_period') === 'PM' ? 'selected' : '' }}>
                            Afternoon (13:00 - 17:00)
                        </option>
                    </select>

                    @error('half_day_period')
                        <span class="text-red-400 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Reason -->
                <div class="flex flex-col gap-2">
                    <label for="reason">{{ __('request_day_off.reason_optional_label') }}</label>
                    <input name="reason" id="reason"
                        value="{{ old('reason') }}"
                        class="rounded-xl border px-4 py-3">
                </div>

                <button type="submit"
                    class="bg-[#5D3FD3] text-white px-6 py-2 rounded-xl">
                    {{ __('request_day_off.submit_request') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JS --}}
<script>
    alert('JS is running');
document.addEventListener('DOMContentLoaded', () => {
    const leaveType = document.getElementById('leave_type');
    const halfDayBox = document.getElementById('half-day-container');
    const halfDaySelect = document.getElementById('half_day_period');

    function toggleHalfDay() {
        const isHalf = leaveType.value === 'OFF_HALF';

        halfDayBox.classList.toggle('hidden', !isHalf);

        if (isHalf) {
            halfDaySelect.setAttribute('required', 'required');
        } else {
            halfDaySelect.removeAttribute('required');
            halfDaySelect.value = '';
        }
    }

    leaveType.addEventListener('change', toggleHalfDay);
    toggleHalfDay(); // init
});
</script>
@endsection
