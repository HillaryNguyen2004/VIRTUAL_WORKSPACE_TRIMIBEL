@extends('layout_dashboard')
@section('title', __('user_dashboard.title'))

@section('content')
@role('user')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('user_dashboard.heading') }}</h1>
        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> {{ __('user_dashboard.generate_report') }}
        </a>
    </div>

    <!-- Check In/Out Buttons -->
    <div class="mb-4">
        <div class="alert alert-info">
            <strong>Face Recognition Check-in System</strong><br>
            Click the buttons below to check in or check out using face recognition.
        </div>
        
        <a href="{{ route('checkin.face.page', 'checkin') }}"
        class="btn btn-success btn-lg me-2">
            <i class="fas fa-camera"></i> Face Check In
        </a>

        <a href="{{ route('checkin.face.page', 'checkout') }}"
        class="btn btn-danger btn-lg">
            <i class="fas fa-camera"></i> Face Check Out
        </a>

        @if($workingHour)
            <div class="alert alert-info mt-3">
                <strong>Company Working Hours:</strong><br>
                Start: <span class="text-primary">{{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->start_at)->format('H:i') }}</span><br>
                End: <span class="text-danger">{{ \Carbon\Carbon::createFromFormat('H:i:s', $workingHour->end_at)->format('H:i') }}</span>
            </div>
        @endif
    </div>


    <div class="row mb-4">
        <div class="col-md-4">
            <a href="{{ route('dayoff.request') }}" class="text-decoration-none">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-calendar-times fa-2x text-warning"></i>
                        </div>
                        <div class="ml-3">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                {{ __('user_dashboard.request_day_off') }}
                            </div>
                            <div class="mb-0 font-weight-bold text-gray-800">{{ __('user_dashboard.click_to_request') }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('chat.index') }}" class="text-decoration-none">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-comments fa-2x text-info"></i>
                        </div>
                        <div class="ml-3">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                {{ __('user_dashboard.team_chat') }}
                            </div>
                            <div class="mb-0 font-weight-bold text-gray-800">{{ __('user_dashboard.join_team_conversation') }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Content Row -->
    <!-- [everything below remains unchanged] -->
    
    <!-- Earnings (Monthly) Card Example -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                {{ __('user_dashboard.earnings_monthly') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$40,000</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings (Annual) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                {{ __('user_dashboard.earnings_annual') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$215,000</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">{{ __('user_dashboard.tasks') }}</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">50%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 50%"
                                            aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Requests Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                {{ __('user_dashboard.pending_requests') }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">18</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Info Section -->
    <div class="row">
        <!-- Team Leader -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    {{ __('user_dashboard.team_leader') }}
                </div>
                <div class="card-body">
                    @if($teamLeader)
                        <p><strong>{{ __('user_dashboard.name_label') }}:</strong> {{ $teamLeader->name }}</p>
                        <p><strong>{{ __('user_dashboard.email_label') }}:</strong> {{ $teamLeader->email }}</p>
                    @else
                        <p class="text-muted">{{ __('user_dashboard.no_team_leader') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Team Members -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    {{ __('user_dashboard.team_members') }}
                </div>
                <div class="card-body">
                    @if($teamMembers->count())
                        <ul class="list-group">
                            @foreach($teamMembers as $member)
                                <li class="list-group-item">
                                    {{ $member->name }} ({{ $member->email }})
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">{{ __('user_dashboard.no_team_members') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    {{-- <div class="row">
        <!-- Content Column -->
        <div class="col-lg-6 mb-4">
            <!-- Project Card Example -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">{{ __('user_dashboard.assigned_projects') }}</h6>
                </div>
                <div class="card-body">
                    @if($assignedTasks->count())
                        @foreach($assignedTasks as $task)
                            <h5 class="small font-weight-bold">{{ $task->title }} 
                                <span class="float-right">{{ __( 'user_dashboard.status_' . $task->status) }}</span>
                            </h5>
                            <div class="progress mb-4">
                                <div 
                                    class="progress-bar 
                                        @if($task->status === 'pending') bg-warning 
                                        @elseif($task->status === 'in_progress') bg-info 
                                        @elseif($task->status === 'completed') bg-success 
                                        @else bg-secondary 
                                        @endif"
                                    role="progressbar" 
                                    style="width:
                                        @if($task->status === 'pending') 20%
                                        @elseif($task->status === 'in_progress') 60%
                                        @elseif($task->status === 'completed') 100%
                                        @else 0%
                                        @endif"
                                    aria-valuenow="
                                        @if($task->status === 'pending') 20
                                        @elseif($task->status === 'in_progress') 60
                                        @elseif($task->status === 'completed') 100
                                        @else 0
                                        @endif"
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">{{ __('user_dashboard.no_projects_assigned') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div> --}}
</div>
@else
<div class="container py-5">
    <div class="alert alert-danger text-center">
        <h4>{{ __('user_dashboard.no_permission') }}</h4>
    </div>
</div>
@endrole
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
@endsection
