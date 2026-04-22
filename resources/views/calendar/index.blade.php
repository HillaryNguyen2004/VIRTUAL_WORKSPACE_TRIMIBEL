@extends('layout_dashboard')
@section('title', __('calendar.title'))

@section('content')
<div class="@container flex w-full overflow-hidden h-[calc(100vh-4rem)] text-main relative transition-all">
    
    {{-- LEFT PANE: Sidebar / Controls --}}
    <div id="calendarSidebar" class="hidden @4xl:flex w-[85%] max-w-sm @4xl:w-[28%] @4xl:max-w-none h-full @4xl:h-full overflow-y-auto border-r border-muted-200 flex-col shrink-0 bg-white z-40 @4xl:z-0 custom-scrollbar shadow-md absolute @4xl:relative left-0 top-0">
        
        {{-- Header Section --}}
        <div class="flex flex-col px-6 py-8 border-b border-muted-200 gap-4">
            {{-- Mobile close button --}}
            <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="font-semibold text-2xl md:text-3xl text-main">{{ __('calendar.title') }}</h1>
                        <p class="text-muted-500 text-sm md:text-base mt-1">{{ __('calendar.subtitle') }}</p>
                    </div>
                    <button id="closeSidebarBtn" class="@4xl:hidden p-2 rounded-lg text-muted-400 hover:bg-muted-100 hover:text-main transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                {{-- Action Button --}}
                <div class="mt-6">
                    @if(auth()->user()->is_google_connected)
                        {{-- CONNECTED STATE --}}
                        <div class="flex items-center justify-center gap-2 rounded-xl bg-green-50 border border-green-200 w-full py-2.5 text-green-700 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm">{{ __('calendar.google_synced') }}</span>
                        </div>
                    @else
                        {{-- DISCONNECTED STATE --}}
                        <a href="{{ route('calendar.google.connect') }}" 
                        class="group flex items-center justify-center gap-2 rounded-xl bg-white border border-muted-200 w-full py-2.5 text-main font-medium shadow-sm hover:border-primary hover:shadow-lg hover:shadow-primary/10 transition-all active:scale-95">
                            <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                            <span class="text-sm">{{ __('calendar.sync_google') }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
        </div>

        {{-- Mini Calendar --}}
        <div class="p-6 border-b">
            <div class="flex justify-between items-center mb-2">
                <h4 class="text-md md:text-lg font-semibold text-main" id="miniCalendarTitle"></h4>
                <div class="flex gap-1">
                    <button id="miniPrevBtn" class="p-1 hover:bg-muted-100 rounded text-muted-500 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>
                    <button id="miniNextBtn" class="p-1 hover:bg-muted-100 rounded text-muted-500 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>
                </div>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center text-xs">
                <span class="text-muted-400 font-medium py-1">{{ __('calendar.day_mo') }}</span>
                <span class="text-muted-400 font-medium py-1">{{ __('calendar.day_tu') }}</span>
                <span class="text-muted-400 font-medium py-1">{{ __('calendar.day_we') }}</span>
                <span class="text-muted-400 font-medium py-1">{{ __('calendar.day_th') }}</span>
                <span class="text-muted-400 font-medium py-1">{{ __('calendar.day_fr') }}</span>
                <span class="text-muted-400 font-medium py-1">{{ __('calendar.day_sa') }}</span>
                <span class="text-muted-400 font-medium py-1">{{ __('calendar.day_su') }}</span>
                <div id="miniCalendarDays" class="col-span-7 grid grid-cols-7 gap-1"></div>
            </div>
        </div>

        {{-- Filters List --}}
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            {{-- My Calendars --}}
            <div>
                <div class="flex justify-between items-center mb-3 cursor-pointer group" onclick="toggleCalendarsList()">
                    <h4 class="text-md md:text-lg font-semibold text-main">{{ __('calendar.calendars') }}</h4>
                    <svg id="calendars-toggle-icon" class="w-4 h-4 text-muted-400 group-hover:text-primary transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <ul id="calendars-list" class="space-y-3">
                    <li class="flex items-center gap-3">
                        <input type="checkbox" checked class="calendar-filter w-4 h-4 rounded border-muted-300 text-primary focus:ring-primary focus:ring-offset-0 cursor-pointer" data-calendar="tasks">
                        <span class="text-sm font-medium text-main">{{ __('calendar.my_calendar') }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        {{-- CHANGE 'daily-sync' TO 'google' HERE --}}
                        <input type="checkbox" checked class="calendar-filter w-4 h-4 rounded border-muted-300 text-primary focus:ring-primary focus:ring-offset-0 cursor-pointer" data-calendar="google">
                        <span class="text-sm font-medium text-main">{{ __('calendar.google_calendar') }}</span>
                    </li>
                </ul>
            </div>

            {{-- Categories --}}
            <div>
                <div class="flex justify-between items-center mb-3 cursor-pointer group" onclick="toggleCategoriesList()">
                    <h4 class="text-md md:text-lg font-semibold text-main">{{ __('calendar.categories') }}</h4>
                    <svg id="categories-toggle-icon" class="w-4 h-4 text-muted-400 group-hover:text-primary transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
                <ul id="categories-list" class="space-y-3">
                    <li class="flex items-center gap-3 group cursor-pointer category-filter-item" data-category="tasks">
                        <input type="checkbox" checked class="category-filter w-4 h-4 rounded border-muted-300 text-primary focus:ring-primary focus:ring-offset-0 cursor-pointer" data-category="tasks">
                        <div class="w-2.5 h-2.5 rounded-full bg-primary ring-2 ring-transparent group-hover:ring-primary/20 transition-all"></div>
                        <span class="text-sm font-medium text-main">{{ __('calendar.cat_tasks') }}</span>
                    </li>
                    <li class="flex items-center gap-3 group cursor-pointer category-filter-item" data-category="meeting">
                        <input type="checkbox" checked class="category-filter w-4 h-4 rounded border-muted-300 text-accent focus:ring-accent focus:ring-offset-0 cursor-pointer" data-category="meeting">
                        <div class="w-2.5 h-2.5 rounded-full bg-accent ring-2 ring-transparent group-hover:ring-accent/20 transition-all"></div>
                        <span class="text-sm font-medium text-main">{{ __('calendar.cat_meeting') }}</span>
                    </li>
                    <li class="flex items-center gap-3 group cursor-pointer category-filter-item" data-category="other">
                        <input type="checkbox" checked class="category-filter w-4 h-4 rounded border-muted-300 text-secondary focus:ring-secondary focus:ring-offset-0 cursor-pointer" data-category="other">
                        <div class="w-2.5 h-2.5 rounded-full bg-secondary ring-2 ring-transparent group-hover:ring-secondary/20 transition-all"></div>
                        <span class="text-sm font-medium text-main">{{ __('calendar.cat_other') }}</span>
                    </li>
                    <li class="flex items-center gap-3 group cursor-pointer category-filter-item" data-category="holiday">
                        <input type="checkbox" checked class="category-filter w-4 h-4 rounded border-muted-300 text-warning focus:ring-warning focus:ring-offset-0 cursor-pointer" data-category="holiday">
                        <div class="w-2.5 h-2.5 rounded-full bg-warning ring-2 ring-transparent group-hover:ring-warning/20 transition-all"></div>
                        <span class="text-sm font-medium text-main">🎉 {{ __('calendar.cat_holiday') ?? 'Holidays' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- RIGHT PANE: Main Calendar --}}
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-white relative z-auto">
        
        {{-- Calendar Toolbar --}}
        <div class="px-4 @4xl:px-6 py-3 border-b border-muted-200 flex flex-wrap @xl:flex-nowrap justify-between items-center gap-y-2 bg-white shrink-0 shadow-sm">
            {{-- Row 1 (left): Panel toggle + Date title --}}
            <div class="flex items-center gap-2 w-full @xl:w-auto">
                <button id="leftPanelBtn" class="@4xl:hidden p-2 rounded-lg text-muted-400 hover:bg-primary/5 hover:text-primary transition-colors shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-panel-left-icon lucide-panel-left w-5 h-5">
                        <rect width="18" height="18" x="3" y="3" rx="2"/>
                        <path d="M9 3v18"/>
                    </svg>
                </button>
                <h2 class="text-xl md:text-2xl font-bold text-main tracking-tight leading-tight" id="calendarTitle"></h2>
            </div>

            {{-- Row 2 (right on large, full-width row on small): View switcher + Nav --}}
            <div class="flex items-center gap-2 w-full @xl:w-auto justify-between @xl:justify-end">
                <div class="flex bg-muted-100 rounded-lg p-1">
                    <button class="px-3 py-1.5 text-xs font-bold rounded-md text-muted-500 hover:text-main transition-all" id="viewDay">{{ __('calendar.view_day') }}</button>
                    <button class="px-3 py-1.5 text-xs font-bold rounded-md bg-white text-main shadow-sm transition-all" id="viewWeek">{{ __('calendar.view_week') }}</button>
                    <button class="px-3 py-1.5 text-xs font-bold rounded-md text-muted-500 hover:text-main transition-all" id="viewMonth">{{ __('calendar.view_month') }}</button>
                </div>

                <div class="flex gap-1">
                    <button id="prevBtn" class="p-2 rounded-lg border border-muted-200 text-muted-500 hover:text-primary hover:border-primary/30 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>
                    <button id="todayBtn" class="px-3 py-1.5 text-xs font-bold rounded-lg border border-muted-200 text-muted-500 hover:text-primary hover:border-primary/30 transition-all">{{ __('calendar.today') }}</button>
                    <button id="nextBtn" class="p-2 rounded-lg border border-muted-200 text-muted-500 hover:text-primary hover:border-primary/30 transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>
                </div>
            </div>
        </div>

        {{-- FullCalendar Container --}}
        <div class="flex-1 overflow-hidden bg-white relative">
            <div id="calendar" class="h-full w-full custom-calendar"></div>
            
            {{-- "Add Schedule" Modal --}}
            <div id="add-schedule-modal" class="absolute top-4 right-4 w-[380px] bg-white rounded-2xl shadow-xl shadow-main/5 border border-muted-200 z-50 hidden animate-fade-in-up">
                <div class="p-5 flex flex-col gap-4">
                    <div class="flex justify-between items-center pb-2 border-b border-muted-200">
                        {{-- Dynamic Title --}}
                        <h3 class="text-base font-bold text-main" id="modalTitle">{{ __('calendar.add_event') }}</h3>
                        <button id="close-modal" class="text-muted-400 hover:text-primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    {{-- HIDDEN ID FIELD --}}
                    <input type="hidden" id="eventId">

                    {{-- Title Input --}}
                    <div>
                        <input type="text" id="eventTitle" placeholder="{{ __('calendar.event_title_placeholder') }}" class="w-full px-0 py-2 border-0 border-b border-muted-200 outline-none focus:ring-0 focus:border-primary text-lg font-medium placeholder-muted-300 text-main">
                    </div>

                    <div class="flex flex-col gap-3">
                        {{-- Date Picker --}}
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-muted-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> 
                            </div>
                            <input type="date" id="eventDate" class="pl-10 w-full p-3 bg-muted-50 rounded-xl border border-muted-200 text-sm text-main outline-none focus:ring-primary/20 focus:ring-1 focus:border-primary transition-all" required>
                        </div>

                        {{-- Time Pickers --}}
                        <div class="flex items-center gap-3">
                            <div class="flex-1 relative">
                                <input type="time" id="eventStartTime" class="w-full p-3 bg-muted-50 rounded-xl border border-muted-200 text-sm text-main outline-none focus:ring-primary/20 focus:ring-1 focus:border-primary transition-all" required>
                            </div>
                            <span class="text-muted-400">→</span>
                            <div class="flex-1 relative">
                                <input type="time" id="eventEndTime" class="w-full p-3 bg-muted-50 rounded-xl border border-muted-200 text-sm text-main outline-none focus:ring-primary/20 focus:ring-1 focus:border-primary transition-all" required>
                            </div>
                        </div>

                        {{-- Category --}}
                        <div class="relative">
                            <select id="eventCategory" class="w-full p-3 bg-white rounded-xl border border-muted-200 text-sm text-muted-500 focus:ring-primary/20 focus:ring-1 focus:border-primary appearance-none transition-all">
                                <option value="tasks">{{ __('calendar.cat_tasks') }}</option>
                                <option value="meeting">{{ __('calendar.cat_meeting') }}</option>
                                <option value="other">{{ __('calendar.cat_other') }}</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-muted-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>

                        {{-- Meeting Link Input --}}
                        <div class="relative mt-3 group">
                            {{-- Left Icon / Button --}}
                            <button type="button" id="copyMeetingBtn" class="absolute inset-y-0 left-0 pl-3 flex items-center text-muted-400 hover:text-primary transition-colors outline-none z-10 cursor-pointer" title="{{ __('calendar.copy_meeting_link') }}">
                                
                                {{-- Default Link Icon --}}
                                <svg id="iconLink" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>

                                {{-- Success Check Icon (Hidden by default) --}}
                                <svg id="iconCheck" class="w-5 h-5 text-green-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>

                            <input type="url" id="meetingId" placeholder="{{ __('calendar.meeting_url_placeholder') }}" class="pl-10 w-full p-3 bg-muted-50 rounded-xl border border-muted-200 text-sm text-main outline-none focus:ring-primary/20 focus:ring-1 focus:border-primary transition-all">
                        </div>

                        {{-- Recurrence Options --}}
                        <div class="mt-3 p-3 bg-muted-50 rounded-xl border border-muted-200">
                            <label class="text-xs font-bold text-muted-500 uppercase tracking-wider mb-2 block">{{ __('calendar.repeat') }}</label>
                            
                            <div class="flex gap-3 mb-3">
                                <select id="recurrenceType" class="flex-1 p-2 bg-white rounded-lg border border-muted-200 text-sm focus:ring-primary">
                                    <option value="none">{{ __('calendar.no_repeat') }}</option>
                                    <option value="daily">{{ __('calendar.daily') }}</option>
                                    <option value="weekly">{{ __('calendar.weekly') }}</option>
                                    <option value="monthly">{{ __('calendar.monthly') }}</option>
                                    <option value="yearly">{{ __('calendar.yearly') }}</option>
                                </select>
                                
                                {{-- Show 'Every X days/weeks' --}}
                                <div id="recurrenceIntervalGroup" class="hidden flex items-center gap-2">
                                    <span class="text-sm text-muted-500">{{ __('calendar.every') }}</span>
                                    <input type="number" id="recurrenceInterval" value="1" min="1" class="w-16 p-2 bg-white rounded-lg border border-muted-200 text-sm text-center focus:ring-primary">
                                    <span class="text-sm text-muted-500" id="intervalLabel">{{ __('calendar.daily') }}</span>
                                </div>
                            </div>

                            {{-- End Condition (Hidden by default) --}}
                            <div id="recurrenceEndGroup" class="hidden space-y-2">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="recurrenceEnd" value="never" id="endNever" checked class="text-primary focus:ring-primary">
                                    <label for="endNever" class="text-sm text-main">{{ __('calendar.recur_forever') }}</label>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="recurrenceEnd" value="date" id="endDateRadio" class="text-primary focus:ring-primary">
                                    <label for="endDateRadio" class="text-sm text-main">{{ __('calendar.recur_until') }}</label>
                                    <input type="date" id="recurrenceEndDate" class="p-1 text-sm border border-muted-200 rounded disabled:opacity-50" disabled>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Footer Actions --}}
                    <div class="flex justify-between pt-2">
                        {{-- Delete Button (Hidden by default) --}}
                        <button id="deleteEventBtn" class="hidden px-4 py-2 rounded-xl text-danger font-medium text-sm hover:bg-danger/10 transition-colors">
                            {{ __('calendar.delete') }}
                        </button>
                        
                        {{-- Save/Update Button --}}
                        <button id="saveEventBtn" class="ml-auto px-6 py-2 rounded-xl bg-primary text-white font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary-hover transition-all active:scale-95">
                            {{ __('calendar.save') }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Scripts --}}
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
window._calendarI18n = {
    add_event:      @json(__('calendar.add_event')),
    edit_event:     @json(__('calendar.edit_event')),
    save:           @json(__('calendar.save')),
    update:         @json(__('calendar.update')),
    delete_confirm: @json(__('calendar.confirm_delete')),
    fill_required:  @json(__('calendar.fill_required')),
    error_saving:   @json(__('calendar.error_saving')),
    interval_labels: {
        daily:   @json(__('calendar.daily')),
        weekly:  @json(__('calendar.weekly')),
        monthly: @json(__('calendar.monthly')),
        yearly:  @json(__('calendar.yearly')),
    },
};
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var modal = document.getElementById('add-schedule-modal');
    var closeModalBtn = document.getElementById('close-modal');
    var titleEl = document.getElementById('calendarTitle');

    // Buttons & Inputs
    var saveBtn = document.getElementById('saveEventBtn');
    var deleteBtn = document.getElementById('deleteEventBtn');
    var modalTitle = document.getElementById('modalTitle');
    var eventIdInput = document.getElementById('eventId');

    const copyBtn = document.getElementById('copyMeetingBtn');
    const iconLink = document.getElementById('iconLink');
    const iconCheck = document.getElementById('iconCheck');
    const meetingInput = document.getElementById('meetingId');

    if(copyBtn) {
        copyBtn.addEventListener('click', function() {
            const link = meetingInput.value;
            
            // 1. Prevent copying if empty
            if (!link) return;

            // 2. Copy to Clipboard
            navigator.clipboard.writeText(link).then(() => {
                // 3. Show Success Feedback (Swap Icons)
                iconLink.classList.add('hidden');
                iconCheck.classList.remove('hidden');

                // 4. Revert after 2 seconds
                setTimeout(() => {
                    iconLink.classList.remove('hidden');
                    iconCheck.classList.add('hidden');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        });
    }

    const recurType = document.getElementById('recurrenceType');
    const recurIntervalGroup = document.getElementById('recurrenceIntervalGroup');
    const recurEndGroup = document.getElementById('recurrenceEndGroup');
    const intervalLabel = document.getElementById('intervalLabel');
    const endDateRadio = document.getElementById('endDateRadio');
    const endNever = document.getElementById('endNever');
    const recurrenceEndDate = document.getElementById('recurrenceEndDate');
    
    // Close Modal Logic
    document.getElementById('close-modal').addEventListener('click', () => modal.classList.add('hidden'));

    // Modal Logic
    closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));

    // Initialize Calendar
    // Global filter state
    var activeCalendars = new Set(['tasks', 'google']);
    var activeCategories = new Set(['other', 'tasks', 'meeting', 'holiday']);

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        themeSystem: 'standard',
        editable: true,       // Allows dragging
        selectable: true,     // Allows creating new events via drag
        dayMaxEvents: true,
        allDaySlot: true,
        nowIndicator: true,
        
        // Fetch & Filter Events
        events: function(info, successCallback, failureCallback) {
            const url = new URL("{{ route('calendar.events') }}", window.location.origin);
            url.searchParams.append('start', info.startStr);
            url.searchParams.append('end', info.endStr);

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const filteredEvents = data.filter(event => {
                        // 1. Identify Event Type
                        const isGoogle = (event.extendedProps && event.extendedProps.type === 'google') || 
                                        (event.id && event.id.toString().startsWith('google_'));

                        // --- CASE A: Google Calendar ---
                        // Completely independent. Only cares about the "Google" checkbox.
                        if (isGoogle) {
                            return activeCalendars.has('google');
                        }

                        // --- CASE B: Internal "Khoa Beu" Events ---
                        // (Includes System Tasks, Custom Meetings, others, etc.)
                        
                        // Rule 1: Master Switch
                        // If the main "Khoa Beu" calendar (data-calendar="tasks") is unchecked, 
                        // hide EVERYTHING internal, regardless of category.
                        if (!activeCalendars.has('tasks')) {
                            return false;
                        }

                        // Rule 2: Category Filter
                        // If Master is ON, check the specific category.
                        // Note: System tasks usually have category='tasks' by default in your Service
                        const category = event.category || (event.extendedProps && event.extendedProps.category) || 'tasks';
                        
                        return activeCategories.has(category);
                    });
                    successCallback(filteredEvents);
                })
                .catch(error => {
                    console.error('Error fetching events:', error);
                    failureCallback(error);
                });
        },
        
        editable: true,
        selectable: true,
        dayMaxEvents: true,
        allDaySlot: true,
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        
        // 2. Handle Creating New Event (Select)
        select: function(info) {
            resetModal(_calendarI18n.add_event);
            
            if (info.allDay) {
                // Handle All Day selection (YYYY-MM-DD from startStr)
                document.getElementById('eventDate').value = info.startStr;
                document.getElementById('eventStartTime').value = "00:00";
                document.getElementById('eventEndTime').value = "23:59";
            } else {
                // Handle Time Slot selection
                const start = new Date(info.startStr);
                const end = new Date(info.endStr);
                
                // Note: start.toISOString can cause timezone shifts depending on browser, 
                // but we keep existing logic for consistency on time slots for now.
                document.getElementById('eventDate').value = start.toISOString().split('T')[0];
                document.getElementById('eventStartTime').value = formatTime(start);
                
                // Smart End Time
                if (end.getTime() === start.getTime()) {
                    let endDate = new Date(start.getTime() + 60*60*1000); 
                    document.getElementById('eventEndTime').value = formatTime(endDate);
                } else {
                    document.getElementById('eventEndTime').value = formatTime(end);
                }
            }

            modal.classList.remove('hidden');
        },
        
        // 3. Handle Clicking Existing Event (Edit)
        eventClick: function(info) {
            // 1. CHECK IF GOOGLE EVENT
            // Google events have type='google' in extendedProps OR start with 'google_'
            const isGoogle = (info.event.extendedProps && info.event.extendedProps.type === 'google') || 
                            (info.event.id && info.event.id.toString().startsWith('google_'));

            if (isGoogle && info.event.url) {
                info.jsEvent.preventDefault();
                window.open(info.event.url, "_blank");
                return false;
            }

            // 2. FOR EVERYTHING ELSE (Internal Events) -> OPEN MODAL
            resetModal(_calendarI18n.edit_event);
            
            const event = info.event;
            const start = event.start;
            const end = event.end || start; // Fallback if no end time
            const props = event.extendedProps;

            // Fill Data
            document.getElementById('meetingId').value = props.meeting_id || '';

            eventIdInput.value = event.id;
            document.getElementById('eventTitle').value = event.title;
            
            // 4. FILL RECURRENCE DATA (CRITICAL FIX)
            // We check props.recurrence_type sent from CalendarService
            const currentRecurType = props.recurrence_type || 'none'; 
            const currentRecurInterval = props.recurrence_interval || 1;
            // Note: recurrence_end_date might be null
            
            // Set the Dropdown
            const recurTypeSelect = document.getElementById('recurrenceType');
            recurTypeSelect.value = currentRecurType;
            
            // Set the Interval Number
            document.getElementById('recurrenceInterval').value = currentRecurInterval;

            // Trigger the "change" event manually so the UI shows/hides the correct fields
            recurTypeSelect.dispatchEvent(new Event('change'));

            // Set End Date Radio
            if (props.recurrence_end_date) {
                document.getElementById('endDateRadio').checked = true;
                document.getElementById('recurrenceEndDate').disabled = false;
                document.getElementById('recurrenceEndDate').value = props.recurrence_end_date.split('T')[0]; // Format YYYY-MM-DD
            } else {
                document.getElementById('endNever').checked = true;
                document.getElementById('recurrenceEndDate').disabled = true;
                document.getElementById('recurrenceEndDate').value = '';
            }

            if (event.allDay) {
                // Formatting for All Day events (Force 00:00 - 23:59)
                // startStr is "YYYY-MM-DD"
                document.getElementById('eventDate').value = event.startStr; 
                document.getElementById('eventStartTime').value = "00:00";
                document.getElementById('eventEndTime').value = "23:59";
            } else {
                // Formatting for Timed events
                document.getElementById('eventDate').value = start.toISOString().split('T')[0];
                document.getElementById('eventStartTime').value = formatTime(start);
                document.getElementById('eventEndTime').value = formatTime(end);
            }
            
            // Set Category based on class name
            const classes = event.classNames.join(' ');
            if(classes.includes('success') || classes.includes('other')) {
                 document.getElementById('eventCategory').value = 'other';
            } else if (classes.includes('accent') || classes.includes('meeting')) {
                 document.getElementById('eventCategory').value = 'meeting';
            } else {
                 document.getElementById('eventCategory').value = 'tasks';
            }

            // Show Delete Button
            deleteBtn.classList.remove('hidden');
            saveBtn.textContent = _calendarI18n.update;
            
            modal.classList.remove('hidden');
        },

        // 4. Handle Drag & Drop (Update Time)
        eventDrop: function(info) { updateEventOnServer(info); },
        eventResize: function(info) { updateEventOnServer(info); },

        datesSet: function(info) {
            titleEl.textContent = info.view.title;
            if(document.getElementById('currentDateDisplay')){
                document.getElementById('currentDateDisplay').textContent = new Date().toLocaleDateString();
            }
            miniCalendarDate = new Date(info.view.currentStart);
            renderMiniCalendar();
        },

        eventClassNames: function(arg) {
            return [ 'rounded-md', 'border-none', 'shadow-sm', 'font-semibold', 'text-xs', 'px-1' ];
        },
        
        height: '100%',
    });

    calendar.render();

    function resetModal(title) {
        modalTitle.textContent = title;
        eventIdInput.value = '';
        document.getElementById('eventTitle').value = '';
        document.getElementById('meetingId').value = '';
        document.getElementById('eventCategory').value = 'tasks';
        deleteBtn.classList.add('hidden'); // Hide delete by default
        saveBtn.textContent = _calendarI18n.save;
    }

    // Helper: Format Time HH:mm
    function formatTime(date) {
        let hours = date.getHours().toString().padStart(2, '0');
        let minutes = date.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    // Show/Hide options based on selection
    recurType.addEventListener('change', function() {
        if(this.value === 'none') {
            recurIntervalGroup.classList.add('hidden');
            recurEndGroup.classList.add('hidden');
        } else {
            recurIntervalGroup.classList.remove('hidden');
            recurEndGroup.classList.remove('hidden');
            // Update label (e.g., "weeks")
            const map = { daily: _calendarI18n.interval_labels.daily, weekly: _calendarI18n.interval_labels.weekly, monthly: _calendarI18n.interval_labels.monthly, yearly: _calendarI18n.interval_labels.yearly };
            intervalLabel.textContent = map[this.value];
        }
    });

    // Toggle Date Input enable/disable
    [endDateRadio, endNever].forEach(radio => {
        radio.addEventListener('change', () => {
             recurrenceEndDate.disabled = !endDateRadio.checked;
        });
    });

    // SAVE / UPDATE BUTTON CLICK
    saveBtn.addEventListener('click', function() {
        const id = eventIdInput.value;
        const title = document.getElementById('eventTitle').value;
        const datePart = document.getElementById('eventDate').value;
        const startTime = document.getElementById('eventStartTime').value;
        const endTime = document.getElementById('eventEndTime').value;
        const category = document.getElementById('eventCategory').value;
        const meetingId = document.getElementById('meetingId').value;

        if(!title || !datePart || !startTime) return alert(_calendarI18n.fill_required);

        const fullStart = `${datePart} ${startTime}:00`;
        const fullEnd = `${datePart} ${endTime}:00`;

        // Decide URL and Method based on if we have an ID
        const url = id ? "{{ route('calendar.update-details') }}" : "{{ route('calendar.store') }}";
        const method = id ? "PUT" : "POST";

        // GATHER NEW DATA
        const recurTypeVal = document.getElementById('recurrenceType').value;
        const recurIntervalVal = document.getElementById('recurrenceInterval').value;
        
        let recurEndDateVal = null;
        const endDateRadio = document.getElementById('endDateRadio'); 
        const recurrenceEndDateInput = document.getElementById('recurrenceEndDate'); 

        if (recurTypeVal !== 'none' && endDateRadio && endDateRadio.checked) {
            recurEndDateVal = recurrenceEndDateInput.value;
        }

        // Base payload without ID
        const payload = {
            title: title,
            start_date: fullStart, 
            end_date: fullEnd,
            category: category,
            meeting_id: meetingId,
            recurrence_type: recurTypeVal,
            recurrence_interval: recurIntervalVal,
            recurrence_end_date: recurEndDateVal,
        };

        // Only append ID if it actually exists (prevents validation errors on new events)
        if (id) {
            payload.id = id;
        }

        fetch(url, {
            method: method,
            headers: { 
                "Content-Type": "application/json", 
                "Accept": "application/json", // <-- Crucial: Tells Laravel to send JSON validation errors
                "X-CSRF-TOKEN": "{{ csrf_token() }}" 
            },
            body: JSON.stringify(payload) 
        })
        .then(async r => {
            if (!r.ok) {
                // If validation fails (422) or server errors (500), parse the error
                const errData = await r.json();
                console.error("Server Error:", errData);
                throw new Error(errData.message || 'Server rejected the request');
            }
            return r.json();
        })
        .then(data => {
            if(data.status === 'success') {
                modal.classList.add('hidden');
                calendar.refetchEvents();
            } else {
                alert(_calendarI18n.error_saving + "\n" + (data.message || ''));
            }
        })
        .catch(err => {
            // This catches the fetch crash and alerts you properly
            console.error('Fetch Exception:', err);
            alert(_calendarI18n.error_saving + "\n" + err.message);
        });
    });

    // DELETE BUTTON CLICK
    deleteBtn.addEventListener('click', function() {
        const id = eventIdInput.value;
        if(!id || !confirm(_calendarI18n.delete_confirm)) return;

        fetch("{{ route('calendar.destroy') }}", {
            method: "DELETE",
            headers: { 
                "Content-Type": "application/json", 
                "Accept": "application/json", // <-- Crucial: Forces Laravel to send JSON errors
                "X-CSRF-TOKEN": "{{ csrf_token() }}" 
            },
            body: JSON.stringify({ id: id })
        })
        .then(async r => {
            if (!r.ok) {
                // If validation fails (422) or server errors (500), parse the error
                const errData = await r.json();
                console.error("Server Error:", errData);
                throw new Error(errData.message || 'Server rejected the request');
            }
            return r.json();
        })
        .then(data => {
            if(data.status === 'success') {
                modal.classList.add('hidden');
                calendar.refetchEvents();
            } else {
                alert(_calendarI18n.error_saving + "\n" + (data.message || 'Error deleting event'));
            }
        })
        .catch(err => {
            // Catches the exception and alerts you properly
            console.error('Delete Exception:', err);
            alert("Error deleting event: \n" + err.message);
        });
    });

    // Drag & Drop Helper
    function updateEventOnServer(info) {
        let payload = {
            id: info.event.id,
            start: info.event.startStr,
            end: info.event.endStr || null 
        };

        if (info.event.allDay) {
            // Force 00:00 - 23:59 format
            // FullCalendar startStr for allDay is YYYY-MM-DD
            
            let date = info.event.startStr;
            // What if it's multi-day?
            if (info.event.end) {
                payload.start = date + ' 00:00:00';

                let endDate = new Date(info.event.end); // 00:00 of next day
                endDate.setSeconds(endDate.getSeconds() - 60); // Subtract 1 minute -> 23:59 previous day
                
                // Format YYYY-MM-DD HH:mm:ss
                let endY = endDate.getFullYear();
                let endM = (endDate.getMonth()+1).toString().padStart(2, '0');
                let endD = endDate.getDate().toString().padStart(2, '0');
                let endH = endDate.getHours().toString().padStart(2, '0');
                let endMin = endDate.getMinutes().toString().padStart(2, '0');
                 
                payload.end = `${endY}-${endM}-${endD} ${endH}:${endMin}:00`;
            } else {
                // Single Day (FC might default to null end for single all-day)
                payload.start = date + ' 00:00:00';
                payload.end = date + ' 23:59:00';
            }
        }

        fetch("{{ route('calendar.update') }}", {
            method: "PATCH",
            headers: { 
                "Content-Type": "application/json", 
                "Accept": "application/json", // <--- THE FIX: Force JSON response
                "X-CSRF-TOKEN": "{{ csrf_token() }}" 
            },
            body: JSON.stringify(payload)
        })
        .then(async r => {
            if (!r.ok) {
                const errData = await r.json();
                console.error("Drag Update Error:", errData);
                throw new Error(errData.message || 'Server rejected the drag update');
            }
            return r.json();
        })
        .then(data => {
            if(data.status !== 'success') {
                console.error("Update failed:", data);
                info.revert();
            }
        })
        .catch(err => {
            console.error("Fetch Exception:", err);
            alert("Error updating event: " + err.message);
            info.revert();
        });
    }

    // Render Mini Calendar
    function renderMiniCalendar() {
        const year = miniCalendarDate.getFullYear();
        const month = miniCalendarDate.getMonth();
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        if(document.getElementById('miniCalendarTitle')) {
            document.getElementById('miniCalendarTitle').textContent = `${monthNames[month]} ${year}`;
        }
        
        const firstDay = new Date(year, month, 1);
        let startDay = firstDay.getDay();
        startDay = startDay === 0 ? 6 : startDay - 1; 
        
        const lastDay = new Date(year, month + 1, 0).getDate();
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        
        const daysContainer = document.getElementById('miniCalendarDays');
        if(!daysContainer) return;
        daysContainer.innerHTML = '';
        
        const today = new Date();
        const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;
        
        for (let i = startDay - 1; i >= 0; i--) {
            const day = prevMonthLastDay - i;
            const dayEl = createDayEl(day, 'text-muted-300', year, month - 1);
            daysContainer.appendChild(dayEl);
        }
        
        for (let day = 1; day <= lastDay; day++) {
            let classes = 'text-main hover:bg-muted-100';
            if (isCurrentMonth && day === today.getDate()) {
                classes = 'text-white bg-primary shadow-sm shadow-primary/40';
            }
            const dayEl = createDayEl(day, classes, year, month);
            daysContainer.appendChild(dayEl);
        }
        
        const totalCells = startDay + lastDay;
        const remaining = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
        for (let day = 1; day <= remaining; day++) {
            const dayEl = createDayEl(day, 'text-muted-300', year, month + 1);
            daysContainer.appendChild(dayEl);
        }
    }

    function createDayEl(day, classes, year, month) {
        const el = document.createElement('span');
        el.className = `${classes} py-1 cursor-pointer rounded-full w-7 h-7 flex items-center justify-center mx-auto transition-colors`;
        el.textContent = day;
        el.addEventListener('click', () => {
            calendar.gotoDate(new Date(year, month, day));
            renderMiniCalendar();
        });
        return el;
    }

    if(document.getElementById('miniPrevBtn')){
        document.getElementById('miniPrevBtn').addEventListener('click', () => {
            miniCalendarDate.setMonth(miniCalendarDate.getMonth() - 1);
            renderMiniCalendar();
        });
    }
    if(document.getElementById('miniNextBtn')){
        document.getElementById('miniNextBtn').addEventListener('click', () => {
            miniCalendarDate.setMonth(miniCalendarDate.getMonth() + 1);
            renderMiniCalendar();
        });
    }
    
    // Filters
    document.querySelectorAll('.calendar-filter').forEach(cb => {
        cb.addEventListener('change', function() {
            const calendarType = this.dataset.calendar;
            
            // Update State
            if (this.checked) activeCalendars.add(calendarType);
            else activeCalendars.delete(calendarType);

            // HIERARCHY UX: If this is the "Khoa Beu" (tasks) calendar...
            if (calendarType === 'tasks') {
                const categoryCheckboxes = document.querySelectorAll('.category-filter');
                const categoryLabels = document.querySelectorAll('.category-filter-item'); // The <li> container
                
                if (this.checked) {
                    // Enable Categories
                    categoryCheckboxes.forEach(catCb => catCb.disabled = false);
                    categoryLabels.forEach(label => label.classList.remove('opacity-50', 'pointer-events-none'));
                } else {
                    // Disable Categories (Visual only, logic is handled in events function)
                    categoryCheckboxes.forEach(catCb => catCb.disabled = true);
                    categoryLabels.forEach(label => label.classList.add('opacity-50', 'pointer-events-none'));
                }
            }

            calendar.refetchEvents();
        });
    });
    
    document.querySelectorAll('.category-filter').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) activeCategories.add(this.dataset.category);
            else activeCategories.delete(this.dataset.category);
            calendar.refetchEvents();
        });
    });

    // View Switcher
    const viewButtons = { 'viewDay': 'timeGridDay', 'viewWeek': 'timeGridWeek', 'viewMonth': 'dayGridMonth' };
    Object.keys(viewButtons).forEach(btnId => {
        if(document.getElementById(btnId)){
            document.getElementById(btnId).addEventListener('click', function() {
                Object.keys(viewButtons).forEach(id => {
                    document.getElementById(id).classList.remove('bg-white', 'text-main', 'shadow-sm');
                    document.getElementById(id).classList.add('text-muted-500', 'hover:text-main');
                });
                this.classList.add('bg-white', 'text-main', 'shadow-sm');
                this.classList.remove('text-muted-500', 'hover:text-main');
                calendar.changeView(viewButtons[btnId]);
            });
        }
    });

    if(document.getElementById('prevBtn')) document.getElementById('prevBtn').addEventListener('click', () => calendar.prev());
    if(document.getElementById('nextBtn')) document.getElementById('nextBtn').addEventListener('click', () => calendar.next());
    if(document.getElementById('todayBtn')) document.getElementById('todayBtn').addEventListener('click', () => calendar.today());
    
    renderMiniCalendar();

    // Mobile Sidebar Toggle
    const leftPanelBtn = document.getElementById('leftPanelBtn');
    const sidebar = document.getElementById('calendarSidebar');
    
    if (leftPanelBtn && sidebar) {
        function closeSidebar() {
            sidebar.classList.add('hidden');
            sidebar.classList.remove('flex');
        }

        function openSidebar() {
            sidebar.classList.remove('hidden');
            sidebar.classList.add('flex');
        }

        leftPanelBtn.addEventListener('click', function() {
            if (sidebar.classList.contains('hidden')) {
                openSidebar();
            } else {
                closeSidebar();
            }
        });

        const closeSidebarBtn = document.getElementById('closeSidebarBtn');
        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', closeSidebar);
        }
    }
});

function toggleCalendarsList() {
    const list = document.getElementById('calendars-list');
    const icon = document.getElementById('calendars-toggle-icon');
    if (!list || !icon) return;
    const isCollapsed = list.classList.toggle('hidden');
    icon.style.transform = isCollapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
}

function toggleCategoriesList() {
    const list = document.getElementById('categories-list');
    const icon = document.getElementById('categories-toggle-icon');
    if (!list || !icon) return;
    const isCollapsed = list.classList.toggle('hidden');
    icon.style.transform = isCollapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
}
</script>

{{-- STYLES: Now clean and minimal, only handling FullCalendar overrides --}}
<style>
    :root {
        /* We still need a few overrides for the Grid/Chrome itself */
        --fc-border-color: #E5E7EB;        /* border-muted-200 */
        --fc-page-bg-color: #ffffff;
        --fc-neutral-bg-color: #F9FAFB;    /* bg-canvas */
        --fc-today-bg-color: rgba(83, 71, 204, 0.04);
        --fc-now-indicator-color: #EF4444; 
    }

    /* Hide Toolbar */
    .fc-header-toolbar { display: none !important; }

    /* Headers */
    .fc-col-header-cell { 
        background-color: var(--fc-page-bg-color); 
        padding: 12px 0; 
        border-bottom: 1px solid var(--fc-border-color); 
    }
    .fc-col-header-cell-cushion { 
        text-transform: uppercase; 
        font-size: 0.75rem; 
        font-weight: 700; 
        letter-spacing: 0.05em; 
        color: #9CA3AF; /* text-muted-400 */
        text-decoration: none !important; 
    }
    .fc-timegrid-slot-label-cushion { 
        font-size: 0.75rem; 
        color: #9CA3AF; 
        font-weight: 500; 
    }

    /* Event Base Styles */
    .fc-event { 
        border-radius: 6px; 
        font-size: 0.75rem; 
        transition: all 0.2s; 
        border-top: none !important;
        border-right: none !important;
        border-bottom: none !important;
    }
    .fc-event:hover { 
        opacity: 0.95; 
        transform: scale(1.01); 
        z-index: 50; 
    }
    
    /* Ensure content inside event has padding */
    .fc-event-main {
        padding: 2px 4px;
    }

    .fc-daygrid-event-dot {
        
    }

    /* Indicator Line */
    .fc-now-indicator-line { 
        border-color: var(--fc-now-indicator-color); 
        border-width: 2px; 
    }
    .fc-now-indicator-arrow { 
        border-color: var(--fc-now-indicator-color); 
        background-color: var(--fc-now-indicator-color); 
    }

    /* Scrollbar */
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #D1D5DB; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #9CA3AF; }
</style>
@endsection