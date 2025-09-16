@props(['title' => '', 'campaign' => null, 'users' => collect(), 'templates' => collect()])

@php
    $isEdit = isset($campaign);
    $formAction = isset($campaign) ? route('campaigns.update', $campaign) : route('campaigns.store')
@endphp

<x-form-layout :title="$title">
    {{-- form --}}
    <form action="{{ $formAction }}" method="POST" class="flex flex-col items-center gap-3 w-full py-6 px-8">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full">
            {{-- name --}}
            <div class="flex flex-col gap-1 text-sm xl:text-lg">
                <label class="">{{ __('campaigns_create.campaign_name') }} <span class="text-red-600">*</span></label>
                <input type="text" name="name"
                    class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    placeholder="{{ __('campaigns_create.campaign_name') }}"
                    value="{{ old('name', $campaign->name ?? '') }}" required>
            </div>

            <div class="flex flex-col gap-1 text-sm xl:text-lg">
                <label class="">{{ __('campaigns_create.subject') }}</label>
                <input type="text" name="subject"
                    class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    value="{{ old('subject', $campaign->subject ?? '') }}"
                    placeholder="{{ __('campaigns_create.enter_email_subject') }}">
            </div>

            <div class="flex flex-col gap-1 text-sm xl:text-lg">
                <label class="form-label">{{ __('campaigns_create.email_template') }} <span class="text-red-600">*</span></label>
                <select name="email_template_id" class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition" required>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" {{ old('email_template_id', $campaign->email_template_id ?? '') == $template->id ? 'selected' : '' }}>
                            {{ $template->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1 text-sm xl:text-lg">
                <label for="scheduled_at" class="">{{ __('campaigns_create.schedule_at') }}</label>
                <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                    class="rounded-xl border border-gray-300 px-4 h-11 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    value="{{ old('scheduled_at', isset($campaign->scheduled_at) ? \Carbon\Carbon::parse($campaign->scheduled_at)->format('Y-m-d\TH:i') : '') }}">
                <p class="text-gray-400 text-xs md:text-sm">{{ __('campaigns_create.schedule_at_hint') }}</p>
            </div>

            <div class="flex gap-2 text-sm xl:text-lg md:col-span-2">
                <input type="checkbox" name="send_to_all" id="send_to_all" class="" {{ old('send_to_all') ? 'checked' : '' }}>
                <label for="send_to_all" class="form-check-label">{{ __('campaigns_create.send_to_all_users') }}</label>
            </div>

            <div class="flex flex-col gap-1 text-sm xl:text-lg md:col-span-2">
                <label class="">{{ __('campaigns_create.select_users') }}</label>
                <select id="users" name="users[]"
                    class="rounded-xl border border-gray-300 placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                    multiple>
                    @foreach($users as $user)
                        <option class="px-4 py-2" value="{{ $user->id }}" {{ (collect(old('users', isset($campaign) ? $campaign->users->pluck('id')->toArray() : []))->contains($user->id)) ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                <p class="text-gray-400 text-xs md:text-sm">Press <span class="font-semibold">Command</span> (for Macos)
                    / <span class="font-semibold">Shift</span> (for Windows) and click to choose multiple options</p>
            </div>
        </div>

        <button type="submit"
            class="px-4 py-2 mt-4 w-full sm:w-52 bg-[#5D3FD3] hover:opacity-95 text-white text-center rounded-xl shadow-[0_8px_24px_rgba(99,102,241,0.35)] transition">
            {{ __('campaigns_create.save_and_schedule') }}
        </button>
    </form>

    @if(isset($campaign) && $campaign->email_template_id)
        <form action="{{ route('campaigns.syncTemplate', $campaign->id) }}" method="POST" class="">
            @csrf
            <button type="submit" class="">
                {{ __('campaigns_create.sync_with_template') }}
            </button>
        </form>
    @endif
</x-form-layout>