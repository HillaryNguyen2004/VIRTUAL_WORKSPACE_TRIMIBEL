@extends('layouts.app')

@section('content')
<div class="container-fluid px-0" style="background:#17a2b8;">
    <div class="container py-3 d-flex align-items-center justify-content-between">
        <div>
            <span class="h4 text-white fw-bold">{{ $conversation->display_name }}</span>
            <span class="badge bg-light text-dark ms-2">
                @if($conversation->type === 'group')
                    <i class="bi bi-people"></i> Group Chat
                @else
                    <i class="bi bi-person"></i> Direct Message
                @endif
            </span>
        </div>
        <div class="d-flex align-items-center">
            <a href="{{ route('chat.index') }}" class="text-white">
                <i class="bi bi-arrow-left"></i> Back to Chat
            </a>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-chat-dots text-info"></i> 
                            {{ $conversation->display_name }}
                            <small class="text-muted">({{ $conversation->participants->count() }} participants)</small>
                        </h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <small id="searchResults" class="text-muted search-info" style="display: none;"></small>
                            </div>
                            <div class="input-group" style="width: 250px;">
                                <input type="text" 
                                       class="form-control form-control-sm" 
                                       id="searchInput"
                                       placeholder="Search messages..."
                                       style="border-radius: 0.375rem 0 0 0.375rem;">
                                <button class="btn btn-outline-secondary btn-sm" 
                                        type="button" 
                                        id="clearSearch"
                                        style="border-radius: 0 0.375rem 0.375rem 0;"
                                        title="Clear search">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body" style="height: 400px; overflow-y: auto;" id="chatMessages">
                    @forelse($messages as $message)
                        <div class="mb-3">
                            <div class="d-flex align-items-start">
                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px; font-size: 14px; background: {{ $message->user_id === auth()->id() ? '#17a2b8' : '#6c757d' }};">
                                    {{ strtoupper(substr($message->user->name, 0, 2)) }}
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">
                                        {{ $message->user->name }}
                                        @if($message->user_id === auth()->id())
                                            <small class="text-muted">(You)</small>
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ $message->created_at->diffForHumans() }}</div>
                                    <div class="mt-1">
                                        @if($message->type === 'image' && $message->hasFile())
                                            <div class="message-image mb-2">
                                                <img src="{{ $message->getFileUrl() }}" 
                                                     alt="{{ $message->file_name }}" 
                                                     class="img-fluid rounded"
                                                     style="max-width: 300px; max-height: 200px; cursor: pointer;"
                                                     onclick="openImageModal('{{ $message->getFileUrl() }}', '{{ $message->file_name }}')"
                                                     loading="lazy">
                                                <div class="text-muted small mt-1">
                                                    {{ $message->file_name }} ({{ $message->getFormattedFileSize() }})
                                                </div>
                                            </div>
                                            @if($message->content && $message->content !== 'Image: ' . $message->file_name)
                                                <div>{{ $message->content }}</div>
                                            @endif
                                        @elseif($message->type === 'file' && $message->hasFile())
                                            @php
                                                $ext = strtolower(pathinfo($message->file_name ?? '', PATHINFO_EXTENSION));
                                                $isPdf = $ext === 'pdf';
                                                $fileIcon = match($ext) {
                                                    'pdf' => 'bi-file-earmark-pdf text-danger',
                                                    'doc', 'docx' => 'bi-file-earmark-word text-primary',
                                                    'xls', 'xlsx' => 'bi-file-earmark-excel text-success',
                                                    'ppt', 'pptx' => 'bi-file-earmark-ppt text-warning',
                                                    'zip', 'rar', '7z' => 'bi-file-earmark-zip text-secondary',
                                                    'mp4', 'mov', 'avi' => 'bi-file-earmark-play text-info',
                                                    default => 'bi-file-earmark text-primary',
                                                };
                                            @endphp
                                            <div class="message-file p-3 bg-light rounded border">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi {{ $fileIcon }} fs-4 me-3"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-medium">{{ $message->file_name }}</div>
                                                        <div class="text-muted small">{{ $message->getFormattedFileSize() }}</div>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        @if($isPdf)
                                                            <button type="button"
                                                                class="btn btn-outline-secondary btn-sm"
                                                                onclick="openPdfPreview('{{ $message->getFileUrl() }}', '{{ e($message->file_name) }}')">
                                                                <i class="bi bi-eye"></i> Preview
                                                            </button>
                                                        @endif
                                                        <a href="{{ $message->getFileUrl() }}"
                                                           download="{{ $message->file_name }}"
                                                           class="btn btn-outline-primary btn-sm">
                                                            <i class="bi bi-download"></i> Download
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            @if($message->content && $message->content !== 'File: ' . $message->file_name)
                                                <div class="mt-2">{{ $message->content }}</div>
                                            @endif
                                        @else
                                            {{ $message->content }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-chat-dots display-4"></i>
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    @endforelse
                </div>

                <div class="card-footer">
                    <form id="messageForm" class="d-flex align-items-end">
                        @csrf
                        <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                        
                        <!-- File upload buttons -->
                        <div class="me-2">
                            <div class="btn-group-vertical">
                                <button type="button" 
                                        class="btn btn-outline-secondary btn-sm mb-1" 
                                        title="Send Image"
                                        onclick="document.getElementById('imageInput').click()">
                                    <i class="bi bi-image"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-secondary btn-sm" 
                                        title="Send File"
                                        onclick="document.getElementById('fileInput').click()">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                            </div>
                            <!-- Hidden file inputs -->
                            <input type="file" 
                                   id="imageInput" 
                                   name="image" 
                                   accept="image/*" 
                                   style="display: none;"
                                   onchange="handleFileSelect(this, 'image')">
                            <input type="file" 
                                   id="fileInput" 
                                   name="file" 
                                   style="display: none;"
                                   onchange="handleFileSelect(this, 'file')">
                        </div>
                        
                        <!-- Message input area -->
                        <div class="flex-grow-1 me-2">
                            <!-- File preview area -->
                            <div id="filePreview" class="mb-2" style="display: none;">
                                <div class="bg-light p-2 rounded border">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div id="fileInfo" class="d-flex align-items-center">
                                            <i id="fileIcon" class="me-2"></i>
                                            <span id="fileName"></span>
                                            <small id="fileSize" class="text-muted ms-2"></small>
                                        </div>
                                        <button type="button" class="btn btn-sm text-danger" onclick="clearFileSelection()">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <div id="imagePreview" style="display: none;">
                                        <img id="previewImg" class="mt-2 rounded" style="max-width: 200px; max-height: 100px;">
                                    </div>
                                </div>
                            </div>
                            
                            <input type="text" 
                                   class="form-control" 
                                   name="content"
                                   id="messageInput" 
                                   placeholder="Type your message..."
                                   required>
                        </div>
                        
                        <button type="submit" class="btn" style="background:#17a2b8;color:#fff;">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal for viewing full-size images -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalTitle">Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" class="img-fluid" alt="Full size image">
            </div>
        </div>
    </div>
</div>

<!-- PDF Preview Modal -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfPreviewTitle">PDF Preview</h5>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <a id="pdfDownloadLink" href="#" download class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download"></i> Download
                    </a>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="height: 80vh;">
                <iframe id="pdfPreviewFrame" src="" style="width:100%;height:100%;border:none;" title="PDF Preview"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Include file upload script -->
<script src="{{ asset('js/chat-file-upload.js') }}"></script>

<script>
document.getElementById('messageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageInput = document.getElementById('messageInput');
    
    fetch('{{ route("chat.message.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add new message to chat
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'mb-3';
            messageDiv.innerHTML = `
                <div class="d-flex align-items-start">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-3" 
                         style="width: 40px; height: 40px; font-size: 14px; background: #17a2b8;">
                        ${data.message.user.name.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${data.message.user.name} <small class="text-muted">(You)</small></div>
                        <div class="text-muted small">Just now</div>
                        <div class="mt-1">${data.message.content}</div>
                    </div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            messageInput.value = '';
            
            // Reapply search filter if there's an active search
            const searchInput = document.getElementById('searchInput');
            if (searchInput && searchInput.value.trim() !== '') {
                searchMessages(searchInput.value.toLowerCase().trim());
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending message');
    });
});

// Auto-scroll to bottom on page load
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Initialize search functionality
    initializeSearch();
});

function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const chatMessages = document.getElementById('chatMessages');
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        searchMessages(searchTerm);
    });
    
    // Clear search
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        searchMessages('');
        searchInput.focus();
    });
    
    // Enter key to search
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchMessages('');
        }
    });
}

function searchMessages(searchTerm) {
    const messageElements = document.querySelectorAll('#chatMessages .mb-3');
    const searchResults = document.getElementById('searchResults');
    let foundCount = 0;
    
    messageElements.forEach(messageEl => {
        const messageContent = messageEl.textContent.toLowerCase();
        const isMatch = searchTerm === '' || messageContent.includes(searchTerm);
        
        if (isMatch) {
            messageEl.style.display = 'block';
            messageEl.style.backgroundColor = '';
            foundCount++;
            
            // Highlight search term if not empty
            if (searchTerm !== '') {
                highlightSearchTerm(messageEl, searchTerm);
                messageEl.style.backgroundColor = '#fff3cd'; // Light yellow background
            } else {
                removeHighlight(messageEl);
            }
        } else {
            messageEl.style.display = 'none';
        }
    });
    
    // Update search results info
    if (searchTerm !== '') {
        searchResults.style.display = 'block';
        if (foundCount === 0) {
            searchResults.textContent = 'No messages found';
            searchResults.className = 'text-danger search-info';
        } else {
            searchResults.textContent = `${foundCount} message${foundCount !== 1 ? 's' : ''} found`;
            searchResults.className = 'text-success search-info';
        }
    } else {
        searchResults.style.display = 'none';
    }
    
    // Update search input styling based on results
    const searchInput = document.getElementById('searchInput');
    if (searchTerm !== '' && foundCount === 0) {
        searchInput.style.borderColor = '#dc3545'; // Red border if no results
        searchInput.title = 'No messages found';
    } else {
        searchInput.style.borderColor = '';
        searchInput.title = searchTerm !== '' ? `${foundCount} message(s) found` : '';
    }
}

function highlightSearchTerm(element, searchTerm) {
    const messageContentDiv = element.querySelector('.mt-1');
    if (messageContentDiv) {
        const originalText = messageContentDiv.textContent;
        const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
        const highlightedText = originalText.replace(regex, '<mark>$1</mark>');
        messageContentDiv.innerHTML = highlightedText;
    }
}

function removeHighlight(element) {
    const messageContentDiv = element.querySelector('.mt-1');
    if (messageContentDiv) {
        // Store original text if not already stored
        if (!messageContentDiv.dataset.originalText) {
            messageContentDiv.dataset.originalText = messageContentDiv.textContent;
        }
        messageContentDiv.innerHTML = messageContentDiv.dataset.originalText;
    }
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
</script>

<style>
/* Search highlight styling */
mark {
    background-color: #ffeb3b !important;
    color: #000 !important;
    padding: 1px 2px;
    border-radius: 2px;
}

/* Search input focus styling */
#searchInput:focus {
    border-color: #17a2b8 !important;
    box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25) !important;
}

/* Clear search button hover */
#clearSearch:hover {
    background-color: #e9ecef !important;
}

/* Message highlighting animation */
.mb-3 {
    transition: background-color 0.3s ease;
}

/* No results state */
.no-results {
    border-color: #dc3545 !important;
}

/* Search results info */
.search-info {
    font-size: 0.75rem;
    color: #6c757d;
}
</style>

@endsection
