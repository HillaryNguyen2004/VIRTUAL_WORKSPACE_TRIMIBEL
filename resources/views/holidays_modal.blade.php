@php
    $canEditHolidays = auth()->user()->can('admin.holidays.edit');
@endphp

<div id="holidayModal" class="hidden items-center justify-center fixed inset-0 z-[60] bg-black/50" role="dialog" aria-modal="true">
    
    {{-- Modal Panel --}}
    <div class="flex flex-col w-[320px] sm:w-[380px] md:w-[450px] bg-white rounded-2xl shadow-xl animate-fade-in-up [animation-delay:150ms] overflow-hidden">
        
        {{-- Header --}}
        <div class="w-full px-6 py-4 flex items-center justify-between border-b border-muted-200 bg-white">
            <h3 class="text-lg font-bold text-main" id="modal-title">
                Manage Holiday
            </h3>
            <button type="button" class="close-holiday-modal p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="w-full">
            <form id="holidayForm" action="{{ route('holidays.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="holidayMethod" value="POST">

                <div class="p-6 flex flex-col gap-5 w-full">
                    
                    {{-- Title Input --}}
                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="holidayTitle" class="text-sm font-medium text-main">Holiday Title</label>
                        <input type="text" name="title" id="holidayTitle" required
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                            @disabled(!$canEditHolidays)
                        >
                    </div>

                    {{-- Start Date --}}
                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="holidayStart" class="text-sm font-medium text-main">Start Date</label>
                        <input type="datetime-local" name="start_date" id="holidayStart" required
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                            @disabled(!$canEditHolidays)
                        >
                    </div>

                    {{-- End Date --}}
                    <div class="flex flex-col gap-1.5 w-full">
                        <label for="holidayEnd" class="text-sm font-medium text-main">End Date</label>
                        <input type="datetime-local" name="end_date" id="holidayEnd" required
                            class="text-sm block w-full rounded-xl bg-canvas border border-muted-200 px-4 py-3 text-main focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                            @disabled(!$canEditHolidays)
                        >
                    </div>

                    {{-- Actions --}}
                    <div class="mt-2 flex flex-col-reverse sm:flex-row sm:justify-between gap-3 w-full">
                        {{-- Delete Button (only shown in edit mode) --}}
                        @can('admin.holidays.delete')
                            <button type="button" id="deleteHolidayBtn" class="hidden w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-medium text-white bg-danger hover:bg-danger/90 transition-colors">
                                Delete Holiday
                            </button>
                        @endcan
                        
                        {{-- Right side buttons --}}
                        @can('admin.holidays.edit')
                            <div class="flex flex-col-reverse sm:flex-row gap-3 sm:ml-auto">
                                {{-- Cancel Button (hidden in edit mode) --}}
                                <button type="button" id="cancelHolidayBtn" class="close-holiday-modal w-full sm:w-auto px-5 py-2.5 rounded-xl text-sm font-medium text-muted-600 hover:bg-muted-100 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="w-full sm:w-auto px-6 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-bold rounded-xl shadow-lg shadow-primary/25 transition-all active:scale-95">
                                    Save Holiday
                                </button>
                            </div>
                        @endcan
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>