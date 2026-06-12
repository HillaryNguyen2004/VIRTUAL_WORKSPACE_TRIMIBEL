{{-- Edit Workspace Modal --}}
<div id="edit-workspace-modal" class="fixed inset-0 z-50 items-center justify-center p-4"
    style="display:none" aria-modal="true" role="dialog">

    <div class="absolute inset-0 bg-black/50" id="edit-modal-backdrop"></div>

    <div class="relative w-full max-w-2xl bg-canvas rounded-2xl border border-muted-200 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden animate-fade-in-up">

        <div class="absolute top-0 right-0 -mt-6 -mr-6 w-32 h-32 bg-primary/10 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

        {{-- Header --}}
        <div class="flex items-start justify-between px-6 py-5 border-b border-muted-200 relative z-10 flex-shrink-0">
            <div>
                <h2 class="font-bold text-xl text-main tracking-tight">{{ __('ai.edit_workspace') }}</h2>
                <p class="text-muted-500 text-sm mt-0.5 truncate max-w-sm">{{ $workspace->name }}</p>
            </div>
            <button type="button" id="close-edit-modal"
                class="mt-0.5 ml-4 flex-shrink-0 p-2 rounded-xl text-muted-400 hover:bg-muted-100 hover:text-main transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto flex-1">
            <form action="{{ route('ai-workspaces.update', $workspace) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                @if($errors->any())
                    <div class="mb-5 rounded-xl border border-danger/20 bg-danger/5 px-4 py-3 text-danger text-sm">
                        <ul class="space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5">
                    <x-form.input label="ai.workspace_name" name="name" :value="old('name', $workspace->name)"
                        placeholder="ai.workspace_name_placeholder" :isRequired="true" />

                    <x-form.textarea label="ai.description" name="description"
                        :value="old('description', $workspace->description)"
                        placeholder="ai.description_placeholder" :rows="3" />

                    {{-- Visibility --}}
                    <div>
                        <label class="block text-sm font-semibold text-main mb-3">
                            {{ __('ai.visibility') }} <span class="text-danger">*</span>
                        </label>
                        <div class="flex items-stretch w-full text-sm font-medium text-muted-500 bg-white p-1 border border-muted-300 rounded-2xl">

                            {{-- Private --}}
                            <label class="group flex flex-col flex-1 gap-1 p-3.5 rounded-xl cursor-pointer transition-all duration-300 hover:bg-muted-50 has-[:checked]:bg-primary/10">
                                <input type="radio" name="visibility" value="private" class="sr-only"
                                    {{ old('visibility', $workspace->visibility) === 'private' ? 'checked' : '' }} required>
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 flex-shrink-0 rounded-lg bg-primary/10 text-primary flex items-center justify-center transition-colors group-has-[:checked]:bg-primary group-has-[:checked]:text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-main text-sm group-has-[:checked]:text-primary">{{ __('ai.visibility_private') }}</span>
                                </div>
                                <p class="text-muted-500 text-xs leading-relaxed pl-9">{{ __('ai.visibility_private_desc') }}</p>
                            </label>

                            <div id="edit-vis-d1" class="w-px bg-muted-300 my-1 transition-opacity duration-300"></div>

                            {{-- Team --}}
                            <label class="group flex flex-col flex-1 gap-1 p-3.5 rounded-xl cursor-pointer transition-all duration-300 hover:bg-muted-50 has-[:checked]:bg-secondary/10">
                                <input type="radio" name="visibility" value="team" class="sr-only"
                                    {{ old('visibility', $workspace->visibility) === 'team' ? 'checked' : '' }}>
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 flex-shrink-0 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center transition-colors group-has-[:checked]:bg-secondary group-has-[:checked]:text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-main text-sm group-has-[:checked]:text-secondary">{{ __('ai.visibility_team') }}</span>
                                </div>
                                <p class="text-muted-500 text-xs leading-relaxed pl-9">{{ __('ai.visibility_team_desc') }}</p>
                            </label>

                            <div id="edit-vis-d2" class="w-px bg-muted-300 my-1 transition-opacity duration-300"></div>

                            {{-- Public --}}
                            <label class="group flex flex-col flex-1 gap-1 p-3.5 rounded-xl cursor-pointer transition-all duration-300 hover:bg-muted-50 has-[:checked]:bg-accent/10">
                                <input type="radio" name="visibility" value="public" class="sr-only"
                                    {{ old('visibility', $workspace->visibility) === 'public' ? 'checked' : '' }}>
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 flex-shrink-0 rounded-lg bg-accent/10 text-accent flex items-center justify-center transition-colors group-has-[:checked]:bg-accent group-has-[:checked]:text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-main text-sm group-has-[:checked]:text-accent">{{ __('ai.visibility_public') }}</span>
                                </div>
                                <p class="text-muted-500 text-xs leading-relaxed pl-9">{{ __('ai.visibility_public_desc') }}</p>
                            </label>
                        </div>
                        @error('visibility')
                            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Allow others upload --}}
                    <label class="relative block rounded-xl border border-muted-200 bg-white p-4 cursor-pointer transition-all hover:bg-muted-50 has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                        <input type="hidden" name="allow_others_upload" value="0">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" name="allow_others_upload" value="1"
                                class="mt-0.5 h-4 w-4 rounded border-muted-300 text-primary focus:ring-primary accent-primary"
                                {{ old('allow_others_upload', $workspace->allow_others_upload) ? 'checked' : '' }}>
                            <div class="min-w-0">
                                <span class="block text-sm font-semibold text-main">{{ __('ai.allow_others_upload') }}</span>
                                <p class="mt-0.5 text-xs leading-relaxed text-muted-500">{{ __('ai.allow_others_upload_desc') }}</p>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-muted-200">
                    <button type="button" id="cancel-edit-modal"
                        class="px-5 py-2.5 rounded-xl border border-muted-300 text-sm font-medium text-muted-600 hover:bg-muted-50 transition-colors">
                        {{ __('app.btn_cancel') }}
                    </button>
                    <button type="submit"
                        class="flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover">
                        {{ __('app.btn_update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('edit-workspace-modal');
        if (!modal) return;

        const radios = modal.querySelectorAll('input[name="visibility"]');
        const d1 = document.getElementById('edit-vis-d1');
        const d2 = document.getElementById('edit-vis-d2');

        // A divider is hidden when either of its adjacent options is selected.
        // Private | [d1] | Team | [d2] | Public
        const dividerVisibility = {
            private:  [false, true],
            team:     [false, false],
            public:   [true,  false],
        };

        function updateDividers() {
            const val = modal.querySelector('input[name="visibility"]:checked')?.value;
            const [show1, show2] = dividerVisibility[val] ?? [true, true];
            if (d1) { d1.classList.toggle('opacity-100', show1); d1.classList.toggle('opacity-0', !show1); }
            if (d2) { d2.classList.toggle('opacity-100', show2); d2.classList.toggle('opacity-0', !show2); }
        }

        radios.forEach(r => r.addEventListener('change', updateDividers));
        updateDividers();
    })();
</script>
