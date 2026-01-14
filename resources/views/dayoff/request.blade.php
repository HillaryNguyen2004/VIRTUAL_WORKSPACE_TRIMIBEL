@extends('layout_dashboard')

@section('content')
@php
    $minDate = \Carbon\Carbon::tomorrow()->toDateString();
@endphp

<div class="flex flex-col gap-6 w-full max-w-[900px] mx-auto">
    <a href="{{ route('user.dashboard') }}" class="text-[#5D3FD3] text-lg font-medium w-fit">
        ← {{ __('request_day_off.back_to_dashboard') }}
    </a>

    <div class="bg-white rounded-2xl shadow-lg">
        <div class="py-3 text-center text-xl bg-[#F1EFFC] text-[#5D3FD3] font-medium rounded-t-2xl">
            {{ __('request_day_off.form_title') }}
        </div>

        <form method="POST" action="{{ route('dayoff.request.store') }}" id="dayoff-form">
            @csrf

            <div class="p-6 flex flex-col gap-6">

                {{-- DATE RANGE --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form.input
                        type="date"
                        label="request_day_off.start_date_label"
                        id="start_date"
                        min="{{ $minDate }}"
                        required
                    />

                    <x-form.input
                        type="date"
                        label="request_day_off.end_date_label"
                        id="end_date"
                        min="{{ $minDate }}"
                        required
                    />
                </div>

                {{-- LEAVE TYPE --}}
                <x-form.select
                    label="request_day_off.leave_type_label"
                    name="leave_type"
                    id="leave_type"
                    :value="old('leave_type','OFF_FULL')"
                    :options="[
                        'OFF_FULL' => __('request_day_off.full_day'),
                        'OFF_HALF' => __('request_day_off.half_day'),
                    ]"
                />

                {{-- HALF DAY --}}
                <div id="half-day-box" class="hidden flex flex-col gap-2">
                    <x-form.select
                        label="request_day_off.half_day_period_label"
                        name="half_day_period"
                        id="half_day_period"
                        :options="[
                            'AM' => __('request_day_off.morning'),
                            'PM' => __('request_day_off.afternoon'),
                        ]"
                    />

                    <p id="half-day-preview" class="text-sm text-gray-500"></p>
                </div>

                {{-- REASON --}}
                <x-form.input
                    label="request_day_off.reason_optional_label"
                    name="reason"
                />

                {{-- HIDDEN DATES --}}
                <input type="hidden" name="dates[]" id="dates-input">

                <button class="bg-[#5D3FD3] text-white px-6 py-2 rounded-xl w-fit">
                    {{ __('request_day_off.submit_request') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JS --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const leaveType = document.getElementById('leave_type');
    const halfBox = document.getElementById('half-day-box');
    const halfSelect = document.getElementById('half_day_period');
    const preview = document.getElementById('half-day-preview');

    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    const datesInput = document.getElementById('dates-input');

    function toggleHalf() {
        const isHalf = leaveType.value === 'OFF_HALF';
        halfBox.classList.toggle('hidden', !isHalf);
        halfSelect.required = isHalf;
        preview.textContent = '';
    }

    function buildDates() {
        if (!startInput.value || !endInput.value) return;

        const start = new Date(startInput.value);
        const end = new Date(endInput.value);
        const dates = [];

        for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
            dates.push(d.toISOString().slice(0, 10));
        }

        datesInput.value = dates.join(',');
    }

    leaveType.addEventListener('change', toggleHalf);
    startInput.addEventListener('change', buildDates);
    endInput.addEventListener('change', buildDates);

    toggleHalf();
});
</script>
@endsection
