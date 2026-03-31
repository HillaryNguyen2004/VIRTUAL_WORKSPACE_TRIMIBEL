@extends('layout_dashboard')
@section('title', __('staff_dashboard.title'))

@section('content')
    @php
        $dashboardMode = $dashboardMode ?? 'staff';
        $isStaff = auth()->user()->hasRole('staff');
        $isSubstaffDashboard = ($dashboardMode === 'substaff');

        $canView = $isSubstaffDashboard
            ? auth()->user()->can('staff.dashboard.view')
            : $isStaff;

        $dashboardHomeRoute = $isSubstaffDashboard
            ? route('substaff.dashboard')
            : route('staff.dashboard');
    @endphp
    {{-- Main Container --}}
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- Header Section --}}
        <div class="flex flex-col sm:justify-between w-full">
            <div class="flex items-center gap-3">
                <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">
                    {{ __('staff_dashboard.dashboard') }}</h1>
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full bg-secondary/10 text-secondary text-xs font-semibold uppercase tracking-wide">
                    {{ $isSubstaffDashboard ? 'Substaff' : __('staff_dashboard.staff') }}
                </span>
            </div>
            <p class="text-muted-500 text-sm md:text-base mt-1">{{ __('user_dashboard.subheading') }}</p>
        </div>

        {{-- Main Container --}}
        <div class="grid grid-cols-1 @4xl:grid-cols-12 gap-6 w-full">

            {{-- Attendance Check --}}
            @include('components.dashboard_widgets.check_attendance')

            {{-- Pending Day Off Requests --}}
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasDepartmentRolePermission('staff.request_daysoff.view'))
                <div class="@4xl:col-span-7 flex flex-col h-full">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-md md:text-lg font-semibold text-main">
                            {{ __('staff_dashboard.pending_day_off_requests') }}</h3>
                        <a href="{{ route('dayoff.staff.pending') }}" title="{{ __('admin_dashboard.view_all') }}"
                            class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>

                    {{-- Request List --}}
                    <div class="grid grid-cols-2 gap-3">
                        @forelse($recentDayOffRequests as $req)
                                        @php
                                            $isFullDay = $req->leave_type === 'OFF_FULL';
                                            $circleColor = $isFullDay
                                                ? 'bg-secondary/10 text-secondary ring-secondary/20'
                                                : 'bg-accent/10 text-accent ring-accent/20';
                                            $circleLabel = $isFullDay ? __('staff_dashboard.full_day') : __('staff_dashboard.half_day');
                                        @endphp
                                        <div
                                            class="group flex items-center gap-4 p-4 rounded-2xl bg-white border border-muted-300 hover:border-primary/50 transition-all duration-300">
                                            {{-- Info --}}
                                            <div class="flex-1 min-w-0">
                                                <h4
                                                    class="text-sm md:text-base font-semibold text-main group-hover:text-primary transition-colors truncate">
                                                    {{ $req->user->name ?? '—' }}</h4>
                                                <p class="text-xs text-muted-500 mt-0.5">
                                                    {{ \Carbon\Carbon::parse($req->date)->format('M d, Y') }}
                                                    @if($req->half_day_period)
                                                        &nbsp;·&nbsp;{{ ucfirst(strtolower($req->half_day_period)) }}
                                                    @endif
                                                </p>
                                            </div>

                                            {{-- Pending badge --}}
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset {{ $circleColor }}">
                                                {{ $circleLabel }}
                                            </span>
                                        </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-10 text-muted-400 text-sm gap-2">
                                <svg class="w-8 h-8 text-muted-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                {{ __('staff_dashboard.no_pending_day_off_requests') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- TEAM MEMBERS SECTION --}}
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasDepartmentRolePermission('staff.team_members.view') || auth()->user()->hasRole('substaff'))
                <div class="@4xl:col-span-4 bg-white border border-muted-300 hover:border-primary/50 transition-all duration-300 rounded-2xl p-6 flex-1">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.team_members') }}</h4>
                        <a href="{{ route('team.overview') }}"
                            class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16m-7 6h7" />
                            </svg>
                        </a>
                        {{-- New View All Button --}}
                        <button id="open-team"
                            class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                            </svg>
                        </button>
                    </div>

                    @if($teamMembers->isNotEmpty())
                        <ul class="flex flex-col gap-4">
                            @foreach($teamMembers->take(3) as $member)
                                <li class="flex items-center gap-4 group">
                                    <div
                                        class="h-10 w-10 rounded-full bg-muted-100 text-muted-600 border border-muted-200 grid place-items-center font-semibold text-sm  transition-colors">
                                        {{ mb_substr($member->name ?? '', 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs md:text-sm font-medium truncate" title="{{ $member->name }}">
                                            {{ $member->name }}
                                        </p>
                                        <p class="text-xs text-muted-500 truncate">{{ $member->email }}</p>
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
            @endif

            {{-- Project Section --}}
            @if(auth()->user()->hasRole('staff'))
                <div class="@4xl:col-span-8 bg-white border border-muted-300 hover:border-primary/50 transition-all duration-300 rounded-2xl p-6 animate-fade-in-up [animation-delay:150ms]">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-lg font-bold text-main">{{ __('staff_dashboard.my_projects') }}</h4>
                        <a href="{{ route('projects.index') }}"
                            class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>

                    </div>

                    @php
                        $statusMap = [
                            'pending' => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                            'in_progress' => 'bg-secondary/10 text-secondary ring-secondary/20',
                            'active' => 'bg-accent/10 text-accent ring-accent/20',
                        ];
                    @endphp

                    <div class="w-full">
                        {{-- Header Row --}}
                        <div
                            class="grid grid-cols-12 gap-4 pb-3 border-b border-muted-200 text-xs font-semibold text-muted-400 uppercase tracking-wider">
                            <div class="col-span-6">{{ __('staff_dashboard.project_name') }}</div>
                            <div class="col-span-2 text-center hidden @2xl:block">{{ __('staff_dashboard.percentage') }}</div>
                            <div class="col-span-3 @2xl:col-span-2 text-right">{{ __('staff_dashboard.status') }}</div>
                            <div class="col-span-3 @2xl:col-span-2 text-right">{{ __('staff_dashboard.tasks_count') }}</div>
                        </div>

                        {{-- List Rows --}}
                        @forelse ($projects->take(3) as $project)
                            <div
                                class="grid grid-cols-12 gap-4 py-4 items-center border-b border-muted-100 last:border-0 hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                                <div class="col-span-6">
                                    <p class="text-sm font-medium text-main truncate" title="{{ $project->name }}">
                                        {{ $project->title }}</p>
                                </div>

                                <div class="col-span-2 text-center hidden @2xl:block">
                                    <span class="text-sm text-muted-500 font-medium">{{ $project->percentage ?? 0 }}%</span>
                                </div>

                                <div class="col-span-3 @2xl:col-span-2 flex justify-end">
                                    <span
                                        class="inline-flex items-center text-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset {{ $statusMap[$project->status] ?? $statusMap['pending'] }}">
                                        {{ __('staff_dashboard.' . $project->status) }}
                                    </span>
                                </div>

                                <div class="col-span-3 @2xl:col-span-2 flex justify-end">
                                    <span class="text-sm text-muted-500 font-medium">{{ $project->tasks->count() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-muted-400 text-sm">
                                {{ __('staff_dashboard.no_upcoming_projects') ?? 'No data available' }}
                            </div>
                        @endforelse
                    </div>
                </div>
            @elseif(auth()->user()->hasRole('substaff'))
                <div class="@4xl:col-span-8 bg-white border border-muted-300 hover:border-primary/30 transition-all duration-300 rounded-2xl p-6 h-max">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-md md:text-lg font-semibold text-main">{{ __('user_dashboard.assigned_projects') }}</h4>
                        <a href="{{ route('tasks.index') }}"
                            class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-muted-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8m9 0v9" />
                            </svg>
                        </a>
                    </div>

                    <div class="w-full">
                        <div
                            class="grid grid-cols-12 gap-4 pb-3 border-b border-muted-200 text-xs font-semibold text-muted-400 uppercase tracking-wider">
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
                                     <li
                                                    class="grid grid-cols-12 gap-4 py-4 items-center hover:bg-canvas transition-colors px-2 rounded-lg -mx-2">
                                                    <div class="col-span-8 flex items-center gap-3">
                                                        {{-- Small indicator dot --}}
                                                        <div
                                                            class="w-2 h-2 rounded-full {{ str_replace('bg-', 'bg-', $currentStatus['text']) }} opacity-50">
                                                        </div>
                                                        <div class="flex items-center gap-2 overflow-hidden">
                                                            @if($task->isUnread())
                                                                <span
                                                                    class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50 flex-shrink-0 animate-pulse"
                                                                    title="New/Updated"></span>
                                                            @endif
                                                            <a href="{{ route('tasks.details', $task->id) }}"
                                                                class="text-sm font-medium text-main truncate hover:text-primary hover:underline"
                                                                title="{{ $task->title }}">{{ $task->title }}</a>
                                                            @if($isInactive)
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger/10 text-danger ring-1 ring-inset ring-danger/20 whitespace-nowrap">
                                                                    Inactive
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-span-4 flex justify-end">
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $currentStatus['bg'] }} {{ $currentStatus['text'] }} ring-1 ring-inset {{ $currentStatus['ring'] }}">
                                                            {{ __('user_dashboard.status_' . $task->status) }}
                                                        </span>
                                                    </div>
                                                </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-10">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-muted-100 mb-3">
                                    <svg class="w-6 h-6 text-muted-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
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
            <div
                class="grid grid-cols-1 @2xl:grid-cols-2 @4xl:grid-cols-3 gap-6 w-full animate-fade-in-up [animation-delay:150ms]">
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