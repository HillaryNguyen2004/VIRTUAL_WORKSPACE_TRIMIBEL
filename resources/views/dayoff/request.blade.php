@extends('layout_dashboard')

@section('content')
@php
    $minDate = \Carbon\Carbon::tomorrow()->toDateString();
@endphp
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
                <x-form.input
                    type="date"
                    label="request_day_off.start_date_label"
                    name="start_date"
                    id="start_date"
                    :isRequired="true"
                    :value="null"
                    min="{{ $minDate }}"
                />

                <!-- End Date -->
                <x-form.input
                    type="date"
                    label="request_day_off.end_date_label"
                    name="end_date"
                    id="end_date"
                    :isRequired="true"
                    :value="null"
                    min="{{ $minDate }}"
                />

                <!-- Leave Type -->
                <x-form.select
                    label="request_day_off.leave_type_label"
                    name="leave_type"
                    id="leave_type"
                    :value="old('leave_type', 'OFF_FULL')"
                    :options="[
                        'OFF_FULL' => __('request_day_off.full_day'),
                        'OFF_HALF' => __('request_day_off.half_day'),
                    ]"
                />

                <!-- Half Day Period -->
                <div id="half-day-container" class="flex flex-col gap-2 {{ old('leave_type') === 'OFF_HALF' ? '' : 'hidden' }}">
                    <x-form.select
                        label="request_day_off.half_day_period_label"
                        name="half_day_period"
                        id="half_day_period"
                        placeholder="request_day_off.select_period"
                        :value="old('half_day_period')"
                        :options="[
                            'AM' => 'Morning (09:00 - 13:00)',
                            'PM' => 'Afternoon (13:00 - 17:00)',
                        ]"
                    />
                </div>

                <!-- Reason -->
                <x-form.input
                    label="request_day_off.reason_optional_label"
                    name="reason"
                    id="reason"
                    :value="null"
                />

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
