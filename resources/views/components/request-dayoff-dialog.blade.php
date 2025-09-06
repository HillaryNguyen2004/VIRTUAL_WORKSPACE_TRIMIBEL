@vite(['resources/utils/request_dayoff/request_dayoff_dialog.js'])

<div id="request-dayoff-dialog" class="hidden items-center justify-center fixed h-screen w-screen bg-black/20 z-50">
    <div
        class="flex flex-col items-center w-[250px] sm:w-[300px] md:w-[400px] lg:w-[500px] h-fit bg-[#FDFDFF] rounded-2xl shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] animate-fade-in-up [animation-delay:150ms]">
        <!-- title -->
        <div class="w-full py-3 text-center text-xl bg-[#F1EFFC] text-[#5D3FD3] font-medium rounded-t-2xl relative">
            <h2>{{ __('request_day_off.form_title') }}</h2>
            <button id="close-request-dayoff" class="absolute top-4 right-5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                    <path
                        d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z" />
                </svg>
            </button>
        </div>
        <!-- form -->
        <div class="w-full">
            <form id="request-dayoff-form" method="POST" action="{{ route('dayoff.request.store') }}" novalidate>
                @csrf

                <div class="p-6 flex flex-col gap-6">
                    <div class="flex flex-col gap-2 w-full">
                        <label for="date" class="form-label">{{ __('request_day_off.select_date_label') }}</label>
                        <input type="date" name="date" id="date"
                            class="block w-full rounded-xl border border-gray-300 px-4 py-3 cursor-pointer hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition @error('date') is-invalid @enderror"
                            min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}" value="{{ old('date') }}">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="leave_type" class="form-label">{{ __('request_day_off.leave_type_label') }}</label>
                        <select name="leave_type" id="leave_type"
                            class="block w-full rounded-xl border border-gray-300 px-4 py-3 cursor-pointer hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition @error('leave_type') is-invalid @enderror">
                            <option value="OFF_FULL" {{ old('leave_type') == 'OFF_FULL' ? 'selected' : '' }}>
                                {{ __('request_day_off.full_day') }}
                            </option>
                            <option value="OFF_HALF" {{ old('leave_type') == 'OFF_HALF' ? 'selected' : '' }}>
                                {{ __('request_day_off.half_day') }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="reason" class="form-label">{{ __('request_day_off.reason_optional_label') }}</label>
                        <input name="reason" id="reason"
                            class="block w-full rounded-xl border border-gray-300 px-4 py-3 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                            placeholder="{{ __('request_day_off.reason_example') }}">{{ old('reason') }}</input>
                    </div>

                    <div class="text-center pt-2">
                        <button type="submit"
                            class="btn btn-primary px-4 py-2 bg-[#5D3FD3] hover:opacity-95 text-white rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
                            {{ __('request_day_off.submit_request') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- <div class="container py-5">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="card shadow border-primary">
                                    <div class="card-header bg-primary text-white text-center">
                                        <h3 class="mb-0">Request a Day Off</h3>
                                    </div>
                                    <div class="card-body">

                                        @if(session('success'))
                                            <div class="alert alert-primary text-center">
                                                {{ session('success') }}
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('dayoff.request.store') }}">
                                            @csrf

                                            <div class="mb-3">
                                                <label for="date" class="form-label">Select Date</label>
                                                <input type="date" name="date" id="date"
                                                    class="form-control @error('date') is-invalid @enderror"
                                                    min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}"
                                                    value="{{ old('date') }}">
                                                @error('date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="leave_type" class="form-label">Leave Type</label>
                                                <select name="leave_type" id="leave_type"
                                                    class="form-select @error('leave_type') is-invalid @enderror">
                                                    <option value="OFF_FULL" {{ old('leave_type') == 'OFF_FULL' ? 'selected' : '' }}>Full Day</option>
                                                    <option value="OFF_HALF" {{ old('leave_type') == 'OFF_HALF' ? 'selected' : '' }}>Half Day</option>
                                                </select>
                                                @error('leave_type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="reason" class="form-label">Reason (Optional)</label>
                                                <textarea name="reason" id="reason"
                                                    class="form-control"
                                                    rows="4"
                                                    placeholder="E.g. Medical appointment, family emergency, etc.">{{ old('reason') }}</textarea>
                                            </div>

                                            <div class="text-center pt-2">
                                                <button type="submit" class="btn btn-primary px-4 py-2">
                                                    Submit Request
                                                </button>
                                            </div>
                                        </form>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->