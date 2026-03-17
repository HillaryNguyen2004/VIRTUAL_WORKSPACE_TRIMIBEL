@extends('layout_dashboard')
@section('title', __('user_dashboard.title'))

@section('content')
    @role('user')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        
        <div class="flex flex-row gap-4 justify-between @2xl:items-center w-full">
            <div>
                <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">{{ __('user_dashboard.heading') }}</h1>
                <p class="text-muted-500 text-sm md:text-base mt-1">{{ __('user_dashboard.subheading') }}</p>
            </div>
            
            <button
                id="open-request-dayoff"
                class="group flex items-center justify-center gap-2 rounded-xl bg-primary-gradient px-6 py-3 text-white text-md md:text-base font-semibold shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ __('user_dashboard.request_day_off') }}
            </button>
        </div>

        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full">
            
            {{-- Attendance Check --}}
            @include('components.dashboard_widgets.check_attendance')

            <div class="@4xl:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4 animate-fade-in-up [animation-delay:100ms]">
                @php
                    // We map these to your new palette
                    $statCards = [
                        [
                            'label' => __('user_dashboard.earnings_monthly'),
                            'value' => '$40,000',
                            'icon' => '<path d="M16 2v4M8 2v4M3 10h18" />',
                            'color' => 'text-primary', // Cyan
                            'bg' => 'bg-primary/10',
                            'hover' => 'hover:border-primary/50',
                        ],
                        [
                            'label' => __('user_dashboard.earnings_annual'),
                            'value' => '$215,000',
                            'icon' => '<path d="M12 1v22M17 5H9a3 3 0 000 6h6a3 3 0 010 6H7" />',
                            'color' => 'text-primary', // Cyan
                            'bg' => 'bg-primary/10',
                            'hover' => 'hover:border-primary/50',
                        ],
                        [
                            'label' => __('user_dashboard.tasks'),
                            'value' => '50%',
                            'icon' => '<path d="M8 7h8M4 3h16v18H4z" /><path d="m9 14 2 2 4-5" />',
                            'color' => 'text-secondary', // Blue
                            'bg' => 'bg-secondary/10',
                            'hover' => 'hover:border-secondary/30',
                        ],
                        [
                            'label' => __('user_dashboard.pending_requests'),
                            'value' => '18',
                            'icon' => '<rect x="3" y="4" width="18" height="16" rx="2" /><path d="M7 8h10" />',
                            'color' => 'text-secondary', // Blue
                            'bg' => 'bg-secondary/10',
                            'hover' => 'hover:border-secondary/30',
                        ],
                    ];
                @endphp

                @foreach($statCards as $card)
                <div class="bg-white rounded-2xl p-6 border border-muted-300  {{ $card['hover'] }} transition-all duration-300 group">
                    <div class="flex items-start h-full justify-between">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-muted-500 font-medium text-xs md:text-sm">{{ $card['label'] }}</p>
                            <p class="text-2xl md:text-3xl font-semibold text-main tracking-tight">{{ $card['value'] }}</p>
                        </div>
                        <div class="p-3 rounded-xl {{ $card['bg'] }} {{ $card['color'] }} group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                {!! $card['icon'] !!}
                            </svg>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full animate-fade-in-up [animation-delay:200ms]">
            @if ($teamLeader)
                <div class="@4xl:col-span-7 grid grid-cols-1 container @2xl:grid-cols-2 @4xl:grid-cols-5 gap-6">
                    <div class="@2xl:col-span-1 @4xl:col-span-2 bg-white border border-muted-300 hover:border-primary/30 transition-colors rounded-2xl p-6 flex flex-col items-center text-center">
                        <div class="relative my-auto">
                            <div class="absolute inset-0 bg-primary/20 rounded-full blur-lg opacity-50"></div>
                            <img src="{{ $teamLeader->user_profile_photo ?? '/img/undraw_profile_2.svg' }}" alt="leader_avatar" class="relative w-24 h-24 rounded-full ring ring-muted-200 ring-offset-8 object-cover">
                        </div>
                        
                        <div class="py-4">
                            <h4 class="text-md md:text-lg font-semibold text-main">{{ $teamLeader->name }}</h4>
                            <span class="inline-block mt-1 px-3 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full uppercase tracking-wide">
                                {{ __('user_dashboard.team_leader') }}
                            </span>
                            <a href="mailto:{{ $teamLeader->email }}" class="mt-3 text-muted-500 text-xs md:text-sm hover:text-primary transition-colors flex items-center justify-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                {{ $teamLeader->email }}
                            </a>
                        </div>
                    </div>

                    <div class="@2xl:col-span-1 @4xl:col-span-3 bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 rounded-2xl p-6 flex-1">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.team_members') }}</h4>
                            <button id="open-team-member" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                                </svg>
                            </button>
                        </div>
                        
                        @if($teamMembers->isNotEmpty())
                            <ul class="flex flex-col gap-4">
                                @foreach($teamMembers->take(3) as $member)
                                    <li class="flex items-center gap-4 group">
                                        <div class="h-10 w-10 rounded-full bg-muted-100 text-muted-600 border border-muted-200 grid place-items-center font-semibold text-sm  transition-colors">
                                            {{ mb_substr($member->name ?? '', 0, 1) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs md:text-sm font-medium text-main truncate" title="{{ $member->name }}">
                                                {{ $member->name }}
                                            </p>
                                            <p class="text-xs text-muted-400 truncate">Member</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-6 text-muted-400 text-xs md:text-sm">
                                {{ __('user_dashboard.no_team_members') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="@4xl:col-span-5 bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 rounded-2xl p-6 h-max">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.assigned_projects') }}</h4>
                        <a href="{{ route('tasks.index') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                            </svg>
                        </a>
                    </div>

                    <div class="w-full">
                        <div class="grid grid-cols-12 gap-4 pb-3 border-b border-muted-200 text-xs font-semibold text-muted-400 uppercase tracking-wider">
                            <div class="col-span-8">{{ __('user_dashboard.tasks') }}</div>
                            <div class="col-span-4 text-right">{{ __('user_dashboard.task_status') }}</div>
                        </div>
                        
                        @if ($assignedTasks->isNotEmpty())
                            <ul class="flex flex-col divide-y divide-muted-100">
                                @foreach ($assignedTasks->take(3) as $task)
                                    @php
                                        // Mapping statuses to your specific palette
                                        $statusConfig = [
                                            // Pending -> Muted/Neutral
                                            'pending' => ['bg' => 'bg-muted-100', 'text' => 'text-muted-600', 'ring' => 'ring-muted-500/10'],
                                            // In Progress -> Secondary (Blue)
                                            'in_progress' => ['bg' => 'bg-secondary/10', 'text' => 'text-secondary', 'ring' => 'ring-secondary/20'],
                                            // Completed -> Accent (Cyan) 
                                            'completed' => ['bg' => 'bg-accent/10', 'text' => 'text-accent', 'ring' => 'ring-accent/20'],
                                        ];
                                        
                                        $currentStatus = $statusConfig[$task->status] ?? $statusConfig['pending'];
                                        // Check if task is inactive
                                        $isInactive = !$task->active; // Assuming 'active' is a boolean field
                                    @endphp
                                    <li class="grid grid-cols-12 gap-4 py-4 items-center hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                                        <div class="col-span-8 flex items-center gap-3">
                                            {{-- Small indicator dot --}}
                                            <div class="w-2 h-2 rounded-full {{ str_replace('bg-', 'bg-', $currentStatus['text']) }} opacity-50"></div>
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                @if($task->isUnread())
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50 flex-shrink-0 animate-pulse" title="New/Updated"></span>
                                                @endif
                                                <a href="{{ route('tasks.details', $task->id) }}" class="text-sm font-medium text-main truncate hover:text-primary hover:underline" title="{{ $task->title }}">{{ $task->title }}</a>
                                                @if($isInactive)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger/10 text-danger ring-1 ring-inset ring-danger/20 whitespace-nowrap">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-span-4 flex justify-end">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $currentStatus['bg'] }} {{ $currentStatus['text'] }} ring-1 ring-inset {{ $currentStatus['ring'] }}">
                                                {{ __('user_dashboard.status_' . $task->status) }}
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-10">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-muted-100 mb-3">
                                    <svg class="w-6 h-6 text-muted-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <p class="text-muted-500 text-xs md:text-sm">{{ __('user_dashboard.no_projects_assigned') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="@4xl:col-span-5 bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 rounded-2xl p-6 flex-1">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.team_members') }}</h4>
                        <button id="open-team-member" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                            </svg>
                        </button>
                    </div>
                    
                    @if($teamMembers->isNotEmpty())
                        <ul class="flex flex-col gap-4">
                            @foreach($teamMembers->take(3) as $member)
                                <li class="flex items-center gap-4 group">
                                    <div class="h-10 w-10 rounded-full bg-muted-100 text-muted-600 border border-muted-200 grid place-items-center font-semibold text-xs md:text-sm transition-colors">
                                        {{ mb_substr($member->name ?? '', 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs md:text-sm font-medium text-main truncate" title="{{ $member->name }}">
                                            {{ $member->name }}
                                        </p>
                                        <p class="text-xs text-muted-400 truncate">Member</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-6 text-muted-400 text-xs md:text-sm">
                            {{ __('user_dashboard.no_team_members') }}
                        </div>
                    @endif
                </div>

                <div class="@4xl:col-span-7 bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 rounded-2xl p-6 h-max">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.assigned_projects') }}</h4>
                        <a href="{{ route('tasks.index') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                            </svg>
                        </a>
                    </div>

                    <div class="w-full">
                        <div class="grid grid-cols-12 gap-4 pb-3 border-b border-muted-200 text-xs font-semibold text-muted-400 uppercase tracking-wider">
                            <div class="col-span-8">{{ __('user_dashboard.tasks') }}</div>
                            <div class="col-span-4 text-right">{{ __('user_dashboard.task_status') }}</div>
                        </div>
                        
                        @if ($assignedTasks->isNotEmpty())
                            <ul class="flex flex-col divide-y divide-muted-100">
                                @foreach ($assignedTasks->take(3) as $task)
                                    @php
                                        // Mapping statuses to your specific palette
                                        $statusConfig = [
                                            // Pending -> Muted/Neutral
                                            'pending' => ['bg' => 'bg-muted-100', 'text' => 'text-muted-600', 'ring' => 'ring-muted-500/10'],
                                            // In Progress -> Secondary (Blue)
                                            'in_progress' => ['bg' => 'bg-secondary/10', 'text' => 'text-secondary', 'ring' => 'ring-secondary/20'],
                                            // Completed -> Accent (Cyan) 
                                            'completed' => ['bg' => 'bg-accent/10', 'text' => 'text-accent', 'ring' => 'ring-accent/20'],
                                        ];
                                        
                                        $currentStatus = $statusConfig[$task->status] ?? $statusConfig['pending'];
                                        // Check if task is inactive
                                        $isInactive = !$task->active; // Assuming 'active' is a boolean field
                                    @endphp
                                    <li class="grid grid-cols-12 gap-4 py-4 items-center hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                                        <div class="col-span-8 flex items-center gap-3">
                                            {{-- Small indicator dot --}}
                                            <div class="w-2 h-2 rounded-full {{ str_replace('bg-', 'bg-', $currentStatus['text']) }} opacity-50"></div>
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                @if($task->isUnread())
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50 flex-shrink-0 animate-pulse" title="New/Updated"></span>
                                                @endif
                                                <a href="{{ route('tasks.details', $task->id) }}" class="text-sm font-medium text-main truncate hover:text-primary hover:underline" title="{{ $task->title }}">{{ $task->title }}</a>
                                                @if($isInactive)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger/10 text-danger ring-1 ring-inset ring-danger/20 whitespace-nowrap">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-span-4 flex justify-end">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $currentStatus['bg'] }} {{ $currentStatus['text'] }} ring-1 ring-inset {{ $currentStatus['ring'] }}">
                                                {{ __('user_dashboard.status_' . $task->status) }}
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-10">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-muted-100 mb-3">
                                    <svg class="w-6 h-6 text-muted-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                </div>
                                <p class="text-muted-500 text-xs md:text-sm">{{ __('user_dashboard.no_projects_assigned') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- SECTION: Admin Permissions Container --}}
        <div class="">
            <!-- <h3 class="text-2xl font-semibold text-main mb-6">{{ __('user_dashboard.admin_section') ?? 'Admin Management' }}</h3> -->
            <div class="grid grid-cols-1 @2xl:grid-cols-2 @4xl:grid-cols-3 gap-6 w-full animate-fade-in-up [animation-delay:150ms]">
                {{-- Admin Campaigns --}}
                @if(auth()->user()->hasRole('admin') || auth()->user()->hasDepartmentRolePermission('admin.campaigns.view'))
                    <x-dashboard_widgets.campaigns :upcomingCampaigns="$upcomingCampaigns" :sentCampaigns="$sentCampaigns" class=""/>
                @endif

                {{-- Admin Email Templates --}}
                @if(auth()->user()->hasRole('admin') || auth()->user()->hasDepartmentRolePermission('admin.email_templates.view'))
                    <x-dashboard_widgets.email_templates :emailTemplates="$emailTemplates" class=""/>
                @endif
            </div>
        </div>
    </div>
    @else
        <div class="flex items-center justify-center min-h-[400px]">
            <div class="text-center">
                <div class="inline-block p-4 rounded-full bg-danger/10 text-danger mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h4 class="text-xl font-semibold text-main">{{ __('user_dashboard.no_permission') }}</h4>
                <p class="text-muted-500 mt-2">You do not have the required role to view this dashboard.</p>
            </div>
        </div>
    @endrole

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
let actionType = null;
let stream = null;
let detecting = false;

document.getElementById('checkInBtn').onclick = () => startFaceCheck('checkin');
document.getElementById('checkOutBtn').onclick = () => startFaceCheck('checkout');

$('#faceModal').on('hidden.bs.modal', () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    detecting = false;
});

async function startFaceCheck(type) {
    actionType = type;
    $('#faceModal').modal('show');

    const video = document.getElementById('video');
    document.getElementById('status').textContent = 'Initializing camera...';

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: "user" }
        });

        video.srcObject = stream;

        video.onloadedmetadata = async () => {
            document.getElementById('status').textContent = 'Loading face detection models...';
            await loadModels();
            document.getElementById('status').textContent = 'Detecting face...';
            detecting = true;
            detectFace();
        };
    } catch (error) {
        document.getElementById('status').textContent = 'Camera access denied or unavailable';
    }
}

async function loadModels() {
    await faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights/');
}

function detectFace() {
    if (!detecting) return;

    const video = document.getElementById('video');

    faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).then(detection => {
        if (detection) {
            const box = detection.box;
            const centerX = box.x + box.width / 2;
            const centerY = box.y + box.height / 2;
            const videoCenterX = 160;
            const videoCenterY = 160;
            const distance = Math.sqrt((centerX - videoCenterX) ** 2 + (centerY - videoCenterY) ** 2);

            if (distance < 100) {
                document.getElementById('status').textContent = 'Face aligned! Capturing...';
                detecting = false;
                setTimeout(captureFace, 500); // small delay
            } else {
                document.getElementById('status').textContent = 'Align your face inside the circle';
            }
        } else {
            document.getElementById('status').textContent = 'No face detected';
        }

        requestAnimationFrame(detectFace);
    }).catch(() => {
        requestAnimationFrame(detectFace);
    });
}

function captureFace() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');

    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(blob => {
        sendFace(blob);
    }, 'image/jpeg', 0.9);
}

function sendFace(blob) {
    const form = new FormData();
    form.append('face_image', blob);
    form.append('action', actionType);
    form.append('_token', '{{ csrf_token() }}');

    fetch('/face/verify', {
        method: 'POST',
        body: form
    })
    .then(r => r.json())
    .then(res => {
        $('#faceModal').modal('hide');
        alert(res.message);
        if (res.status) {
            location.reload();
        }
    })
    .catch(() => {
        $('#faceModal').modal('hide');
        alert('Verification failed');
    });
}
</script>
@endpush