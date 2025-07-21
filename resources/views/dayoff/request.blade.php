@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-blue-50 to-white flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl w-full bg-white p-10 rounded-2xl shadow-xl border border-blue-100">
        <h2 class="text-3xl font-bold text-center text-blue-900 mb-8 tracking-wide">
            Request a Day Off
        </h2>

        @if(session('success'))
            <div class="bg-blue-100 border border-blue-300 text-blue-800 px-4 py-3 rounded mb-6 text-center font-medium">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('dayoff.request.store') }}" class="space-y-6">
            @csrf

            <!-- Date -->
            <div>
                <label for="date" class="block text-sm font-semibold text-blue-800 mb-2">Select Date</label>
                <input type="date" name="date" id="date"
                       class="w-full border border-blue-300 px-4 py-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('date') border-red-500 @enderror"
                       min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}"
                       value="{{ old('date') }}">
                @error('date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Leave Type -->
            <div>
                <label for="leave_type" class="block text-sm font-semibold text-blue-800 mb-2">Leave Type</label>
                <select name="leave_type" id="leave_type"
                        class="w-full border border-blue-300 px-4 py-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="OFF_FULL" {{ old('leave_type') == 'OFF_FULL' ? 'selected' : '' }}>Full Day</option>
                    <option value="OFF_HALF" {{ old('leave_type') == 'OFF_HALF' ? 'selected' : '' }}>Half Day</option>
                </select>
                @error('leave_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Reason -->
            <div>
                <label for="reason" class="block text-sm font-semibold text-blue-800 mb-2">Reason (Optional)</label>
                <textarea name="reason" id="reason"
                          class="w-full border border-blue-300 px-4 py-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none h-24">{{ old('reason') }}</textarea>
            </div>

            <!-- Submit -->
            <div class="pt-4 text-center">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition duration-200 font-semibold tracking-wide shadow-md">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
