@extends('layout_dashboard')
@section('title', __('projects.create_project'))

@section('content')
    @role('admin')
    {{-- Main Container --}}
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex gap-4 flex-row items-center w-full">
            @include('components.back-btn' , ['route' => 'projects.index'])
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('projects.create_project') }}</h2>
                <p class="text-muted-500 text-sm mt-1">{{ __('projects.create_subtitle') ?? 'Enter project details below' }}</p>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="w-full bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 relative overflow-hidden animate-fade-in-up">
            
            {{-- Decorative background element --}}
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-primary/10 rounded-full blur-2xl opacity-50 pointer-events-none"></div>

            {{-- Error Handling (Added for consistency) --}}
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-danger/20 bg-danger/10 p-4 text-danger">
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('projects.store') }}" method="POST" class="relative z-10">
                @csrf

                {{-- Reusable Classes --}}
                @php
                    $labelClass = "block text-sm font-semibold text-main mb-2";
                    $inputClass = "block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all";
                @endphp

                <div class="grid grid-cols-1 gap-6">
                    
                    {{-- Title --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            {{ __('projects.title') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title" class="{{ $inputClass }}" 
                               placeholder="Project Name" required>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="{{ $labelClass }}">{{ __('projects.description') }}</label>
                        <textarea name="description" class="{{ $inputClass }} min-h-[120px] resize-none"></textarea>
                    </div>

                    {{-- Staff & Status Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Assign Staff --}}
                        <div>
                            <label class="{{ $labelClass }}">
                                {{ __('projects.assign_staff') }} <span class="text-danger">*</span>
                            </label>
                            <div class="relative">
                                <select name="staff_id" class="{{ $inputClass }} appearance-none" required>
                                    <option value="" class="text-muted-400">-- Select Staff --</option>
                                    @foreach($staffUsers as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="{{ $labelClass }}">{{ __('projects.status') }}</label>
                            <div class="relative">
                                <select name="status" class="{{ $inputClass }} appearance-none">
                                    <option value="active">{{ __('projects.active') }}</option>
                                    <option value="inactive">{{ __('projects.inactive') }}</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-muted-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Date Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Start date --}}
                        <div>
                            <label class="{{ $labelClass }}">{{ __('projects.start_date') }}</label>
                            <input type="date" name="start_date" class="{{ $inputClass }}">
                        </div>

                        {{-- Due date --}}
                        <div>
                            <label class="{{ $labelClass }}">{{ __('projects.due_date') }}</label>
                            <input type="date" name="due_date" class="{{ $inputClass }}">
                        </div>
                    </div>

                </div>

                {{-- Footer / Submit --}}
                <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-muted-200">
                    <a href="{{ route('projects.index') }}" class="px-6 py-3 rounded-xl text-muted-500 font-medium hover:bg-muted-100 transition-colors">
                        {{ __('projects.cancel') }}
                    </a>
                    <button type="submit" class="group flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                        {{ __('projects.create') }}
                    </button>
                </div>
            </form>

        </div>
    </div>
    @endrole
@endsection