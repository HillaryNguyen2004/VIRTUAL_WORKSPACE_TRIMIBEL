@extends('layout_dashboard')
@section('title', __('settings.update_profile_title'))

@section('content')
    @vite(['resources/js/settings/upload_image.js'])
    @vite(['resources/js/settings/update_detail.js'])

    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        
        {{-- Header --}}
        <div class="flex items-center gap-3">
            @include('components.back-btn', ['route' => 'profile'])

            <h1 class="text-2xl font-bold text-main">{{ __('settings.update_profile_title') }}</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full animate-fade-in-up">
            
            {{-- 1. LEFT COLUMN: Avatar Update (Col Span 4) --}}
            <div class="lg:col-span-4 h-full">
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 h-full flex flex-col items-center text-center">
                    
                    <h3 class="text-lg font-bold text-main mb-6 w-full text-left">{{ __('settings.avatar_label') }}</h3>

                    <form id="avatar-form" action="{{ route('settings.update.avatar') }}" method="POST" class="flex flex-col items-center gap-6 w-full">
                        @csrf
                        @method('PUT')
                        
                        <div class="relative group cursor-pointer">
                            {{-- Image Preview --}}
                            <img id="avatar-preview" 
                                 src="{{ getUserAvatar(Auth::user()) }}" 
                                 class="w-40 h-40 object-cover rounded-full border-4 border-muted-100 group-hover:border-primary/50 transition-colors duration-300 shadow-sm"
                                 alt="User Avatar">
                            
                            {{-- Hidden Input --}}
                            <input type="file" name="avatar" id="avatar" accept="image/jpg,image/png,image/jpeg" class="hidden">
                            
                            {{-- Camera Icon Button (Triggers File Input via JS) --}}
                            <button type="button" id="choose-avatar"
                                class="absolute bottom-0 right-0 p-3 rounded-full bg-white text-primary border border-muted-200 shadow-lg hover:bg-primary hover:text-white transition-all duration-300 group-hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 640 640">
                                    <path d="M352 173.3L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 173.3L246.6 214.7C234.1 227.2 213.8 227.2 201.3 214.7C188.8 202.2 188.8 181.9 201.3 169.4L297.3 73.4C309.8 60.9 330.1 60.9 342.6 73.4L438.6 169.4C451.1 181.9 451.1 202.2 438.6 214.7C426.1 227.2 405.8 227.2 393.3 214.7L352 173.3zM320 464C364.2 464 400 428.2 400 384L480 384C515.3 384 544 412.7 544 448L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 448C96 412.7 124.7 384 160 384L240 384C240 428.2 275.8 464 320 464zM464 488C477.3 488 488 477.3 488 464C488 450.7 477.3 440 464 440C450.7 440 440 450.7 440 464C440 477.3 450.7 488 464 488z" />
                                </svg>
                            </button>
                        </div>

                        {{-- Errors / Success --}}
                        <div class="min-h-[20px]">
                            @if ($errors->has('avatar'))
                                @foreach ($errors->get('avatar') as $error)
                                    <span id="error-avatar" class="text-danger text-xs font-medium">{{ $error }}</span>
                                @endforeach
                            @endif

                            @if (session('success_avatar'))
                                <span class="text-emerald-600 text-sm font-medium bg-emerald-50 px-3 py-1 rounded-full">{{ session('success_avatar') }}</span>
                            @endif
                        </div>

                        {{-- Submit Button --}}
                        <button id="upload-btn" type="submit"
                            class="w-full bg-primary hover:bg-primary-hover text-white rounded-xl py-2.5 font-medium transition-colors shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                            <span>{{ __('settings.upload_image_button') }}</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- 2. RIGHT COLUMN: Details Update (Col Span 8) --}}
            <div class="lg:col-span-8 h-full">
                <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-secondary/30 hover:shadow-secondary/10 transition-all duration-300 h-full">
                    
                    <h3 class="text-lg font-bold text-main mb-6 border-b border-muted-100 pb-4">{{ __('settings.detail_label') }}</h3>

                    <form id="detail-form" action="{{ route('settings.update.name') }}" method="POST" class="flex flex-col gap-6">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- First Name --}}
                            <x-form.input
                                label="settings.firstname_label"
                                name="first_name"
                                id="first_name"
                                :value="auth()->user()->first_name"
                                placeholder="settings.firstname_label"
                            />

                            {{-- Last Name --}}
                            <x-form.input
                                label="settings.lastname_label"
                                name="last_name"
                                id="last_name"
                                :value="auth()->user()->last_name"
                                placeholder="settings.lastname_label"
                            />
                        </div>

                        {{-- Success Message --}}
                        @if (session('success_name'))
                            <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600 text-sm font-medium text-center border border-emerald-100">
                                {{ session('success_name') }}
                            </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div class="flex justify-end mt-4">
                            <button id="update-detail-btn" type="submit"
                                class="px-8 py-2.5 bg-secondary hover:bg-secondary-hover text-white rounded-xl font-medium transition-colors shadow-lg shadow-secondary/20">
                                {{ __('profile.edit_profile_button') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection