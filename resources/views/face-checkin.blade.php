@extends('layout_dashboard')
@section('title', 'Face Recognition Check-in')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        // Determine dashboard route based on role
        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('subadmin') && Route::has('subadmin.dashboard')) {
            $dashRoute = 'subadmin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        } elseif (auth()->user()->hasRole('substaff') && Route::has('substaff.dashboard')) {
            $dashRoute = 'substaff.dashboard';
        }
    @endphp
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        <div class="flex flex-col gap-4 @2xl:flex-row @2xl:items-center w-full">
            @include('components.back-btn' , ['route' => $dashRoute])
            <div>
                <h2 class="font-bold text-3xl text-main tracking-tight">
                    @if($checkType === 'checkin')
                        Face Check In
                    @else
                        Face Check Out
                    @endif
                </h2>
                <p class="text-muted-500 text-sm mt-1">Position your face inside the circle for verification</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Camera Section -->
            <div class="lg:col-span-2 bg-white border border-muted-200 shadow-lg shadow-main/5 rounded-2xl p-6">
                <div class="text-center">
                    <!-- Camera feed container -->
                    <div class="relative mx-auto" style="width: 640px; height: 480px;">
                        <video id="video" width="640" height="480" autoplay muted playsinline
                            class="rounded-xl border-2 border-muted-300 bg-gray-900">
                        </video>

                        <!-- Face guide overlay -->
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 
                            w-64 h-64 border-4 border-dashed border-accent rounded-full 
                            opacity-70 pointer-events-none"></div>

                        <!-- Canvas for face detection -->
                        <canvas id="canvas" width="640" height="480"
                            class="absolute top-0 left-0 pointer-events-none"></canvas>
                    </div>

                    <!-- Status messages -->
                    <div id="statusContainer" class="mt-6">
                        <div id="status" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-muted-100">
                            <div id="statusSpinner" class="spinner-border spinner-border-sm text-primary" role="status">
                            </div>
                            <span id="statusText" class="text-muted-600">Initializing camera...</span>
                        </div>

                        <div id="detectionStatus" class="mt-2 text-sm text-muted-500">
                            Please position your face inside the circle
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions Section -->
            <div class="bg-white border border-muted-200 shadow-lg shadow-main/5 rounded-2xl p-6">
                <h3 class="text-lg font-bold text-main mb-4">Instructions</h3>

                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="text-primary text-sm font-bold">1</span>
                        </div>
                        <span class="text-muted-600">Make sure you're in a well-lit area</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="text-primary text-sm font-bold">2</span>
                        </div>
                        <span class="text-muted-600">Position your face inside the circle</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="text-primary text-sm font-bold">3</span>
                        </div>
                        <span class="text-muted-600">Look directly at the camera</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="text-primary text-sm font-bold">4</span>
                        </div>
                        <span class="text-muted-600">Hold still for 2 seconds</span>
                    </li>
                </ul>

                <div class="mt-6 p-4 bg-muted-50 rounded-lg">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-user-circle text-accent mt-1"></i>
                        <div>
                            <p class="font-medium text-main">{{ auth()->user()->name }}</p>
                            <p class="text-sm text-muted-500">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>

                @if($workingHour)
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                        <h4 class="font-medium text-blue-800 mb-2">Working Hours</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-muted-500">Start:</span>
                                <span class="ml-2 font-medium text-blue-700">
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->start_at)->format('H:i') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-muted-500">End:</span>
                                <span class="ml-2 font-medium text-blue-700">
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->end_at)->format('H:i') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Hidden form data -->
    <input type="hidden" id="checkType" value="{{ $checkType }}">
    <input type="hidden" id="username" value="{{ auth()->user()->username }}">
    <input type="hidden" id="csrfToken" value="{{ csrf_token() }}">
    <input type="hidden" id="faceProcessUrl" value="{{ route('checkin.face.process') }}">
@endsection

@push('styles')
    <style>
        .spinner-border {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            vertical-align: text-bottom;
            border: 0.15em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
        }

        /* #video {
                transform: scaleX(-1);
                /* Mirror the video for better UX
            } */
    </style>
@endpush

@push('scripts')
    <!-- Load face-api.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <!-- Include the face check-in script -->
    @vite(['resources/js/face-checkin.js'])
@endpush