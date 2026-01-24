@extends('layout_dashboard')
@section('title', 'Face Recognition Check-in')

@section('content')
<div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
    
    <div class="flex flex-col gap-4 @2xl:flex-row @2xl:justify-between @2xl:items-center w-full">
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
        
        <a href="{{ route('user.dashboard') }}" class="group flex items-center justify-center gap-2 rounded-xl bg-muted-100 px-4 py-2 text-muted-600 font-medium hover:bg-muted-200 transition-colors">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Camera Section -->
        <div class="lg:col-span-2 bg-white border border-muted-200 shadow-lg shadow-main/5 rounded-2xl p-6">
            <div class="text-center">
                <!-- Camera feed container -->
                <div class="relative mx-auto" style="width: 640px; height: 480px;">
                    <!-- Video element - FIXED: Added id and proper attributes -->

                    <video id="video" 
                           width="640" 
                           height="480" 
                           autoplay 
                           muted
                           playsinline
                           class="rounded-xl border-2 border-muted-300 bg-gray-900">
                    </video>
                     <button id="startCameraBtn"
                            class="mb-4 px-6 py-3 bg-primary text-white rounded-xl font-medium">
                        Start Camera
                    </button>
                    
                    <!-- Face guide overlay -->
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 
                                w-64 h-64 border-4 border-dashed border-accent rounded-full 
                                opacity-70 pointer-events-none"></div>
                    
                    <!-- Canvas for face detection -->
                    <canvas id="canvas" 
                            width="640" 
                            height="480" 
                            class="absolute top-0 left-0 pointer-events-none"></canvas>
                </div>
                
                <!-- Status messages -->
                <div id="statusContainer" class="mt-6">
                    <div id="status" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-muted-100">
                        <div id="statusSpinner" class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span id="statusText" class="text-muted-600">Initializing camera...</span>
                    </div>
                    
                    <div id="detectionStatus" class="mt-2 text-sm text-muted-500">
                        Please position your face inside the circle
                    </div>
                </div>
                
                <!-- Manual Check Button (hidden by default) -->
                <div id="manualCheckContainer" class="mt-6 hidden">
                    <button id="manualCheckBtn" 
                            class="inline-flex items-center gap-2 px-6 py-3 bg-warning hover:bg-warning/80 text-white rounded-xl font-medium transition-colors">
                        <i class="fas fa-user-check"></i>
                        Manual Check {{ ucfirst($checkType) }}
                    </button>
                    <p class="mt-2 text-sm text-muted-500">
                        Face verification failed. You can try manual check.
                    </p>
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
    
    <!-- Success/Error Toast -->
    <div id="toast" class="fixed bottom-4 right-4 hidden z-50">
        <div class="bg-white border rounded-xl shadow-lg p-4 max-w-sm">
            <div class="flex items-start gap-3">
                <div id="toastIcon" class="flex-shrink-0"></div>
                <div class="flex-1">
                    <h4 id="toastTitle" class="font-bold"></h4>
                    <p id="toastMessage" class="text-sm mt-1"></p>
                </div>
                <button onclick="hideToast()" class="text-muted-400 hover:text-muted-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form data -->
<input type="hidden" id="checkType" value="{{ $checkType }}">
<input type="hidden" id="username" value="{{ auth()->user()->username }}">
<input type="hidden" id="csrfToken" value="{{ csrf_token() }}">
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
    to { transform: rotate(360deg); }
}

#video {
    transform: scaleX(-1); /* Mirror the video for better UX */
}
</style>
@endpush

@push('scripts')
<!-- Load face-api.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
// Global variables
let video = document.getElementById('video');
let canvas = document.getElementById('canvas');
let ctx = canvas.getContext('2d');
let modelsLoaded = false;
let isProcessing = false;
let detectionInterval = null;
let stream = null;

// Configuration
const checkType = document.getElementById('checkType').value;
const username = document.getElementById('username').value;
const csrfToken = document.getElementById('csrfToken').value;

// Update status display
function updateStatus(message, type = 'info') {
    const statusEl = document.getElementById('status');
    const statusText = document.getElementById('statusText');
    const statusSpinner = document.getElementById('statusSpinner');
    const detectionStatusEl = document.getElementById('detectionStatus');
    
    let icon = '';
    let bgColor = '';
    
    switch(type) {
        case 'success':
            icon = '<i class="fas fa-check-circle text-green-500"></i>';
            bgColor = 'bg-green-50 border border-green-200';
            statusSpinner.style.display = 'none';
            break;
        case 'error':
            icon = '<i class="fas fa-times-circle text-red-500"></i>';
            bgColor = 'bg-red-50 border border-red-200';
            statusSpinner.style.display = 'none';
            break;
        case 'warning':
            icon = '<i class="fas fa-exclamation-triangle text-yellow-500"></i>';
            bgColor = 'bg-yellow-50 border border-yellow-200';
            statusSpinner.style.display = 'none';
            break;
        default:
            icon = '';
            bgColor = 'bg-muted-100';
            statusSpinner.style.display = 'inline-block';
    }
    
    statusText.textContent = message;
    statusEl.className = `inline-flex items-center gap-2 px-4 py-2 rounded-lg ${bgColor}`;
    
    if (type === 'info') {
        detectionStatusEl.textContent = message;
    }
}

// Load face-api models - FIXED: Use CDN directly
async function loadModels() {
    try {
        updateStatus('Loading face recognition models...', 'info');
        
        // Load from CDN
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights/';
        
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        
        modelsLoaded = true;
        updateStatus('Models loaded. Starting camera...', 'success');
        return true;
        
    } catch (error) {
        console.error('Error loading models:', error);
        updateStatus('Failed to load face recognition models', 'error');
        return false;
    }
}

// Start webcam - FIXED: Better error handling
async function startWebcam() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    updateStatus('Camera not supported or blocked by browser', 'error');
    return false;
}

    try {
        updateStatus('Requesting camera access...', 'info');
        
        // Try different constraints for better compatibility
        const constraints = {
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user',
                frameRate: { ideal: 24 }
            },
            audio: false
        };
        
        // Request camera access
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        
        // Set video source
        video.srcObject = stream;
        await video.play();
        
        // Wait for video to be ready
        await new Promise((resolve) => {
            video.onloadedmetadata = () => {
                updateStatus('Camera ready. Please position your face.', 'success');
                resolve();
            };
            
            video.onerror = () => {
                updateStatus('Failed to load video stream', 'error');
                resolve(false);
            };
            
            // Fallback timeout
            setTimeout(() => {
                if (video.readyState < 2) { // 0=HAVE_NOTHING, 1=HAVE_METADATA, 2=HAVE_CURRENT_DATA, 3=HAVE_FUTURE_DATA, 4=HAVE_ENOUGH_DATA
                    updateStatus('Camera is taking too long to start', 'warning');
                    resolve(false);
                }
            }, 5000);
        });
        
        return true;
        
    } catch (error) {
        console.error('Error accessing webcam:', error);
        
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            updateStatus('Camera access denied. Please enable camera permissions in your browser settings.', 'error');
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            updateStatus('No camera found. Please connect a camera.', 'error');
        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
            updateStatus('Camera is already in use by another application.', 'error');
        } else if (error.name === 'OverconstrainedError') {
            updateStatus('Camera doesn\'t support required constraints. Trying fallback...', 'warning');
            // Try with simpler constraints
            return startWebcamFallback();
        } else {
            updateStatus('Cannot access camera: ' + error.message, 'error');
        }
        
        return false;
    }
}

// Fallback webcam with simpler constraints
async function startWebcamFallback() {
    try {
        const fallbackConstraints = {
            video: true, // Let browser decide
            audio: false
        };
        
        stream = await navigator.mediaDevices.getUserMedia(fallbackConstraints);
        video.srcObject = stream;
        
        await new Promise((resolve) => {
            video.onloadedmetadata = () => {
                updateStatus('Camera started with fallback settings.', 'warning');
                resolve();
            };
            setTimeout(resolve, 2000);
        });
        
        return true;
    } catch (error) {
        updateStatus('Failed to start camera even with fallback settings.', 'error');
        return false;
    }
}

// Draw face detection box
function drawFaceBox(detection) {
    const displaySize = { width: video.videoWidth, height: video.videoHeight };
    
    // Match canvas size to video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Clear previous drawings
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Resize detection to canvas size
    const detectionsForSize = faceapi.resizeResults(detection, displaySize);
    
    // Draw face box
    const box = detectionsForSize.detection.box;
    ctx.strokeStyle = '#00ff00';
    ctx.lineWidth = 3;
    ctx.strokeRect(box.x, box.y, box.width, box.height);
    
    // Draw landmarks (optional)
    faceapi.draw.drawFaceLandmarks(canvas, detectionsForSize);
}

// Start face detection
function startFaceDetection() {
    if (!modelsLoaded || isProcessing) return;
    
    // Clear any existing interval
    if (detectionInterval) {
        clearInterval(detectionInterval);
    }
    
    detectionInterval = setInterval(async () => {
        try {
            // Detect faces
            const detections = await faceapi.detectAllFaces(
                video, 
                new faceapi.TinyFaceDetectorOptions({ 
                    inputSize: 320, 
                    scoreThreshold: 0.5 
                })
            ).withFaceLandmarks();
            
            if (detections.length > 0) {
                const detection = detections[0];
                drawFaceBox(detection);
                
                // Check if face is centered
                const box = detection.detection.box;
                const faceCenterX = box.x + box.width / 2;
                const faceCenterY = box.y + box.height / 2;
                const videoCenterX = video.videoWidth / 2;
                const videoCenterY = video.videoHeight / 2;
                const guideRadius = 100;
                
                const distance = Math.sqrt(
                    Math.pow(faceCenterX - videoCenterX, 2) + 
                    Math.pow(faceCenterY - videoCenterY, 2)
                );
                
                // Check face size (should be reasonably large)
                const faceSizeOk = box.width > 100 && box.height > 100;
                
                if (distance < guideRadius && faceSizeOk && !isProcessing) {
                    updateStatus('Face detected! Verifying...', 'warning');
                    
                    // Stop detection and process
                    clearInterval(detectionInterval);
                    setTimeout(() => {
                        verifyAndSubmit();
                    }, 1500);
                } else {
                    if (distance >= guideRadius) {
                        document.getElementById('detectionStatus').textContent = 'Please center your face in the circle';
                    } else if (!faceSizeOk) {
                        document.getElementById('detectionStatus').textContent = 'Please move closer to the camera';
                    }
                }
            } else {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                document.getElementById('detectionStatus').textContent = 'No face detected. Please look at the camera.';
            }
        } catch (error) {
            console.error('Detection error:', error);
        }
    }, 100); // Check every 100ms
}

// Verify face and submit
async function verifyAndSubmit() {
    if (isProcessing) return;
    
    isProcessing = true;
    updateStatus('Verifying identity...', 'warning');
    
    try {
        // Capture current frame as base64
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = video.videoWidth;
        tempCanvas.height = video.videoHeight;
        const tempCtx = tempCanvas.getContext('2d');
        
        // Flip image horizontally for natural view
        tempCtx.translate(video.videoWidth, 0);
        tempCtx.scale(-1, 1);
        tempCtx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
        
        const imageData = tempCanvas.toDataURL('image/jpeg', 0.8);
        
        // Send to server for verification
        const response = await fetch('{{ route("checkin.face.process") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                username: username,
                check_type: checkType,
                image_data: imageData
            })
        });
        
        const result = await response.json();
        
        if (result.status) {
            // Success
            showToast('success', 'Success', result.message);
            updateStatus('Verification successful! Redirecting...', 'success');
            
            // Redirect to dashboard after delay
            setTimeout(() => {
                window.location.href = '{{ route("user.dashboard") }}';
            }, 2000);
        } else {
            // Error
            showToast('error', 'Error', result.message);
            updateStatus('Verification failed', 'error');
            
            // Show manual check option
            document.getElementById('manualCheckContainer').classList.remove('hidden');
            isProcessing = false;
            
            // Restart detection
            startFaceDetection();
        }
    } catch (error) {
        console.error('Verification error:', error);
        showToast('error', 'Network Error', 'Please check your connection');
        updateStatus('Network error', 'error');
        document.getElementById('manualCheckContainer').classList.remove('hidden');
        isProcessing = false;
        
        // Restart detection
        startFaceDetection();
    }
}

// Manual check button
document.getElementById('manualCheckBtn')?.addEventListener('click', function() {
    if (confirm('Skip face verification and proceed with manual check?')) {
        fetch('{{ route("checkin.manual.process") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                check_type: checkType,
                skip_face_verification: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                showToast('success', 'Success', data.message);
                setTimeout(() => {
                    window.location.href = '{{ route("user.dashboard") }}';
                }, 1500);
            } else {
                showToast('error', 'Error', data.message);
            }
        })
        .catch(error => {
            showToast('error', 'Network Error', 'Please try again');
        });
    }
});

// Toast notification functions
function showToast(type, title, message) {
    const toast = document.getElementById('toast');
    const toastIcon = document.getElementById('toastIcon');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');
    
    // Set content based on type
    if (type === 'success') {
        toastIcon.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xl"></i>';
        toast.className = 'fixed bottom-4 right-4 bg-white border border-green-200 rounded-xl shadow-lg p-4 max-w-sm z-50';
    } else {
        toastIcon.innerHTML = '<i class="fas fa-times-circle text-red-500 text-xl"></i>';
        toast.className = 'fixed bottom-4 right-4 bg-white border border-red-200 rounded-xl shadow-lg p-4 max-w-sm z-50';
    }
    
    toastTitle.textContent = title;
    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 5000);
}

function hideToast() {
    document.getElementById('toast').classList.add('hidden');
}

// Clean up function
function cleanup() {
    if (detectionInterval) {
        clearInterval(detectionInterval);
        detectionInterval = null;
    }
    
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    
    if (video.srcObject) {
        video.srcObject = null;
    }
}

// Initialize everything
async function initializeFaceCheckin() {
    try {
        // Load models first
        const loaded = await loadModels();
        
        if (!loaded) {
            updateStatus('Failed to load face recognition', 'error');
            return;
        }
        
        // Start webcam
        const started = await startWebcam();
        
        if (!started) {
            updateStatus('Failed to start camera', 'error');
            document.getElementById('manualCheckContainer').classList.remove('hidden');
            return;
        }
        
        // Wait a moment for camera to stabilize
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Start face detection
        startFaceDetection();
        
    } catch (error) {
        console.error('Initialization error:', error);
        updateStatus('Initialization failed: ' + error.message, 'error');
        document.getElementById('manualCheckContainer').classList.remove('hidden');
    }
}

// Start when click button
document.getElementById('startCameraBtn').addEventListener('click', async () => {
    document.getElementById('startCameraBtn').remove();
    initializeFaceCheckin();
});


// Clean up on page unload
window.addEventListener('beforeunload', cleanup);
window.addEventListener('pagehide', cleanup);
</script>
@endpush