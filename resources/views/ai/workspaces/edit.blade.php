@extends('layout_dashboard')
@section('title', __('ai.edit_workspace'))

@section('content')
    <div class="flex flex-col gap-6 w-full max-w-2xl mx-auto text-main px-4 md:px-8 py-8">
        {{-- HEADER --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full mb-4">
            <div class="flex items-center gap-4">
                <x-back-btn :route="'ai-workspaces.show'" :params="['ai_workspace' => $workspace->id]" />
                <div>
                    <h2 class="font-bold text-3xl text-main tracking-tight">
                        {{ __('ai.edit_workspace') }}
                    </h2>
                </div>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="w-full bg-white rounded-2xl p-8 border border-muted-200 shadow-lg shadow-main/5 relative overflow-hidden animate-fade-in-up">
            {{-- Decorative background element --}}
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-primary/10 rounded-full blur-2xl opacity-50 pointer-events-none"></div>

            <form action="{{ route('ai-workspaces.update', $workspace) }}" method="POST" class="relative z-10">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6">
                    {{-- Name Field --}}
                    <x-form.input
                        label="ai.workspace_name"
                        name="name"
                        :value="old('name', $workspace->name)"
                        placeholder="ai.workspace_name_placeholder"
                        :isRequired="true"
                    />

                    {{-- Description Field --}}
                    <x-form.textarea
                        label="ai.description"
                        name="description"
                        :value="old('description', $workspace->description)"
                        placeholder="ai.description_placeholder"
                        :rows="4"
                    />

                    {{-- Visibility Field --}}
                    <div>
                        <label class="block text-sm font-semibold text-main mb-4">
                            {{ __('ai.visibility') }}
                            <span class="text-danger">*</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Private --}}
                            <label class="relative flex flex-col p-4 rounded-xl border border-muted-200 cursor-pointer hover:bg-muted-50 transition-all group has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                                <input type="radio" name="visibility" value="private" class="absolute opacity-0" {{ old('visibility', $workspace->visibility) === 'private' ? 'checked' : '' }} required>
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-500 group-has-[:checked]:bg-blue-500 group-has-[:checked]:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    </div>
                                    <span class="font-bold text-main">{{ __('ai.visibility_private') }}</span>
                                </div>
                                <p class="text-muted-500 text-xs leading-relaxed ml-11">{{ __('ai.visibility_private_desc') }}</p>
                            </label>

                            {{-- Team --}}
                            <label class="relative flex flex-col p-4 rounded-xl border border-muted-200 cursor-pointer hover:bg-muted-50 transition-all group has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                                <input type="radio" name="visibility" value="team" class="absolute opacity-0" {{ old('visibility', $workspace->visibility) === 'team' ? 'checked' : '' }}>
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center text-violet-500 group-has-[:checked]:bg-violet-500 group-has-[:checked]:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <span class="font-bold text-main">{{ __('ai.visibility_team') }}</span>
                                </div>
                                <p class="text-muted-500 text-xs leading-relaxed ml-11">{{ __('ai.visibility_team_desc') }}</p>
                            </label>

                            {{-- Public --}}
                            <label class="relative flex flex-col p-4 rounded-xl border border-muted-200 cursor-pointer hover:bg-muted-50 transition-all group has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                                <input type="radio" name="visibility" value="public" class="absolute opacity-0" {{ old('visibility', $workspace->visibility) === 'public' ? 'checked' : '' }}>
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-8 h-8 rounded-lg bg-green-500/10 flex items-center justify-center text-green-500 group-has-[:checked]:bg-green-500 group-has-[:checked]:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                    </div>
                                    <span class="font-bold text-main">{{ __('ai.visibility_public') }}</span>
                                </div>
                                <p class="text-muted-500 text-xs leading-relaxed ml-11">{{ __('ai.visibility_public_desc') }}</p>
                            </label>
                        </div>
                        @error('visibility')
                            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Footer / Submit --}}
                <div class="flex items-center justify-end gap-3 mt-10 pt-6 border-t border-muted-200">
                    <button type="submit" class="group flex items-center justify-center gap-2 rounded-xl bg-primary px-8 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                        <span>{{ __('app.btn_update') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
