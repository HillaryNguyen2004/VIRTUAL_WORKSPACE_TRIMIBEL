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
                <form method="POST" action="{{ route('dayoff.request.store') }}" novalidate>
                    @csrf
                    <div class="p-6 flex flex-col gap-6">
                        <!-- @foreach (['success' => 'success', 'error' => 'error'] as $key => $type)
                                        @if (session()->has($key))
                                            @push('scripts')
                                                <script>
                                                    showToast(@json(session($key)), '{{ $type }}', 5000);
                                                </script>
                                            @endpush
                                        @endif
                                    @endforeach -->
                        <!-- @if(session('success'))
                                    @push('scripts')
                                        <script>
                                            alert('{{ session('success') }}');
                                        </script>
                                    @endpush
                                @endif -->

                        <div class="flex flex-col gap-2 w-full">
                            <label for="date">{{ __('request_day_off.select_date_label') }}</label>
                            <input type="date" name="date" id="date"
                                class="block w-full rounded-xl border border-gray-300 px-4 py-3 cursor-pointer hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition @error('date') is-invalid @enderror"
                                min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}" value="{{ old('date') }}">
                            @error('date')
                                <span id="error-date" class="text-red-400 text-xs">{{ $message }}</span>
                            @enderror
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
@endsection