@extends('layouts.app')

@section('content')
<div class="container py-5">
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

                        <!-- Select Date -->
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

                        <!-- Leave Type -->
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

                        <!-- Reason -->
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason (Optional)</label>
                            <textarea name="reason" id="reason"
                                class="form-control"
                                rows="4"
                                placeholder="E.g. Medical appointment, family emergency, etc.">{{ old('reason') }}</textarea>
                        </div>

                        <!-- Submit -->
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
</div>
@endsection
