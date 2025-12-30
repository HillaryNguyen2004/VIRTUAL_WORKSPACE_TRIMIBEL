<div id="edit-company-hours-dialog" 
     class="hidden fixed inset-0 z-[60] items-center justify-center bg-gray-900/50 transition-opacity"
     role="dialog" aria-modal="true">

    <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all m-4">
        
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-900">
                {{ __('company_hour.update_title') }}
            </h3>
            <button type="button" class="close-company-hours text-gray-400 hover:text-gray-500 transition-colors">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Note: We use the ID 'edit-company-hours-form' for the JS selector --}}
        <form id="edit-company-hours-form" action="{{ route('companyhour.update') }}" method="POST">
            @csrf
            
            <div class="space-y-4">
                <div>
                    <label for="start_at" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('company_hour.start_time') }}
                    </label>
                    <input type="time" name="start_at" id="start_at"
                        value="{{ isset($startHour) ? sprintf('%02d:00', $startHour) : '09:00' }}"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-gray-50 text-gray-900">
                </div>

                <div>
                    <label for="end_at" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('company_hour.end_time') }}
                    </label>
                    <input type="time" name="end_at" id="end_at"
                        value="{{ isset($endHour) ? sprintf('%02d:00', $endHour) : '17:00' }}"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm bg-gray-50 text-gray-900">
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <button type="button" class="close-company-hours px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Cancel
                </button>
                <button id="submit-company-hours-btn" type="submit" class="px-4 py-2 text-sm font-medium text-white bg-[#5D3FD3] border border-transparent rounded-xl hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-lg shadow-primary/20">
                    {{ __('company_hour.update_title') }}
                </button>
            </div>
        </form>
    </div>
</div>