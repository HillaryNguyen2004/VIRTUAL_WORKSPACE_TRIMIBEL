@extends('layout_dashboard')
@section('title', __('create_user.title'))

@section('content')
    {{-- Main Container --}}
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex gap-4 flex-row items-center w-full">
            @include('components.back-btn', ['route' => 'admin.users.index'])
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('create_user.title') }}</h2>
                <p class="text-muted-500 text-sm mt-1">{{ __('create_user.subtitle') }}</p>
            </div>
        </div>

        {{-- Main Card --}}
        <div class="w-full bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 relative overflow-hidden animate-fade-in-up">
            
            {{-- Decorative background element --}}
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-primary/10 rounded-full blur-2xl opacity-50 pointer-events-none"></div>

            {{-- Notifications --}}
            @if(session('success'))
                <div class="flex items-center gap-3 bg-accent/10 border border-accent/20 text-accent p-4 rounded-xl mb-6">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="flex flex-col gap-2 mb-6">
                    @foreach ($errors->all() as $error)
                        <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-500 p-4 rounded-xl">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-sm font-medium">{{ __('create_user.error_message', ['error' => $error]) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Reusable Classes --}}
            @php
                $labelClass = "block text-sm font-semibold text-main mb-2";
                $inputClass = "block w-full bg-canvas border border-muted-200 text-main py-3 px-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all";
                $btnPrimary = "group flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-white font-medium shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95";
                $btnSecondary = "group flex items-center justify-center gap-2 rounded-xl bg-accent px-6 py-3 text-white font-medium shadow-lg shadow-accent/20 transition-all hover:bg-accent-hover focus:ring-4 focus:ring-accent/30 active:scale-95";
            @endphp

            {{-- Split Layout Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 relative z-10">
                
                {{-- 1. Manual Creation Form --}}
                <div class="flex flex-col lg:border-r lg:border-muted-200 lg:pr-8">
                    <h3 class="font-bold text-lg text-main mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </span>
                        {{ __('create_user.create_new_user') }}
                    </h3>
                    
                    <form action="{{ route('admin.users.store') }}" method="POST" class="flex flex-col gap-5 h-full">
                        @csrf
                        {{-- Name --}}
                        <x-form.input
                            label="create_user.name_label"
                            name="name"
                            placeholder="create_user.name_label"
                            :isRequired="true"
                        />

                        {{-- Email --}}
                        <x-form.input
                            type="email"
                            label="create_user.email_label"
                            name="email"
                            placeholder="create_user.email_label"
                            :isRequired="true"
                        />

                        {{-- Role --}}
                        <x-form.select
                            label="create_user.role_label"
                            name="roles"
                            placeholder="create_user.select_role"
                            :isRequired="true"
                            :options="[
                                'user'  => __('create_user.user_role'),
                                'staff' => __('create_user.staff_role'),
                            ]"
                        />

                        {{-- Department --}}
                        <x-form.select
                            label="create_user.department_label"
                            name="department_id"
                            placeholder="create_user.select_department"
                            :isRequired="true"
                            :options="$departments->pluck('name', 'id')->toArray()"
                        />


                        <div class="mt-auto pt-4">
                            <button type="submit" class="{{ $btnPrimary }} w-full">
                                {{ __('create_user.submit_button') }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Mobile Divider (Visible only on small screens) --}}
                <div class="block lg:hidden w-full h-px bg-muted-200"></div>

                {{-- 2. CSV Import Form --}}
                <div class="flex flex-col lg:pl-2">
                    <h3 class="font-bold text-lg text-main mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        </span>
                        {{ __('create_user.import_csv_title') }}
                    </h3>

                    <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5 h-full">
                        @csrf
                        
                        {{-- File Input --}}
                        <div>
                            <label class="{{ $labelClass }}">{{ __('create_user.csv_label') ?? 'CSV File' }} <span class="text-danger">*</span></label>
                            <div class="relative group">
                                <input type="file" name="csv_file" accept=".csv" class="{{ $inputClass }} file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-all" required>
                            </div>
                            <p class="text-xs text-muted-500 mt-2">Please ensure your CSV matches the required format.</p>
                        </div>

                        <div class="flex flex-col @4xl:flex-row gap-3 mt-auto pt-4">
                            {{-- Import Button --}}
                            <button type="submit" class="{{ $btnSecondary }} w-full">
                                <svg class="w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path d="M352 173.3L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 173.3L246.6 214.7C234.1 227.2 213.8 227.2 201.3 214.7C188.8 202.2 188.8 181.9 201.3 169.4L297.3 73.4C309.8 60.9 330.1 60.9 342.6 73.4L438.6 169.4C451.1 181.9 451.1 202.2 438.6 214.7C426.1 227.2 405.8 227.2 393.3 214.7L352 173.3zM320 464C364.2 464 400 428.2 400 384L480 384C515.3 384 544 412.7 544 448L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 448C96 412.7 124.7 384 160 384L240 384C240 428.2 275.8 464 320 464zM464 488C477.3 488 488 477.3 488 464C488 450.7 477.3 440 464 440C450.7 440 440 450.7 440 464C440 477.3 450.7 488 464 488z" />
                                </svg>
                                {{ __('create_user.import_button') ?? 'Import' }}
                            </button>

                            {{-- Download Template Button --}}
                            <a href="{{ route('admin.users.import.template') }}" class="w-full py-3 rounded-xl border border-muted-300 text-muted-500 font-medium hover:bg-muted-100 hover:text-main transition-colors flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path d="M352 96C352 78.3 337.7 64 320 64C302.3 64 288 78.3 288 96L288 306.7L246.6 265.3C234.1 252.8 213.8 252.8 201.3 265.3C188.8 277.8 188.8 298.1 201.3 310.6L297.3 406.6C309.8 419.1 330.1 419.1 342.6 406.6L438.6 310.6C451.1 298.1 451.1 277.8 438.6 265.3C426.1 252.8 405.8 252.8 393.3 265.3L352 306.7L352 96zM160 384C124.7 384 96 412.7 96 448L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 448C544 412.7 515.3 384 480 384L433.1 384L376.5 440.6C345.3 471.8 294.6 471.8 263.4 440.6L206.9 384L160 384zM464 440C477.3 440 488 450.7 488 464C488 477.3 477.3 488 464 488C450.7 488 440 477.3 440 464C440 450.7 450.7 440 464 440z" />
                                </svg>
                                {{ __('create_user.download_template') ?? 'Download Template' }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection