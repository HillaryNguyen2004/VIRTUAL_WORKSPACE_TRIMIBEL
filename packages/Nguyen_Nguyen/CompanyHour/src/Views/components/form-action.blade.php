@props(['title' => '', 'formAction' => '', 'startTime' => '', 'endTime' => '', 'workingDays' => null])

<div
    class="flex flex-col items-center w-full h-fit bg-[#FDFDFF] rounded-2xl shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] animate-fade-in-up [animation-delay:150ms]">
    <!-- title -->
    <div class="w-full py-3 text-center text-xl bg-[#F1EFFC] text-[#5D3FD3] font-medium rounded-t-2xl relative">
        <h2>{{ $title }}</h2>
    </div>

    {{-- form --}}
    <div class="w-full">
        <form action="{{ $formAction }}" method="POST">
            @csrf
            <div class="p-6 flex flex-col items-center gap-6 w-full">
                <div class="flex flex-col gap-2 w-full">
                    <label for="start_at">{{ __('company_hour.start_time') }}</label>
                    <input name="start_at" id="start_at" type="time" value="{{ $startTime }}"
                        class="block w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                </div>

                <div class="flex flex-col gap-2 w-full">
                    <label for="end_at">{{ __('company_hour.end_time') }}</label>
                    <input name="end_at" id="end_at" type="time" value="{{ $endTime }}"
                        class="block w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                </div>

                <!-- Working Days Section -->
                <div class="flex flex-col gap-3 w-full border-t pt-6">
                    <label class="font-medium text-gray-700">{{ __('company_hour.working_days') ?? 'Working Days' }}</label>
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        @php
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $selectedDays = $workingDays ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                            if (is_string($selectedDays)) {
                                $selectedDays = json_decode($selectedDays, true) ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                            }
                        @endphp
                        @foreach($days as $day)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="working_days[]" 
                                    value="{{ $day }}"
                                    {{ in_array($day, $selectedDays) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-gray-300 text-[#5D3FD3] focus:ring-[#5D3FD3]">
                                <span class="text-gray-700">{{ __('company_hour.' . strtolower($day)) ?? $day }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('working_days')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" id="submit-btn"
                    class="px-4 py-2 w-full sm:w-52 bg-[#5D3FD3] hover:opacity-95 text-white rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                    {{ __('company_hour.save') }}
                </button>
            </div>
        </form>
    </div>
</div>