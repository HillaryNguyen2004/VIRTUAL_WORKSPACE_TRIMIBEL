// Chat file upload functionality
let selectedFile = null;
let selectedFileType = null;

// Open image in modal
function openImageModal(imageSrc, imageName) {
    document.getElementById("modalImage").src = imageSrc;
    document.getElementById("imageModalTitle").textContent = imageName;

    const modalEl = document.getElementById("imageModal");
    if (!modalEl) return;

    modalEl.classList.add("show");
    modalEl.style.display = "block";
    modalEl.removeAttribute("aria-hidden");
    modalEl.setAttribute("aria-modal", "true");
}

// Handle file selection
function handleFileSelect(input, type) {
    const file = input.files[0];
    if (!file) return;

    // File size validation (10MB)
    if (file.size > 10485760) {
        alert("File size must be less than 10MB");
        input.value = "";
        return;
    }

    selectedFile = file;
    selectedFileType = type;

    // Show file preview
    const preview = document.getElementById("filePreview");
    const fileName = document.getElementById("fileName");
    const fileSize = document.getElementById("fileSize");
    const fileIcon = document.getElementById("fileIcon");
    const imagePreview = document.getElementById("imagePreview");
    const previewImg = document.getElementById("previewImg");

    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);

    if (type === "image") {
        fileIcon.className = "bi bi-image-fill text-primary me-2";

        // Show image preview
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            imagePreview.style.display = "block";
        };
        reader.readAsDataURL(file);
    } else {
        fileIcon.className = "bi bi-file-earmark text-primary me-2";
        imagePreview.style.display = "none";
    }

    preview.style.display = "block";

    // Update placeholder text
    const messageInput = document.getElementById("messageInput");
    messageInput.placeholder =
        type === "image"
            ? "Add a caption (optional)..."
            : "Add a message (optional)...";
    messageInput.removeAttribute("required");
}

// Clear file selection
function clearFileSelection() {
    selectedFile = null;
    selectedFileType = null;

    document.getElementById("filePreview").style.display = "none";
    document.getElementById("imageInput").value = "";
    document.getElementById("fileInput").value = "";

    const messageInput = document.getElementById("messageInput");
    messageInput.placeholder = "Type your message...";
    messageInput.setAttribute("required", "required");
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}

// Enhanced message form submission
function enhanceMessageForm() {
    const messageForm = document.getElementById("messageForm");
    if (!messageForm) return;

    messageForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const messageInput = document.getElementById("messageInput");
        const content = messageInput.value.trim();

        // Validate: either have text content or a file
        if (!content && !selectedFile) {
            alert("Please enter a message or select a file to send.");
            return;
        }

        const formData = new FormData();
        formData.append(
            "_token",
            document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        );
        formData.append(
            "conversation_id",
            messageForm.querySelector('input[name="conversation_id"]').value,
        );

        if (content) {
            formData.append("content", content);
        }

        if (selectedFile) {
            if (selectedFileType === "image") {
                formData.append("image", selectedFile);
            } else {
                formData.append("file", selectedFile);
            }
        }

        // Disable form during upload
        const submitBtn = messageForm.querySelector('button[type="submit"]');
        const originalBtnContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML =
            '<i class="spinner-border spinner-border-sm me-1"></i> Sending...';

        fetch(messageForm.action || window.location.href, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                Accept: "application/json",
            },
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Add new message to chat
                    addMessageToChat(data.message);

                    // Clear form
                    messageInput.value = "";
                    clearFileSelection();

                    // Scroll to bottom
                    const chatMessages =
                        document.getElementById("chatMessages");
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                } else {
                    alert(data.message || "Failed to send message");
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("An error occurred while sending the message");
            })
            .finally(() => {
                // Re-enable form
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            });
    });
}

// Add message to chat display
function addMessageToChat(message) {
    const chatMessages = document.getElementById("chatMessages");
    const messageDiv = document.createElement("div");
    messageDiv.className = "mb-3";

    let messageContent = "";
    if (message.type === "image" && message.file_path) {
        const imageUrl = "/storage/" + message.file_path;
        messageContent = `
            <div class="message-image mb-2">
                <img src="${imageUrl}" 
                     alt="${message.file_name}" 
                     class="img-fluid rounded"
                     style="max-width: 300px; max-height: 200px; cursor: pointer;"
                     onclick="openImageModal('${imageUrl}', '${message.file_name}')"
                     loading="lazy">
                <div class="text-muted small mt-1">
                    ${message.file_name}
                </div>
            </div>
            ${message.content && message.content !== "Image: " + message.file_name ? `<div>${message.content}</div>` : ""}
        `;
    } else if (message.type === "file" && message.file_path) {
        const fileUrl = "/storage/" + message.file_path;
        messageContent = `
            <div class="message-file p-3 bg-light rounded border">
                <div class="d-flex align-items-center">
                    <i class="bi bi-file-earmark fs-4 me-3 text-primary"></i>
                    <div class="flex-grow-1">
                        <div class="fw-medium">${message.file_name}</div>
                        <div class="text-muted small">${message.file_size ? formatFileSize(message.file_size) : ""}</div>
                    </div>
                    <a href="${fileUrl}" 
                       download="${message.file_name}"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>
            </div>
            ${message.content && message.content !== "File: " + message.file_name ? `<div class="mt-2">${message.content}</div>` : ""}
        `;
    } else {
        messageContent = message.content;
    }

    messageDiv.innerHTML = `
        <div class="d-flex align-items-start">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-3" 
                 style="width: 40px; height: 40px; font-size: 14px; background: #17a2b8;">
                ${message.user.name.substring(0, 2).toUpperCase()}
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">
                    ${message.user.name}
                    <small class="text-muted">(You)</small>
                    <span class="text-muted small ms-2">just now</span>
                </div>
                <div class="text-muted small">just now</div>
                <div class="mt-1">${messageContent}</div>
            </div>
        </div>
    `;

    chatMessages.appendChild(messageDiv);
}

// Initialize file upload functionality
document.addEventListener("DOMContentLoaded", function () {
    enhanceMessageForm();
});
