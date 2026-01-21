@extends('layout_dashboard')
@section('title', 'Face Registration')

@section('content')
@vite(['resources/js/show-toast.js'])

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="max-w-3xl mx-auto py-10 px-4">
    <div class="bg-white rounded-2xl shadow-lg border border-muted-200 p-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-main">Face Registration</h1>
            <a href="{{ route('profile') }}" class="text-sm text-primary hover:underline">
                ← Back to profile
            </a>
        </div>

        {{-- Status Messages --}}
        <div id="cameraStatus" class="hidden mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-700"></p>
        </div>

        {{-- Instructions --}}
        <div class="mb-6 bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-700">
            <ul class="list-disc list-inside space-y-1">
                <li>Please sit in a well-lit environment</li>
                <li>Position your face close to the camera</li>
                <li>Remove masks, hats, and glasses</li>
                <li>Keep your face centered inside the frame</li>
                <li><strong>When asked, click "Allow" to access camera</strong></li>
                <li><strong>After opening camera, click "Capture Face" button</strong></li>
            </ul>
        </div>

        {{-- Camera --}}
        <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden mb-4">
            <video
                id="camera"
                autoplay
                playsinline
                muted
                class="w-full h-full object-cover">
            </video>

            <canvas id="canvas" class="hidden"></canvas>

            {{-- Face guide --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div id="faceGuide" class="w-56 h-56 border-4 border-primary rounded-full opacity-60"></div>
            </div>

            {{-- Camera loading indicator --}}
            <div id="cameraLoading" class="absolute inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden">
                <div class="text-center">
                    <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                    <p class="text-white font-medium">Initializing camera...</p>
                </div>
            </div>
        </div>

        {{-- Preview --}}
        <div id="previewBox" class="hidden mb-4">
            <p class="text-sm font-medium mb-2 text-center">Captured Image</p>
            <img id="previewImage" class="mx-auto w-48 h-48 rounded-lg border-2 border-gray-300 object-cover">
            
            <div class="mt-4 flex justify-center space-x-4">
                <button id="retakeBtn" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium">
                    Retake
                </button>
                <button id="confirmSubmit" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg font-medium">
                    Submit
                </button>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="flex justify-center gap-4 mt-6">
            <button id="startCamera"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                Open Camera
            </button>

            <button id="captureFace"
                class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium disabled:opacity-50"
                disabled>
                Capture Face
            </button>

            <button id="stopCamera"
                class="px-5 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium disabled:opacity-50"
                disabled>
                Stop Camera
            </button>
        </div>

        {{-- Processing Indicator --}}
        <div id="processing" class="hidden mt-6">
            <div class="text-center">
                <div class="inline-block">
                    <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                    <p class="text-lg font-medium text-main mb-2">Processing Face Registration</p>
                    <p class="text-sm text-gray-600 mb-4">This may take 10-15 seconds. Please wait...</p>
                    
                    {{-- Progress steps --}}
                    <div class="max-w-md mx-auto space-y-3 text-left">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full bg-green-500 mr-3"></div>
                            <span class="text-sm">Image uploaded</span>
                        </div>
                        <div class="flex items-center">
                            <div id="step2" class="w-4 h-4 rounded-full bg-gray-300 mr-3"></div>
                            <span class="text-sm text-gray-500">Face detection</span>
                        </div>
                        <div class="flex items-center">
                            <div id="step3" class="w-4 h-4 rounded-full bg-gray-300 mr-3"></div>
                            <span class="text-sm text-gray-500">Face extraction</span>
                        </div>
                        <div class="flex items-center">
                            <div id="step4" class="w-4 h-4 rounded-full bg-gray-300 mr-3"></div>
                            <span class="text-sm text-gray-500">Saving to database</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Success Message --}}
        <div id="successMessage" class="hidden mt-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-green-700 mb-2">Face Registered Successfully!</h3>
                <p class="text-gray-600 mb-6">You can now use face recognition for check-in.</p>
                <button onclick="window.location.href='{{ route('profile') }}'" 
                        class="px-6 py-2 bg-primary hover:bg-primary-hover text-white rounded-xl font-semibold">
                    Back to Profile
                </button>
            </div>
        </div>

    </div>
</div>

{{-- SIMPLE CAMERA SCRIPT --}}
{{-- SIMPLE CAMERA SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const video = document.getElementById('camera');
    const canvas = document.getElementById('canvas');
    const previewImage = document.getElementById('previewImage');
    const previewBox = document.getElementById('previewBox');
    const cameraLoading = document.getElementById('cameraLoading');
    const cameraStatus = document.getElementById('cameraStatus');
    const processing = document.getElementById('processing');
    const successMessage = document.getElementById('successMessage');
    
    // Buttons
    const startBtn = document.getElementById('startCamera');
    const captureBtn = document.getElementById('captureFace');
    const stopBtn = document.getElementById('stopCamera');
    const retakeBtn = document.getElementById('retakeBtn');
    const confirmSubmit = document.getElementById('confirmSubmit');
    
    // Variables
    let stream = null;
    let capturedBlob = null;
    
    // CSRF Token
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    // Show status message
    function showStatus(message, type = 'info') {
        if (!cameraStatus) return;
        
        cameraStatus.classList.remove('hidden');
        cameraStatus.innerHTML = `<p class="text-sm ${type === 'error' ? 'text-red-700' : type === 'success' ? 'text-green-700' : 'text-blue-700'}">${message}</p>`;
        
        if (type === 'error') {
            cameraStatus.className = 'mb-4 p-3 bg-red-50 border border-red-200 rounded-lg';
        } else if (type === 'success') {
            cameraStatus.className = 'mb-4 p-3 bg-green-50 border border-green-200 rounded-lg';
        } else {
            cameraStatus.className = 'mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg';
        }
    }

    // Show toast
    function showToast(message, type = 'info') {
        // If showToast is imported via Vite, use it
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            // Fallback alert
            alert(`${type.toUpperCase()}: ${message}`);
        }
    }

    // START CAMERA
    startBtn.addEventListener('click', async function() {
        try {
            cameraLoading.classList.remove('hidden');
            showStatus('Requesting camera access...', 'info');
            
            // Try to get user media
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });
            
            // Success!
            video.srcObject = stream;
            cameraLoading.classList.add('hidden');
            startBtn.disabled = true;
            captureBtn.disabled = false;
            stopBtn.disabled = false;
            
            showStatus('Camera connected! Click "Capture Face" when ready.', 'success');
            
        } catch (error) {
            cameraLoading.classList.add('hidden');
            console.error('Camera error:', error);
            
            let errorMessage = 'Camera access failed: ';
            switch(error.name) {
                case 'NotAllowedError':
                    errorMessage += 'You denied camera permission. Please allow camera access and try again.';
                    break;
                case 'NotFoundError':
                    errorMessage += 'No camera found. Please connect a camera.';
                    break;
                case 'NotReadableError':
                    errorMessage += 'Camera is in use by another application.';
                    break;
                default:
                    errorMessage += error.message;
            }
            
            showStatus(errorMessage, 'error');
            showToast(errorMessage, 'error');
        }
    });

    // CAPTURE FACE
    captureBtn.addEventListener('click', function() {
        if (!stream) {
            showStatus('Please start camera first', 'error');
            return;
        }
        
        try {
            // Set canvas size to video size
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw current video frame to canvas
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert to blob
            canvas.toBlob(function(blob) {
                capturedBlob = blob;
                previewImage.src = URL.createObjectURL(blob);
                previewBox.classList.remove('hidden');
                
                // Scroll to preview
                previewBox.scrollIntoView({ behavior: 'smooth' });
                
                showStatus('Face captured! Review the image below.', 'success');
                
            }, 'image/jpeg', 0.95);
        } catch (error) {
            console.error('Capture error:', error);
            showStatus('Failed to capture image: ' + error.message, 'error');
        }
    });

    // STOP CAMERA
    stopBtn.addEventListener('click', function() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        
        video.srcObject = null;
        startBtn.disabled = false;
        captureBtn.disabled = true;
        stopBtn.disabled = true;
        previewBox.classList.add('hidden');
        
        showStatus('Camera stopped', 'info');
    });

    // RETAKE PHOTO
    retakeBtn.addEventListener('click', function() {
        previewBox.classList.add('hidden');
        capturedBlob = null;
        showStatus('Ready to capture new photo', 'info');
    });

    // SUBMIT FACE - UPDATED WITH BETTER ERROR HANDLING
    confirmSubmit.addEventListener('click', async function() {
        if (!capturedBlob) {
            showStatus('No face captured yet', 'error');
            return;
        }
        
        // Show processing indicator
        processing.classList.remove('hidden');
        previewBox.classList.add('hidden');
        
        try {
            // Create FormData
            const formData = new FormData();
            formData.append('face_image', capturedBlob, 'face.jpg');
            
            // Add CSRF token
            if (csrf) {
                formData.append('_token', csrf);
            } else {
                // Fallback: try to get from meta tag again
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) {
                    formData.append('_token', csrfMeta.getAttribute('content'));
                }
            }
            
            console.log('Sending request to server...');
            
            // Send request with proper headers
            const response = await fetch('{{ route("face.register.store") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            console.log('Response status:', response.status);
            
            // Get response as text first to see what's returned
            const responseText = await response.text();
            console.log('Response text (first 500 chars):', responseText.substring(0, 500));
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Failed to parse JSON:', jsonError);
                console.error('Full response:', responseText);
                
                // If it's HTML, extract error message
                if (responseText.includes('<!DOCTYPE')) {
                    // Try to extract error from HTML
                    const parser = new DOMParser();
                    const htmlDoc = parser.parseFromString(responseText, 'text/html');
                    const errorElement = htmlDoc.querySelector('.exception-message') || 
                                       htmlDoc.querySelector('.error-message') ||
                                       htmlDoc.querySelector('title');
                    
                    const errorMsg = errorElement ? errorElement.textContent : 'Server returned HTML error page';
                    throw new Error(`Server error: ${errorMsg.substring(0, 100)}...`);
                } else {
                    throw new Error('Server returned invalid response');
                }
            }
            
            console.log('Parsed response data:', data);
            
            if (response.ok && data.status) {
                // Success!
                showToast(data.message || 'Face registered successfully!', 'success');
                
                // Redirect after short delay
                setTimeout(() => {
                    window.location.href = '{{ route("profile") }}?face_registered=true';
                }, 1500);
                
            } else {
                // Server returned error
                processing.classList.add('hidden');
                previewBox.classList.remove('hidden');
                showToast(data.message || 'Registration failed', 'error');
            }
            
        } catch (error) {
            console.error('Submission error:', error);
            processing.classList.add('hidden');
            previewBox.classList.remove('hidden');
            
            // Show appropriate error message
            if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                showToast('Network error. Please check your internet connection.', 'error');
            } else if (error.message.includes('HTML error page')) {
                showToast('Server error occurred. Please try again.', 'error');
            } else {
                showToast('Error: ' + error.message, 'error');
            }
        }
    });

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
    });
});
</script>
@endsection