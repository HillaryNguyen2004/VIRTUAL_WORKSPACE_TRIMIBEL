/**
 * Face Check-in Script
 * Handles face detection and verification for attendance check-in/checkout
 */

import { showToast } from "./show-toast";

// Global variables
let video, canvas, ctx;
let modelsLoaded = false;
let isProcessing = false;
let detectionInterval = null;
let stream = null;
let checkType, username, csrfToken;

/**
 * Initialize when DOM is loaded
 */
document.addEventListener("DOMContentLoaded", function () {
    // Get DOM elements
    video = document.getElementById("video");
    canvas = document.getElementById("canvas");

    if (!video || !canvas) {
        console.error("Required video or canvas elements not found");
        return;
    }

    ctx = canvas.getContext("2d");

    // Get configuration from hidden inputs
    checkType = document.getElementById("checkType")?.value;
    username = document.getElementById("username")?.value;
    csrfToken = document.getElementById("csrfToken")?.value;

    // Initialize face check-in
    initializeFaceCheckin();

    // Setup cleanup listeners
    window.addEventListener("beforeunload", cleanup);
    window.addEventListener("pagehide", cleanup);
});

/**
 * Stop active video stream
 */
function stopActiveStream() {
    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }
    if (video && video.srcObject) {
        video.srcObject = null;
    }
}

/**
 * Update status display
 */
function updateStatus(message, type = "info") {
    const statusEl = document.getElementById("status");
    const statusText = document.getElementById("statusText");
    const statusSpinner = document.getElementById("statusSpinner");
    const detectionStatusEl = document.getElementById("detectionStatus");

    if (!statusEl || !statusText) return;

    let bgColor = "";

    switch (type) {
        case "success":
            bgColor = "bg-green-50 border border-green-200";
            if (statusSpinner) statusSpinner.style.display = "none";
            break;
        case "error":
            bgColor = "bg-red-50 border border-red-200";
            if (statusSpinner) statusSpinner.style.display = "none";
            break;
        case "warning":
            bgColor = "bg-yellow-50 border border-yellow-200";
            if (statusSpinner) statusSpinner.style.display = "none";
            break;
        default:
            bgColor = "bg-muted-100";
            if (statusSpinner) statusSpinner.style.display = "inline-block";
    }

    statusText.textContent = message;
    statusEl.className = `inline-flex items-center gap-2 px-4 py-2 rounded-lg ${bgColor}`;

    if (type === "info" && detectionStatusEl) {
        detectionStatusEl.textContent = message;
    }
}

/**
 * Load face-api models from CDN
 */
async function loadModels() {
    try {
        updateStatus("Loading face recognition models...", "info");

        const MODEL_URL = "/models";

        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);

        modelsLoaded = true;
        updateStatus("Models loaded. Starting camera...", "success");
        return true;
    } catch (error) {
        console.error("Error loading models:", error);
        updateStatus("Failed to load face recognition models", "error");
        return false;
    }
}

/**
 * Start webcam with error handling
 */
async function startWebcam() {
    if (!video) {
        updateStatus("Camera element not found on page", "error");
        return false;
    }

    if (
        location.protocol !== "https:" &&
        location.hostname !== "localhost" &&
        location.hostname !== "127.0.0.1"
    ) {
        updateStatus(
            "Camera requires HTTPS. Please use a secure connection.",
            "error",
        );
        return false;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        updateStatus("Camera not supported or blocked by browser", "error");
        return false;
    }

    try {
        updateStatus("Requesting camera access...", "info");

        // Ensure any previous stream is stopped
        stopActiveStream();

        const constraints = {
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: "user",
                frameRate: { ideal: 24 },
            },
            audio: false,
        };

        // Request camera access
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = stream;
        await video.play();

        // Wait for video to be ready
        await new Promise((resolve) => {
            if (video.readyState >= 1) {
                updateStatus(
                    "Camera ready. Please position your face.",
                    "success",
                );
                resolve();
                return;
            }

            video.onloadedmetadata = () => {
                updateStatus(
                    "Camera ready. Please position your face.",
                    "success",
                );
                resolve();
            };

            video.onerror = () => {
                updateStatus("Failed to load video stream", "error");
                resolve(false);
            };

            // Fallback timeout
            setTimeout(() => {
                if (video.readyState < 1) {
                    updateStatus(
                        "Camera is taking too long to start",
                        "warning",
                    );
                    resolve(false);
                } else {
                    resolve();
                }
            }, 5000);
        });

        return true;
    } catch (error) {
        console.error("Error accessing webcam:", error);
        return handleCameraError(error);
    }
}

/**
 * Handle camera errors
 */
async function handleCameraError(error) {
    if (
        error.name === "NotAllowedError" ||
        error.name === "PermissionDeniedError"
    ) {
        updateStatus(
            "Camera access denied. Please enable camera permissions.",
            "error",
        );
    } else if (
        error.name === "NotFoundError" ||
        error.name === "DevicesNotFoundError"
    ) {
        updateStatus("No camera found. Please connect a camera.", "error");
    } else if (
        error.name === "NotReadableError" ||
        error.name === "TrackStartError"
    ) {
        updateStatus("Camera is in use. Retrying...", "warning");
        stopActiveStream();
        await new Promise((resolve) => setTimeout(resolve, 800));
        return startWebcamFallback(true);
    } else if (error.name === "OverconstrainedError") {
        updateStatus("Trying fallback camera settings...", "warning");
        return startWebcamFallback();
    } else {
        updateStatus("Cannot access camera: " + error.message, "error");
    }
    return false;
}

/**
 * Fallback webcam with simpler constraints
 */
async function startWebcamFallback(useDeviceId = false) {
    try {
        let videoConstraint = true;

        if (useDeviceId) {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const firstCamera = devices.find(
                (device) => device.kind === "videoinput",
            );
            if (firstCamera?.deviceId) {
                videoConstraint = { deviceId: { exact: firstCamera.deviceId } };
            }
        }

        stream = await navigator.mediaDevices.getUserMedia({
            video: videoConstraint,
            audio: false,
        });

        video.srcObject = stream;

        await new Promise((resolve) => {
            video.onloadedmetadata = () => {
                updateStatus(
                    "Camera started with fallback settings.",
                    "warning",
                );
                resolve();
            };
            setTimeout(resolve, 2000);
        });

        return true;
    } catch (error) {
        updateStatus("Failed to start camera with fallback settings.", "error");
        return false;
    }
}

/**
 * Draw face detection box
 */
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
    ctx.strokeStyle = "#00ff00";
    ctx.lineWidth = 3;
    ctx.strokeRect(box.x, box.y, box.width, box.height);

    // Draw landmarks
    faceapi.draw.drawFaceLandmarks(canvas, detectionsForSize);
}

/**
 * Start face detection loop
 */
function startFaceDetection() {
    if (!modelsLoaded || isProcessing) return;

    // Clear any existing interval
    if (detectionInterval) {
        clearInterval(detectionInterval);
    }

    detectionInterval = setInterval(async () => {
        try {
            // Detect faces
            const detections = await faceapi
                .detectAllFaces(
                    video,
                    new faceapi.TinyFaceDetectorOptions({
                        inputSize: 320,
                        scoreThreshold: 0.5,
                    }),
                )
                .withFaceLandmarks();

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
                        Math.pow(faceCenterY - videoCenterY, 2),
                );

                // Check face size
                const faceSizeOk = box.width > 100 && box.height > 100;

                if (distance < guideRadius && faceSizeOk && !isProcessing) {
                    updateStatus("Face detected! Verifying...", "warning");

                    // Stop detection and process
                    clearInterval(detectionInterval);
                    setTimeout(() => {
                        verifyAndSubmit();
                    }, 1500);
                } else {
                    const detectionStatus =
                        document.getElementById("detectionStatus");
                    if (detectionStatus) {
                        if (distance >= guideRadius) {
                            detectionStatus.textContent =
                                "Please center your face in the circle";
                        } else if (!faceSizeOk) {
                            detectionStatus.textContent =
                                "Please move closer to the camera";
                        }
                    }
                }
            } else {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const detectionStatus =
                    document.getElementById("detectionStatus");
                if (detectionStatus) {
                    detectionStatus.textContent =
                        "No face detected. Please look at the camera.";
                }
            }
        } catch (error) {
            console.error("Detection error:", error);
        }
    }, 100);
}

/**
 * Verify face and submit to server
 */
async function verifyAndSubmit() {
    if (isProcessing) return;

    isProcessing = true;
    updateStatus("Verifying identity...", "warning");

    try {
        // Capture current frame as base64
        const tempCanvas = document.createElement("canvas");
        tempCanvas.width = video.videoWidth;
        tempCanvas.height = video.videoHeight;
        const tempCtx = tempCanvas.getContext("2d");

        // Flip image horizontally for natural view
        tempCtx.translate(video.videoWidth, 0);
        tempCtx.scale(-1, 1);
        tempCtx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);

        const imageData = tempCanvas.toDataURL("image/jpeg", 0.8);

        // Get the route from hidden input or data attribute or default
        const faceProcessUrlInput = document.getElementById("faceProcessUrl");
        const submitUrl = faceProcessUrlInput
            ? faceProcessUrlInput.value
            : document.body.dataset.faceProcessUrl || "/checkin/face/process";

        // Send to server for verification
        const response = await fetch(submitUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify({
                username: username,
                check_type: checkType,
                image_data: imageData,
            }),
        });

        const result = await response.json();

        if (!response.ok) {
            showToast(`${result.message || "An error occurred during verification."}`, "error");
            setTimeout(() => {
                window.location.href = '/profile';
            }, 2000);
            return;
        }

        if (result.status) {
            // Success
            showToast(result.message, "success");
            updateStatus("Verification successful! Redirecting...", "success");

            // Get dashboard URL from data attribute or use default
            const dashboardUrl =
                document.body.dataset.dashboardUrl || "/user/dashboard";

            // Redirect to dashboard after delay
            setTimeout(() => {
                window.location.href = dashboardUrl;
            }, 2000);
        } else {
            // Error
            showToast(result.message, "error");
            updateStatus("Verification failed", "error");
            isProcessing = false;
            return;

            // Restart detection
            // startFaceDetection();
        }
    } catch (error) {
        console.error("Verification error:", error);
        showToast("Please check your connection", "error");
        updateStatus("Network error", "error");
        isProcessing = false;

        // Restart detection
        startFaceDetection();
    }
}

/**
 * Clean up resources
 */
function cleanup() {
    if (detectionInterval) {
        clearInterval(detectionInterval);
        detectionInterval = null;
    }

    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }

    if (video && video.srcObject) {
        video.srcObject = null;
    }
}

/**
 * Initialize face check-in system
 */
async function initializeFaceCheckin() {
    try {
        // Start webcam first
        const started = await startWebcam();

        if (!started) {
            updateStatus("Failed to start camera", "error");
            return false;
        }

        // Show capture button
        const captureBtn = document.getElementById("captureBtn");
        if (captureBtn) {
            captureBtn.classList.remove("hidden");
        }

        // Check if face-api is loaded
        if (!window.faceapi) {
            updateStatus("Face API library failed to load", "error");
            return false;
        }

        // Load models
        const loaded = await loadModels();
        if (!loaded) {
            updateStatus("Failed to load face recognition", "error");
            return false;
        }

        // Wait for camera to stabilize
        await new Promise((resolve) => setTimeout(resolve, 1000));

        // Start face detection
        startFaceDetection();
        return true;
    } catch (error) {
        console.error("Initialization error:", error);
        updateStatus("Initialization failed: " + error.message, "error");
        return false;
    }
}
