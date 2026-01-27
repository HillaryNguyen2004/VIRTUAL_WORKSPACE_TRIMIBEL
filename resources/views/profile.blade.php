@extends('layout_dashboard')
@section('title', __('profile.profile_title'))

@section('content')
@vite(['resources/js/toggle_view.js'])

@php
    use Illuminate\Support\Facades\Route;

    // Determine dashboard route based on role
    $dashRoute = 'user.dashboard';
    if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
        $dashRoute = 'admin.dashboard';
    } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
        $dashRoute = 'staff.dashboard';
    }
@endphp
<div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 @3xl:px-8 @4xl:px-16 @5xl:px-24 py-8">
    
    {{-- Back Button & Title --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            @include('components.back-btn', ['route' => $dashRoute])
            <h1 class="text-2xl font-bold text-main">{{ __('profile.profile_title') }}</h1>
        </div>

        {{-- Edit Button --}}
        <a href="{{ route('settings') }}"
            class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-primary/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <span class="font-medium">{{ __('profile.edit_profile_button') }}</span>
        </a>
    </div>

    {{-- 1. TOP HEADER SECTION --}}
    <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 flex flex-col @4xl:flex-row items-start @4xl:items-center gap-6 @4xl:gap-12 animate-fade-in-up">
        
        {{-- Left: Avatar & Identity --}}
        <div class="flex items-center gap-6 w-full @4xl:w-auto">
            <div class="relative group">
                <img src="{{ getUserAvatar(Auth::user()) }}" 
                     class="w-24 h-24 @4xl:w-32 @4xl:h-32 rounded-full object-cover group-hover:scale-105 transition-transform duration-300" 
                     alt="User Avatar">
                <div class="absolute inset-0 rounded-full shadow-inner pointer-events-none"></div>
            </div>
            
            <div class="flex flex-col gap-1">
                <h2 class="text-2xl @4xl:text-3xl font-bold text-main tracking-tight">{{ Auth::user()->name }}</h2>
                <div class="flex items-center gap-2 text-muted-500 font-medium">
                    {{-- Role / Position --}}
                    <span class="text-primary">{{ Auth::user()->roles->first()->name ?? 'Staff' }}</span>
                    <span class="text-muted-300">|</span>
                    <span>{{ Auth::user()->department?->name ?? 'Product Department' }}</span>
                </div>
            </div>
        </div>

        {{-- Vertical Divider (Desktop) --}}
        <div class="hidden @4xl:block w-px h-24 bg-muted-200"></div>

        {{-- Right: Contact/ID Grid --}}
        <div class="grid grid-cols-1 @3xl:grid-cols-2 gap-x-8 gap-y-4 w-full @4xl:flex-1">
            
            {{-- ID --}}
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-muted-400 uppercase tracking-wide">Staff ID</span>
                <span class="text-main font-semibold">{{ Auth::user()->staff_id ?? 'SJ53862' }}</span>
            </div>

            {{-- Phone --}}
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-muted-400 uppercase tracking-wide">Phone number</span>
                <span class="text-main font-semibold">{{ Auth::user()->phone ?? '0913 854 235' }}</span>
            </div>

            {{-- Account --}}
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-muted-400 uppercase tracking-wide">Staff Account</span>
                <span class="text-main font-semibold">{{ Auth::user()->username }}</span>
            </div>

            {{-- Email --}}
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-muted-400 uppercase tracking-wide">Email</span>
                <span class="text-main font-semibold break-all">{{ Auth::user()->email }}</span>
            </div>
        </div>
    </div>

    {{-- 2. MAIN CONTENT GRID --}}
    <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up [animation-delay:150ms]">
        
        {{-- LEFT COLUMN: Personal Information (Spans 5 cols) --}}
        <div class="@4xl:col-span-5 flex flex-col h-full">
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-primary/30 hover:shadow-primary/10 transition-all duration-300 h-full relative group">
                
                {{-- Card Header --}}
                <div class="flex items-center justify-between mb-6 border-b border-muted-100 pb-4">
                    <h3 class="text-lg font-bold text-main">Personal information</h3>
                    <a href="{{ route('settings') }}" class="p-2 rounded-lg hover:bg-muted-50 text-muted-400 hover:text-primary transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </a>
                </div>

                {{-- Card Body: Key/Value Grid --}}
                <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                    
                    <div>
                        <p class="text-xs text-muted-400 mb-1">Gender</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->gender ?? 'Female' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-400 mb-1">Date of birth</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->dob ? \Carbon\Carbon::parse(Auth::user()->dob)->format('jS F, Y') : '5th March, 1996' }}</p>
                    </div>

                    <div class="col-span-2">
                        <p class="text-xs text-muted-400 mb-1">Identify code</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->identity_code ?? '3234611342' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-400 mb-1">Nationality</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->nationality ?? 'Vietnam' }}</p>
                    </div>

                     <div>
                        <p class="text-xs text-muted-400 mb-1">Religion</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->religion ?? 'None' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-400 mb-1">Language</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->language ?? 'Vietnamese, English' }}</p>
                    </div>

                     <div>
                        <p class="text-xs text-muted-400 mb-1">Marital status</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->marital_status ?? 'Single' }}</p>
                    </div>

                    <div class="col-span-2">
                        <p class="text-xs text-muted-400 mb-1">Permanent address</p>
                        <p class="text-sm font-semibold text-main leading-relaxed">
                            {{ Auth::user()->permanent_address ?? '5. Nguyen Chi Thanh Street, Tan Binh Ward, Hai Duong' }}
                        </p>
                    </div>

                    <div class="col-span-2">
                        <p class="text-xs text-muted-400 mb-1">Current address</p>
                        <p class="text-sm font-semibold text-main leading-relaxed">
                            {{ Auth::user()->address ?? '29. Nguyen Ngoc Doan Street, Dong Da District, Ha Noi' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Education & Account (Spans 7 cols) --}}
        <div class="@4xl:col-span-7 flex flex-col gap-6">
            
            {{-- Education Card --}}
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-secondary/30 hover:shadow-secondary/10 transition-all duration-300">
                <div class="flex items-center justify-between mb-6 border-b border-muted-100 pb-4">
                    <h3 class="text-lg font-bold text-main">Education information</h3>
                    <a href="{{ route('settings') }}" class="p-2 rounded-lg hover:bg-muted-50 text-muted-400 hover:text-primary transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </a>
                </div>

                <div class="flex flex-col gap-6">
                    {{-- Item 1 --}}
                    <div class="flex flex-col @2xl:flex-row @2xl:justify-between @2xl:items-start gap-1">
                        <div>
                            <p class="font-bold text-main">Bachelor in Management Information System</p>
                            <p class="text-sm text-muted-400">National Economic University</p>
                        </div>
                        <span class="text-sm font-semibold text-main">2014-2018</span>
                    </div>
                    
                    {{-- Item 2 --}}
                    <div class="flex flex-col @2xl:flex-row @2xl:justify-between @2xl:items-start gap-1">
                         <div>
                            <p class="font-bold text-main">Certificate of Graphic Design</p>
                            <p class="text-sm text-muted-400">FPT Arena University</p>
                        </div>
                        <span class="text-sm font-semibold text-main">2018-2019</span>
                    </div>
                </div>
            </div>

            {{-- Account Information Card --}}
            <div class="bg-white rounded-2xl p-6 border border-muted-200 shadow-lg shadow-main/5 hover:border-accent/50 hover:shadow-accent/10 transition-all duration-300">
                <div class="flex items-center justify-between mb-6 border-b border-muted-100 pb-4">
                    <h3 class="text-lg font-bold text-main">Account information</h3>
                    <a href="{{ route('settings') }}" class="p-2 rounded-lg hover:bg-muted-50 text-muted-400 hover:text-primary transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </a>
                </div>

                <div class="grid grid-cols-1 @2xl:grid-cols-2 gap-y-6 gap-x-4">
                     <div>
                        <p class="text-xs text-muted-400 mb-1">Bank account</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->bank_account_number ?? '02520613401' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-400 mb-1">Account name</p>
                        <p class="text-sm font-semibold text-main uppercase">{{ Auth::user()->bank_account_name ?? Auth::user()->name }}</p>
                    </div>

                    <div class="@2xl:col-span-2">
                        <p class="text-xs text-muted-400 mb-1">Bank</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->bank_name ?? 'TPBank Duy Tan' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-400 mb-1">Tax code</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->tax_code ?? '8456120546' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-muted-400 mb-1">Insurance code</p>
                        <p class="text-sm font-semibold text-main">{{ Auth::user()->insurance_code ?? '8456120546' }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection