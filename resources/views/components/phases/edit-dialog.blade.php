<div id="edit-phase-dialog" style="display: none;"
    class="flex fixed inset-0 z-50 bg-black/50 items-center justify-center">

    <div class="flex flex-col w-[320px] sm:w-[380px] md:w-[450px]
                bg-white rounded-2xl shadow-xl overflow-hidden">

        {{-- HEADER --}}
        <div class="px-6 py-4 flex justify-between items-center border-b bg-white">
            <h2 class="text-lg font-bold text-main">
                {{ __('phases.update_phase') }}
            </h2>

            <button id="close-edit-phase" class="p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- BODY --}}
        <form id="editPhaseForm" method="POST" class="p-6" novalidate>
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-5">

                {{-- PHASE TITLE --}}
                <x-form.input label="{{ __('phases.phase_title') }}" name="title" placeholder="e.g. Planning, Development"
                    isRequired="true" />

                {{-- START DATE --}}
                <x-form.input type="date" label="{{ __('phases.start_date') }}" id="edit_start_date" name="start_date" />

                {{-- DUE DATE --}}
                <x-form.input type="date" label="{{ __('phases.due_date') }}" id="edit_due_date" name="due_date" />

                {{-- ACTIONS --}}
                <div class="flex justify-end gap-3">
                    <button type="button" id="close-edit-phase"
                        class="px-4 py-2 text-muted-600 hover:bg-muted-100 rounded-xl">
                        {{ __('app.btn_cancel') }}
                    </button>

                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-xl">
                        {{ __('app.btn_update') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>