@extends('layout_dashboard')
@section('title', isset($campaign) ? __('campaigns_create.edit_campaign') : __('campaigns_create.create_new_campaign'))

@section('content')
    {{-- Main Container --}}
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex gap-4 flex-row items-center w-full">
            @include('components.back-btn' , ['route' => 'campaigns.index'])
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">
                    {{ isset($campaign) ? __('campaigns_create.edit_campaign') : __('campaigns_create.create_new_campaign') }}
                </h2>
                <p class="text-muted-500 text-sm mt-1">
                    {{ __('campaigns_create.subtitle') ?? 'Manage your email campaign details' }}
                </p>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="w-full bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 relative overflow-hidden animate-fade-in-up">
            
            {{-- Decorative background element --}}
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-primary/10 rounded-full blur-2xl opacity-50 pointer-events-none"></div>

            {{-- Success Message --}}
            @if(session('success'))
                <div class="flex items-center gap-3 bg-accent/10 border border-accent/20 text-accent p-4 rounded-xl mb-6">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Error Handling --}}
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-danger/20 bg-danger/10 p-4 text-danger">
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ isset($campaign) ? route('campaigns.update', $campaign) : route('campaigns.store') }}" class="relative z-10">
                @csrf
                @if(isset($campaign))
                    @method('PUT')
                @endif

                {{-- Reusable Classes --}}
                @php
                    $labelClass = "block text-sm font-semibold text-main mb-2";
                    $inputClass = "block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all";
                @endphp

                <div class="grid grid-cols-1 gap-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Campaign Name --}}
                    <x-form.input
                        label="campaigns_create.campaign_name"
                        name="name"
                        :value="$campaign->name ?? ''"
                        placeholder="campaigns_create.enter_campaign_name"
                        :isRequired="true"
                    />

                    {{-- Subject --}}
                    <x-form.input
                        label="campaigns_create.subject"
                        name="subject"
                        :value="$campaign->subject ?? ''"
                        placeholder="campaigns_create.enter_email_subject"
                    />

                    </div>

                    {{-- Send To All Toggle --}}
                    <div class="flex items-center gap-3 p-4 border border-muted-200 rounded-xl bg-canvas/50">
                        <input type="checkbox" name="send_to_all" id="send_to_all" 
                            class="w-5 h-5 text-accent rounded border-muted-300 focus:ring-accent/20"
                            {{ old('send_to_all') ? 'checked' : '' }}>
                        <label for="send_to_all" class="text-sm font-semibold text-main cursor-pointer select-none">
                            {{ __('campaigns_create.send_to_all_users') }}
                        </label>
                    </div>

                    {{-- User Selection (Conditional) --}}
                    <div id="user-select-wrapper" style="{{ old('send_to_all') ? 'display: none;' : '' }}">
                        <label class="{{ $labelClass }}">{{ __('campaigns_create.select_users') }}</label>
                        <div class="relative">
                            {{-- Note: Standard multi-select styled to match theme. 
                                 If you require search functionality, a JS library like Alpine or Choices.js would be needed here. --}}
                            <select name="users[]" multiple class="{{ $inputClass }} min-h-[150px]">
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" class="py-1 px-2"
                                        {{ (collect(old('users', isset($campaign) ? $campaign->users->pluck('id')->toArray() : []))->contains($user->id)) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-xs text-muted-500 mt-2">Hold Ctrl (Windows) or Cmd (Mac) to select multiple users.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Email Template --}}
                        <x-form.select
                            label="campaigns_create.email_template"
                            name="email_template_id"
                            :value="$campaign->email_template_id ?? ''"
                            :options="$templates->pluck('name','id')"
                        />

                        {{-- Schedule At --}}
                        <div>
                            <x-form.input
                                type="datetime-local"
                                label="campaigns_create.schedule_at"
                                name="scheduled_at"
                                :value="isset($campaign->scheduled_at) ? \Carbon\Carbon::parse($campaign->scheduled_at)->format('Y-m-d\TH:i') : ''"
                            />

                            <p class="text-xs text-muted-500 mt-1">{{ __('campaigns_create.schedule_at_hint') }}</p>
                        </div>

                    </div>
                </div>

                {{-- Footer / Submit --}}
                <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-muted-200">
                    
                    {{-- Left side: Cancel --}}
                    <a href="{{ route('campaigns.index') }}" class="px-6 py-3 rounded-xl text-muted-500 font-medium hover:bg-muted-100 transition-colors">
                        {{ __('campaigns_create.cancel') ?? 'Cancel' }}
                    </a>

                    <div class="flex items-center gap-3">
                        {{-- Optional: Sync Button (Only on Edit) --}}
                        @if(isset($campaign) && $campaign->email_template_id)
                            <button form="sync-form" type="submit" class="px-4 py-3 rounded-xl text-amber-600 bg-amber-50 font-medium hover:bg-amber-100 transition-colors">
                                {{ __('campaigns_create.sync_with_template') }}
                            </button>
                        @endif

                        {{-- Main Save Button --}}
                        <button type="submit" class="group flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                            {{ isset($campaign) ? __('campaigns_create.update_campaign') : __('campaigns_create.save_and_schedule') }}
                        </button>
                    </div>
                </div>
            </form>
            
            {{-- Hidden Sync Form --}}
            @if(isset($campaign) && $campaign->email_template_id)
                <form id="sync-form" action="{{ route('campaigns.syncTemplate', $campaign->id) }}" method="POST" class="hidden">
                    @csrf
                </form>
            @endif

            {{-- Logic for toggling User Select --}}
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const sendToAllCheckbox = document.getElementById('send_to_all');
                    const userWrapper = document.getElementById('user-select-wrapper');

                    if(sendToAllCheckbox && userWrapper) {
                        function toggleUserSelect() {
                            if (sendToAllCheckbox.checked) {
                                userWrapper.style.display = 'none';
                            } else {
                                userWrapper.style.display = 'block';
                            }
                        }

                        // Initial check
                        toggleUserSelect();

                        // Listener
                        sendToAllCheckbox.addEventListener('change', toggleUserSelect);
                    }
                });
            </script>

        </div>
    </div>
@endsection