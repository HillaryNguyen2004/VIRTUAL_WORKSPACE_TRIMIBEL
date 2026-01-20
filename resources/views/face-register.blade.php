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

        {{-- Instructions --}}
        <div class="mb-6 bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-700">
            <ul class="list-disc list-inside space-y-1">
                <li>Please sit in a well-lit environment</li>
                <li>Position your face close to the camera</li>
                <li>Remove masks, hats, and glasses</li>
                <li>Keep your face centered inside the frame</li>
                <li><strong>When asked, click “Allow”</strong></li>
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
                <div class="w-56 h-56 border-4 border-primary rounded-full opacity-60"></div>
            </div>
        </div>

        {{-- Preview --}}
        <div id="previewBox" class="hidden mb-4 text-center">
            <p class="text-sm font-medium mb-2">Captured Image</p>
            <img id="previewImage" class="mx-auto w-40 h-40 rounded-lg border object-cover">
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
                Stop
            </button>
        </div>

        {{-- Submit --}}
        <div class="text-center mt-6">
            <button id="submitFace"
                class="hidden px-6 py-2 bg-primary hover:bg-primary-hover text-white rounded-xl font-semibold">
                Save Face & Finish
            </button>
        </div>

    </div>
</div>

{{-- ✅ CAMERA SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    const video = document.getElementById('camera');
    const canvas = document.getElementById('canvas');
    const previewImage = document.getElementById('previewImage');
    const previewBox = document.getElementById('previewBox');

    const startBtn = document.getElementById('startCamera');
    const captureBtn = document.getElementById('captureFace');
    const stopBtn = document.getElementById('stopCamera');
    const submitBtn = document.getElementById('submitFace');

    let stream = null;
    let capturedBlob = null;

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    // START CAMERA
    startBtn.addEventListener('click', async () => {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                showToast('Camera not supported in this browser', 'error');
                return;
            }

            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user' },
                audio: false
            });

            video.srcObject = stream;
            await video.play();

            startBtn.disabled = true;
            captureBtn.disabled = false;
            stopBtn.disabled = false;

            showToast('Camera opened', 'success');
        } catch (err) {
            console.error(err);
            showToast('Camera access denied or unavailable', 'error');
        }
    });

    // CAPTURE
    captureBtn.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        canvas.toBlob(blob => {
            capturedBlob = blob;
            previewImage.src = URL.createObjectURL(blob);
            previewBox.classList.remove('hidden');
            submitBtn.classList.remove('hidden');
            showToast('Face captured', 'success');
        }, 'image/jpeg', 0.95);
    });

    // STOP
    stopBtn.addEventListener('click', () => {
        if (stream) stream.getTracks().forEach(t => t.stop());
        stream = null;
        video.srcObject = null;

        startBtn.disabled = false;
        captureBtn.disabled = true;
        stopBtn.disabled = true;
    });

    // SUBMIT
    submitBtn.addEventListener('click', async () => {
        if (!capturedBlob) return;

        const formData = new FormData();
        formData.append('face_image', capturedBlob);
        formData.append('_token', csrf);

        const res = await fetch('{{ route("face.register.store") }}', {
            method: 'POST',
            body: formData
        });

        if (res.ok) {
            showToast('Face registered successfully', 'success');
            setTimeout(() => window.location.href = '{{ route("profile") }}', 1200);
        } else {
            showToast('Registration failed', 'error');
        }
    });
});
</script>

@endsection
