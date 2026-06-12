<!-- BACKUP of resources/views/chat/index.blade.php -->

```php
<!-- @extends('layouts.app')

@section('content')
<div class="container-fluid px-0" style="background:#17a2b8;">
    <div class="container py-3 d-flex align-items-center justify-content-between">
        <div>
            <span class="h4 text-white fw-bold">Team Chat</span>
            <span class="badge bg-light text-dark ms-2">{{ $conversations->count() }} conversations</span>
        </div>
        <div class="d-flex align-items-center">
            <button class="btn btn-light me-3" data-bs-toggle="modal" data-bs-target="#newChatModal">
                <i class="bi bi-plus-circle"></i> New Chat
            </button>
            <a href="{{ auth()->user()->hasRole('staff') ? route('staff.dashboard') : route('dashboard') }}" class="text-white">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div> -->

<div class="container py-4">
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="row">
        <!-- Conversations List -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <ul class="nav nav-tabs" id="chatTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="conversations-tab" data-bs-toggle="tab" data-bs-target="#conversationsPane" type="button" role="tab" aria-controls="conversationsPane" aria-selected="true">Conversations</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="channels-tab" data-bs-toggle="tab" data-bs-target="#channelsPane" type="button" role="tab" aria-controls="channelsPane" aria-selected="false">Channels</button>
                                </li>
                            </ul>
                        </div>
                        <small id="conversationCount" class="text-muted">{{ $conversations->count() }} total</small>
                    </div>
                    <!-- Search Bar -->
                    <div class="mt-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   class="form-control border-start-0" 
                                   id="conversationSearch"
                                   placeholder="Search conversations..."
                                   style="border-left: none;">
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    id="clearConversationSearch"
                                    title="Clear search">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <div id="searchResultsInfo" class="mt-1" style="display: none;">
                            <small class="text-muted"></small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;" id="conversationsList">
                    @forelse($conversations as $conversation)
                        <a href="{{ route('chat.conversation', $conversation) }}" class="text-decoration-none conversation-item" data-conversation-name="{{ strtolower($conversation->display_name) }}" data-last-message="{{ $conversation->lastMessage ? strtolower($conversation->lastMessage->content) : '' }}" data-last-sender="{{ $conversation->lastMessage ? strtolower($conversation->lastMessage->user->name) : '' }}">
                            <div class="d-flex align-items-center p-3 border-bottom hover-bg-light">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 50px; height: 50px; background: #17a2b8; color: white; font-size: 16px;">
                                    @if($conversation->type === 'group')
                                        <i class="bi bi-people"></i>
                                    @else
                                        {{ strtoupper(substr($conversation->display_name, 0, 2)) }}
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">
                                        {{ $conversation->display_name }}
                                        @if($conversation->unread_count > 0)
                                            <span class="badge bg-danger rounded-pill">{{ $conversation->unread_count }}</span>
                                        @endif
                                    </div>
                                    @if($conversation->lastMessage)
                                        <div class="text-muted small">
                                            <strong>{{ $conversation->lastMessage->user->name }}:</strong>
                                            {{ Str::limit($conversation->lastMessage->content, 30) }}
                                        </div>
                                        <div class="text-muted small">{{ $conversation->lastMessage->created_at->diffForHumans() }}</div>
                                    @else
                                        <div class="text-muted small">No messages yet</div>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center p-4 text-muted" id="noConversations">
                            <i class="bi bi-chat-dots display-4"></i>
                            <p>No conversations yet. Start a new chat!</p>
                        </div>
                    @endforelse
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="conversationsPane" role="tabpanel" aria-labelledby="conversations-tab">
                            <div id="conversationsList">
                                @forelse($conversations as $conversation)
                                    <a href="{{ route('chat.conversation', $conversation) }}" class="text-decoration-none conversation-item" data-conversation-name="{{ strtolower($conversation->display_name) }}" data-last-message="{{ $conversation->lastMessage ? strtolower($conversation->lastMessage->content) : '' }}" data-last-sender="{{ $conversation->lastMessage ? strtolower($conversation->lastMessage->user->name) : '' }}">
                                        <div class="d-flex align-items-center p-3 border-bottom hover-bg-light">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 50px; height: 50px; background: #17a2b8; color: white; font-size: 16px;">
                                                @if($conversation->type === 'group')
                                                    <i class="bi bi-people"></i>
                                                @else
                                                    {{ strtoupper(substr($conversation->display_name, 0, 2)) }}
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">
                                                    {{ $conversation->display_name }}
                                                    @if($conversation->unread_count > 0)
                                                        <span class="badge bg-danger rounded-pill">{{ $conversation->unread_count }}</span>
                                                    @endif
                                                </div>
                                                @if($conversation->lastMessage)
                                                    <div class="text-muted small">
                                                        <strong>{{ $conversation->lastMessage->user->name }}:</strong>
                                                        {{ Str::limit($conversation->lastMessage->content, 30) }}
                                                    </div>
                                                    <div class="text-muted small">{{ $conversation->lastMessage->created_at->diffForHumans() }}</div>
                                                @else
                                                    <div class="text-muted small">No messages yet</div>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="text-center p-4 text-muted" id="noConversations">
                                        <i class="bi bi-chat-dots display-4"></i>
                                        <p>No conversations yet. Start a new chat!</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="tab-pane fade" id="channelsPane" role="tabpanel" aria-labelledby="channels-tab">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Channels</strong>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" id="refreshChannelsBtn">Refresh</button>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newChannelModal">New Channel</button>
                                    </div>
                                </div>
                                <div id="channelsList" style="max-height: 380px; overflow-y: auto;">
                                    <div class="text-center text-muted py-4">Loading channels...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <i class="bi bi-chat-heart display-1" style="color: #17a2b8;"></i>
                        <h3 class="mt-3">Welcome to Team Chat!</h3>
                        <p class="text-muted">Select a conversation to start chatting, or create a new one.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newChatModal" style="background: #17a2b8; border-color: #17a2b8;">
                            <i class="bi bi-plus-circle"></i> Start New Conversation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Chat Modal -->
<div class="modal fade" id="newChatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('chat.create') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New Conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Conversation Type</label>
                        <select class="form-select" name="type" id="conversationType" required>
                            <option value="direct">Direct Message</option>
                            <option value="group">Group Chat</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="groupNameField" style="display: none;">
                        <label class="form-label">Group Name</label>
                        <input type="text" class="form-control" name="name" placeholder="Enter group name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Participants</label>
                        <div id="participantsValidation" class="text-danger small mb-2" style="display: none;"></div>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.5rem;">
                            @foreach($users as $user)
                                <div class="form-check">
                                    <input class="form-check-input participant-checkbox" type="checkbox" name="participants[]" value="{{ $user->id }}" id="user{{ $user->id }}">
                                    <label class="form-check-label" for="user{{ $user->id }}">
                                        {{ $user->name }} ({{ $user->email }})
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text" id="participantHelp">
                            For direct message: Select exactly 1 person. For group chat: Select multiple people.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #17a2b8; border-color: #17a2b8;">Create Conversation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Channel Modal -->
<div class="modal fade" id="newChannelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="newChannelForm">
                <div class="modal-header">
                    <h5 class="modal-title">Create Channel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Channel Name</label>
                        <input type="text" class="form-control" name="name" id="channelName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="channelDescription"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="channelPrivate" name="is_private">
                        <label class="form-check-label" for="channelPrivate">Private channel (invite only)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Channel Details Modal -->
<div class="modal fade" id="channelDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="channelDetailsTitle">Channel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="channelDetailsBody">
                <div class="mb-3">
                    <strong>Description</strong>
                    <p id="channelDetailsDescription" class="text-muted"></p>
                </div>
                <div class="mb-3">
                    <strong>Members</strong>
                    <div id="channelMembersList" class="mt-2"></div>
                </div>
                <div class="mb-3">
                    <strong>Rules</strong>
                    <div id="channelRulesList" class="mt-2"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initial setup
document.addEventListener('DOMContentLoaded', function() {
    const conversationType = document.getElementById('conversationType');
    const groupNameInput = document.querySelector('input[name="name"]');
    
    // Set initial state for direct message (default)
    if (conversationType.value === 'direct') {
        groupNameInput.disabled = true;
        groupNameInput.removeAttribute('name');
    }
});

document.getElementById('conversationType').addEventListener('change', function() {
    const groupNameField = document.getElementById('groupNameField');
    const groupNameInput = groupNameField.querySelector('input');
    const participantHelp = document.getElementById('participantHelp');
    const validationDiv = document.getElementById('participantsValidation');
    
    if (this.value === 'group') {
        groupNameField.style.display = 'block';
        groupNameInput.required = true;
        groupNameInput.disabled = false;
        participantHelp.textContent = 'For group chat: Select multiple people.';
    } else {
        groupNameField.style.display = 'none';
        groupNameInput.required = false;
        groupNameInput.disabled = true;
        groupNameInput.value = ''; // Clear the value
        participantHelp.textContent = 'For direct message: Select exactly 1 person.';
    }
    
    // Clear previous validation
    validationDiv.style.display = 'none';
    validationDiv.textContent = '';
    
    // Clear all checkboxes when switching types
    document.querySelectorAll('.participant-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
});

// Add form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const conversationType = document.getElementById('conversationType').value;
    const checkedParticipants = document.querySelectorAll('.participant-checkbox:checked');
    const validationDiv = document.getElementById('participantsValidation');
    const groupNameInput = document.querySelector('input[name="name"]');
    
    let isValid = true;
    let errorMessage = '';
    
    // Disable name field for direct messages to prevent it from being sent
    if (conversationType === 'direct') {
        groupNameInput.disabled = true;
        groupNameInput.removeAttribute('name'); // Remove name attribute so it won't be sent
        
        if (checkedParticipants.length === 0) {
            isValid = false;
            errorMessage = 'Please select exactly one person for direct message.';
        } else if (checkedParticipants.length > 1) {
            isValid = false;
            errorMessage = 'For direct message, please select only one person.';
        }
    } else if (conversationType === 'group') {
        groupNameInput.disabled = false;
        groupNameInput.setAttribute('name', 'name'); // Restore name attribute
        
        if (checkedParticipants.length === 0) {
            isValid = false;
            errorMessage = 'Please select at least one person for group chat.';
        }
        
        const groupName = groupNameInput.value.trim();
        if (!groupName) {
            isValid = false;
            errorMessage = 'Please enter a group name.';
        }
    }
    
    if (!isValid) {
        e.preventDefault();
        validationDiv.textContent = errorMessage;
        validationDiv.style.display = 'block';
        return false;
    }
    
    validationDiv.style.display = 'none';
});

// Real-time validation for direct messages
document.querySelectorAll('.participant-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const conversationType = document.getElementById('conversationType').value;
        const checkedParticipants = document.querySelectorAll('.participant-checkbox:checked');
        const validationDiv = document.getElementById('participantsValidation');
        
        if (conversationType === 'direct' && checkedParticipants.length > 1) {
            // Uncheck other checkboxes for direct message
            document.querySelectorAll('.participant-checkbox').forEach(cb => {
                if (cb !== this) {
                    cb.checked = false;
                }
            });
            
            validationDiv.textContent = 'For direct message, only one person can be selected.';
            validationDiv.style.display = 'block';
            
            setTimeout(() => {
                validationDiv.style.display = 'none';
            }, 3000);
        } else {
            validationDiv.style.display = 'none';
        }
    });
});

// Initialize conversation search
document.addEventListener('DOMContentLoaded', function() {
    initializeConversationSearch();
});

function initializeConversationSearch() {
    const searchInput = document.getElementById('conversationSearch');
    const clearSearchBtn = document.getElementById('clearConversationSearch');
    const conversationsList = document.getElementById('conversationsList');
    const totalConversations = document.querySelectorAll('.conversation-item').length;
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        searchConversations(searchTerm, totalConversations);
    });
    
    // Clear search
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        searchConversations('', totalConversations);
        searchInput.focus();
    });
    
    // Keyboard shortcuts
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchConversations('', totalConversations);
        }
    });
}

function searchConversations(searchTerm, totalCount) {
    const conversationItems = document.querySelectorAll('.conversation-item');
    const noConversationsDiv = document.getElementById('noConversations');
    const searchResultsInfo = document.getElementById('searchResultsInfo');
    const conversationCount = document.getElementById('conversationCount');
    
    let visibleCount = 0;
    
    conversationItems.forEach(item => {
        const conversationName = item.dataset.conversationName || '';
        const lastMessage = item.dataset.lastMessage || '';
        const lastSender = item.dataset.lastSender || '';
        
        const isMatch = searchTerm === '' || 
                       conversationName.includes(searchTerm) || 
                       lastMessage.includes(searchTerm) || 
                       lastSender.includes(searchTerm);
        
        if (isMatch) {
            item.style.display = 'block';
            visibleCount++;
            
            // Highlight search term if not empty
            if (searchTerm !== '') {
                highlightConversationSearchTerm(item, searchTerm);
            } else {
                removeConversationHighlight(item);
            }
        } else {
            item.style.display = 'none';
        }
    });
    
    // Handle "no conversations" message
    if (totalCount === 0) {
        if (noConversationsDiv) noConversationsDiv.style.display = 'block';
    } else if (visibleCount === 0 && searchTerm !== '') {
        if (noConversationsDiv) noConversationsDiv.style.display = 'none';
        showNoSearchResults();
    } else {
        if (noConversationsDiv) noConversationsDiv.style.display = visibleCount === 0 ? 'block' : 'none';
        hideNoSearchResults();
    }
    
    // Update search results info
    updateSearchResults(searchTerm, visibleCount, totalCount);
}

function highlightConversationSearchTerm(element, searchTerm) {
    const conversationName = element.querySelector('.fw-bold');
    const lastMessageElement = element.querySelector('.text-muted.small');
    
    if (conversationName) {
        const originalText = conversationName.textContent;
        if (!conversationName.dataset.originalText) {
            conversationName.dataset.originalText = originalText;
        }
        const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
        const highlightedText = conversationName.dataset.originalText.replace(regex, '<mark>$1</mark>');
        conversationName.innerHTML = highlightedText;
    }
    
    if (lastMessageElement) {
        const originalText = lastMessageElement.textContent;
        if (!lastMessageElement.dataset.originalText) {
            lastMessageElement.dataset.originalText = originalText;
        }
        const regex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
        const highlightedText = lastMessageElement.dataset.originalText.replace(regex, '<mark>$1</mark>');
        lastMessageElement.innerHTML = highlightedText;
    }
}

function removeConversationHighlight(element) {
    const conversationName = element.querySelector('.fw-bold');
    const lastMessageElement = element.querySelector('.text-muted.small');
    
    if (conversationName && conversationName.dataset.originalText) {
        conversationName.innerHTML = conversationName.dataset.originalText;
    }
    
    if (lastMessageElement && lastMessageElement.dataset.originalText) {
        lastMessageElement.innerHTML = lastMessageElement.dataset.originalText;
    }
}

function updateSearchResults(searchTerm, visibleCount, totalCount) {
    const searchResultsInfo = document.getElementById('searchResultsInfo');
    const conversationCount = document.getElementById('conversationCount');
    
    if (searchTerm !== '') {
        searchResultsInfo.style.display = 'block';
        const resultText = searchResultsInfo.querySelector('small');
        
        if (visibleCount === 0) {
            resultText.textContent = 'No conversations found';
            resultText.className = 'text-danger';
        } else {
            resultText.textContent = `${visibleCount} of ${totalCount} conversation${visibleCount !== 1 ? 's' : ''}`;
            resultText.className = 'text-success';
        }
        
        conversationCount.textContent = `${visibleCount} of ${totalCount}`;
    } else {
        searchResultsInfo.style.display = 'none';
        conversationCount.textContent = `${totalCount} total`;
    }
}

function showNoSearchResults() {
    const conversationsList = document.getElementById('conversationsList');
    let noResultsDiv = document.getElementById('noSearchResults');
    
    if (!noResultsDiv) {
        noResultsDiv = document.createElement('div');
        noResultsDiv.id = 'noSearchResults';
        noResultsDiv.className = 'text-center p-4 text-muted';
        noResultsDiv.innerHTML = `
            <i class="bi bi-search display-4"></i>
            <p>No conversations match your search.</p>
            <small>Try searching for a different term.</small>
        `;
        conversationsList.appendChild(noResultsDiv);
    } else {
        noResultsDiv.style.display = 'block';
    }
}

function hideNoSearchResults() {
    const noResultsDiv = document.getElementById('noSearchResults');
    if (noResultsDiv) {
        noResultsDiv.style.display = 'none';
    }
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
</script>

<style>
.hover-bg-light:hover {
    background-color: #f8f9fa !important;
}

/* Conversation search styling */
#conversationSearch:focus {
    border-color: #17a2b8 !important;
    box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25) !important;
}

#clearConversationSearch:hover {
    background-color: #e9ecef !important;
}

/* Search highlight styling */
mark {
    background-color: #ffeb3b !important;
    color: #000 !important;
    padding: 1px 2px;
    border-radius: 2px;
}

/* Search input group styling */
.input-group-text {
    background-color: #f8f9fa !important;
}

/* Smooth transitions */
.conversation-item {
    transition: all 0.2s ease;
}

.conversation-item:hover {
    transform: translateX(2px);
}

/* Search results info */
#searchResultsInfo small {
    font-size: 0.75rem;
}

/* No results styling */
#noSearchResults {
    border-top: 1px solid #dee2e6;
}
</style>
@endsection

<script>
// Channels UI interactions
document.addEventListener('DOMContentLoaded', function() {
    const refreshBtn = document.getElementById('refreshChannelsBtn');
    if (refreshBtn) refreshBtn.addEventListener('click', loadChannels);

    // Load channels when channels tab is shown
    const channelsTab = document.getElementById('channels-tab');
    if (channelsTab) {
        channelsTab.addEventListener('shown.bs.tab', function (e) {
            loadChannels();
        });
    }

    // New channel form submit
    const newChannelForm = document.getElementById('newChannelForm');
    if (newChannelForm) {
        newChannelForm.addEventListener('submit', function(e) {
            e.preventDefault();
            createChannel();
        });
    }
});

function loadChannels() {
    const list = document.getElementById('channelsList');
    if (!list) return;
    list.innerHTML = '<div class="text-center text-muted py-4">Loading channels...</div>';

    fetch('/api/chat/channels', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        if (!Array.isArray(data)) {
            list.innerHTML = '<div class="text-danger p-3">Failed to load channels.</div>';
            return;
        }

        if (data.length === 0) {
            list.innerHTML = '<div class="text-center text-muted p-3">No channels yet.</div>';
            return;
        }

        list.innerHTML = '';
        data.forEach(ch => {
            const isMember = !!ch.is_member;
            const membersCount = ch.members_count || 0;
            const item = document.createElement('div');
            item.className = 'd-flex align-items-start p-3 border-bottom';
            item.innerHTML = `
                <div class="flex-grow-1">
                    <div class="fw-bold">${escapeHtml(ch.name)} ${ch.is_private ? '<small class="text-muted">(private)</small>' : ''}</div>
                    <div class="text-muted small">${escapeHtml(ch.description || '')}</div>
                    <div class="text-muted small">Members: ${membersCount}</div>
                </div>
                <div class="ms-2 d-flex flex-column align-items-end">
                    <button class="btn btn-sm ${isMember ? 'btn-outline-danger' : 'btn-outline-success'} mb-1" data-id="${ch.id}" onclick="toggleMembership(${ch.id}, ${isMember})">${isMember ? 'Leave' : 'Join'}</button>
                    <button class="btn btn-sm btn-secondary" onclick="showChannelDetails(${ch.id})">Details</button>
                </div>
            `;
            list.appendChild(item);
        });
    })
    .catch(err => {
        list.innerHTML = '<div class="text-danger p-3">Error loading channels.</div>';
        console.error(err);
    });
}

function createChannel() {
    const name = document.getElementById('channelName').value.trim();
    const description = document.getElementById('channelDescription').value.trim();
    const isPrivate = document.getElementById('channelPrivate').checked ? 1 : 0;

    if (!name) return alert('Channel name is required');

    fetch('/api/chat/channels', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ name, description, is_private: !!isPrivate })
    })
    .then(async res => {
        if (!res.ok) throw await res.json();
        return res.json();
    })
    .then(data => {
        const modalEl = document.getElementById('newChannelModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        document.getElementById('channelName').value = '';
        document.getElementById('channelDescription').value = '';
        document.getElementById('channelPrivate').checked = false;
        loadChannels();
        alert('Channel created');
    })
    .catch(err => {
        console.error(err);
        alert('Failed to create channel');
    });
}

function joinChannel(id) {
    // simple wrapper to join (used previously)
    fetch(`/api/chat/channels/${id}/join`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message || 'Joined channel');
        loadChannels();
    })
    .catch(err => {
        console.error(err);
        alert('Failed to join channel');
    });
}

function toggleMembership(id, isMember) {
    if (isMember) {
        fetch(`/api/chat/channels/${id}/leave`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message || 'Left channel');
            loadChannels();
        })
        .catch(err => {
            console.error(err);
            alert('Failed to leave channel');
        });
    } else {
        joinChannel(id);
    }
}

function showChannelDetails(id) {
    fetch(`/api/chat/channels/${id}`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(ch => {
        document.getElementById('channelDetailsTitle').textContent = ch.name || 'Channel';
        document.getElementById('channelDetailsDescription').textContent = ch.description || '';

        const membersDiv = document.getElementById('channelMembersList');
        membersDiv.innerHTML = '';
        if (Array.isArray(ch.members) && ch.members.length) {
            ch.members.forEach(m => {
                const el = document.createElement('div');
                el.className = 'd-flex align-items-center mb-2';
                el.innerHTML = `<div class="me-2 rounded-circle" style="width:32px;height:32px;background:#e9ecef;border-radius:50%;display:flex;align-items:center;justify-content:center">${(m.name||'').charAt(0).toUpperCase()}</div><div>${escapeHtml(m.name)} <small class="text-muted">${escapeHtml(m.email||'')}</small></div>`;
                membersDiv.appendChild(el);
            });
        } else {
            membersDiv.innerHTML = '<div class="text-muted">No members yet</div>';
        }

        const rulesDiv = document.getElementById('channelRulesList');
        rulesDiv.innerHTML = '';
        if (Array.isArray(ch.rules) && ch.rules.length) {
            ch.rules.forEach(r => {
                const el = document.createElement('div');
                el.className = 'mb-2';
                el.innerHTML = `<strong>${escapeHtml(r.title)}</strong><div class="text-muted small">${escapeHtml(r.content||'')}</div>`;
                rulesDiv.appendChild(el);
            });
        } else {
            rulesDiv.innerHTML = '<div class="text-muted">No rules defined</div>';
        }

        const modalEl = document.getElementById('channelDetailsModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    })
    .catch(err => console.error(err));
}

function escapeHtml(unsafe) {
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/\"/g, "&quot;")
         .replace(/'/g, "&#039;");
}
</script>

```