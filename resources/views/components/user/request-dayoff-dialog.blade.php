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

    leaveType.addEventListener('change', toggleHalfDay);
    startDate.addEventListener('change', toggleHalfDay);
    endDate.addEventListener('change', toggleHalfDay);
    halfSelect.addEventListener('change', loadPreview);

    toggleHalfDay();
});
</script>
