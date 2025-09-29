@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="h4 mb-0">Real-time Chat</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" onclick="showNewConversationModal()">
                        <i class="fas fa-plus"></i> New Chat
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="onlineUsersBtn">
                        <i class="fas fa-users"></i> Online (<span id="online-count">0</span>)
                    </button>
                    <a href="{{ auth()->user()->hasRole('staff') ? route('staff.dashboard') : route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Chat Interface -->
    <div class="row">
        <!-- Conversations Sidebar -->
        <div class="col-md-4 col-lg-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Conversations</h6>
                    <div class="input-group input-group-sm mt-2">
                        <input type="text" class="form-control" id="conversationSearch" placeholder="Search conversations...">
                        <button class="btn btn-outline-secondary" type="button" id="clearConversationSearch">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    <div id="conversations-list">
                        <div class="text-center p-4">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <div class="mt-2">Loading conversations...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="col-md-8 col-lg-9">
            <div class="card h-100">
                <!-- Chat Header -->
                <div class="card-header" id="chat-header">
                    <div class="text-center text-muted">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p class="mb-0">Select a conversation to start chatting</p>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="card-body" id="messages-container" style="height: 400px; overflow-y: auto; display: none;">
                    <div id="messages-list"></div>
                    <div id="typing-indicator" style="display: none;" class="text-muted small">
                        <i class="fas fa-circle" style="animation: pulse 1.5s ease-in-out infinite;"></i>
                        <span id="typing-text">Someone is typing...</span>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="card-footer" id="message-input-container" style="display: none;">
                    <form id="message-form" class="d-flex gap-2">
                        <textarea 
                            class="form-control" 
                            id="message-input" 
                            placeholder="Type a message..." 
                            rows="1"
                            style="resize: none; overflow-y: hidden;"
                        ></textarea>
                        <button type="submit" class="btn btn-primary" id="send-btn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button type="button" class="btn btn-success" id="video-call-btn" title="Start Video Call">
                            <i class="fas fa-video"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Conversation Modal -->
<div class="modal fade" id="newConversationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="new-conversation-form">
                    <div class="mb-3">
                        <label class="form-label">Conversation Type</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="type" id="directType" value="direct" checked>
                                <label class="form-check-label" for="directType">Direct Message</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="type" id="groupType" value="group">
                                <label class="form-check-label" for="groupType">Group Chat</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="groupNameField" style="display: none;">
                        <label for="groupName" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="groupName" placeholder="Enter group name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Search Users</label>
                        <input type="text" class="form-control" id="userSearch" placeholder="Type to search users...">
                        <div id="userSearchResults" class="mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Selected Users</label>
                        <div id="selectedUsers" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="createConversationBtn">Create Conversation</button>
            </div>
        </div>
    </div>
</div>

<!-- Online Users Modal -->
<div class="modal fade" id="onlineUsersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Online Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="onlineUsersList">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <div class="mt-2">Loading online users...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.conversation-item {
    border-bottom: 1px solid #eee;
    padding: 12px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.conversation-item:hover {
    background-color: #f8f9fa;
}

.conversation-item.active {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.message {
    margin-bottom: 15px;
}

.message.own {
    text-align: right;
}

.message.own .message-content {
    background-color: #2196f3;
    color: white;
    margin-left: auto;
}

.message-content {
    max-width: 70%;
    padding: 8px 12px;
    border-radius: 18px;
    background-color: #f1f3f4;
    display: inline-block;
    word-wrap: break-word;
}

.message-info {
    font-size: 0.75rem;
    color: #666;
    margin-top: 4px;
}

.video-call-message {
    background-color: #e8f5e8;
    border: 1px solid #4caf50;
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
    text-align: center;
}

.video-call-message .btn {
    margin: 5px;
}

.typing-indicator {
    padding: 8px 12px;
    background-color: #f1f3f4;
    border-radius: 18px;
    display: inline-block;
    margin-bottom: 10px;
}

.user-tag {
    background-color: #2196f3;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    margin: 2px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.user-tag .remove-user {
    cursor: pointer;
    font-weight: bold;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.online-indicator {
    width: 8px;
    height: 8px;
    background-color: #4caf50;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Global variables from Laravel
window.currentUserId = {{ auth()->id() }};

class RealtimeChatApp {
    constructor() {
        this.apiUrl = '/api/chat';
        this.currentConversation = null;
        this.conversations = new Map();
        this.onlineUsers = new Set();
        this.selectedUsers = new Set();
        this.typingTimeout = null;
        this.isTyping = false;
        this.currentUserId = window.currentUserId;
        this.pollInterval = null;
        
        this.init();
    }

    async init() {
        // Setup CSRF token and headers for Sanctum
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
        }
        
        // Setup axios defaults for Sanctum
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.withCredentials = true;
        
        // First, get CSRF cookie
        try {
            await axios.get('/api/sanctum/csrf-cookie');
        } catch (error) {
            console.warn('Could not get CSRF cookie:', error);
        }
        
        // Load initial data
        await this.loadConversations();
        await this.loadOnlineUsers();
        
        // Setup real-time listeners (disabled - using polling instead)
        // this.setupRealtimeListeners();
        
        // Setup polling for messages (fallback for real-time)
        this.setupPolling();
        
        // Setup UI event handlers
        this.setupEventHandlers();
        
        // Request notification permission
        this.requestNotificationPermission();
        
        console.log('Chat initialized');
    }

    // API Methods
    async loadConversations() {
        try {
            const response = await axios.get(`${this.apiUrl}/conversations`);
            if (response.data && response.data.success) {
                this.conversations.clear();
                response.data.data.conversations.forEach(conv => {
                    this.conversations.set(conv.id, conv);
                });
                this.renderConversations();
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
            // Show fallback message
            document.getElementById('conversations-list').innerHTML = `
                <div class="text-center p-4 text-muted">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Unable to load conversations</p>
                    <button class="btn btn-primary btn-sm" onclick="chatApp.loadConversations()">
                        Retry
                    </button>
                </div>
            `;
        }
    }

    async loadMessages(conversationId) {
        try {
            const response = await axios.get(`${this.apiUrl}/conversations/${conversationId}`);
            if (response.data.success) {
                return response.data.data;
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            return null;
        }
    }

    async sendMessage(conversationId, content, type = 'text') {
        try {
            const response = await axios.post(`${this.apiUrl}/conversations/${conversationId}/messages`, {
                content,
                type
            });
            
            if (response.data.success) {
                // Reload conversations to update last message and timestamp
                await this.loadConversations();
                
                // If this is the current conversation, reload messages
                if (this.currentConversation && this.currentConversation.id === conversationId) {
                    const data = await this.loadMessages(conversationId);
                    if (data) {
                        this.renderMessages(data.messages);
                    }
                }
            }
            
            return response.data.success;
        } catch (error) {
            console.error('Error sending message:', error);
            return false;
        }
    }

    async createConversation(type, participantIds, name = null) {
        try {
            const response = await axios.post(`${this.apiUrl}/conversations`, {
                type,
                participant_ids: participantIds,
                name
            });
            if (response.data.success) {
                const conversation = response.data.data.conversation;
                this.conversations.set(conversation.id, conversation);
                this.renderConversations();
                
                // Show success notification
                this.showNotification(`Conversation created: ${conversation.display_name || conversation.name || 'New Chat'}`);
                
                return conversation;
            }
        } catch (error) {
            console.error('Error creating conversation:', error);
            return null;
        }
    }

    async searchUsers(query) {
        try {
            const response = await axios.get(`${this.apiUrl}/users/search?query=${encodeURIComponent(query)}`);
            if (response.data.success) {
                return response.data.data.users;
            }
        } catch (error) {
            console.error('Error searching users:', error);
            return [];
        }
    }

    async loadOnlineUsers() {
        try {
            const response = await axios.get(`${this.apiUrl}/users/online`);
            if (response.data.success) {
                this.onlineUsers.clear();
                response.data.data.users.forEach(user => {
                    this.onlineUsers.add(user.id);
                });
                this.updateOnlineCount();
            }
        } catch (error) {
            console.error('Error loading online users:', error);
        }
    }

    async createVideoCall(conversationId) {
        try {
            const response = await axios.post(`${this.apiUrl}/conversations/${conversationId}/video-call`);
            if (response.data.success) {
                const meetingUrl = response.data.data.meeting_url;
                window.open(meetingUrl, '_blank');
                return true;
            }
        } catch (error) {
            console.error('Error creating video call:', error);
            return false;
        }
    }

    // Real-time Event Listeners
    setupRealtimeListeners() {
        if (typeof Echo === 'undefined') {
            console.warn('Laravel Echo not available. Real-time features disabled.');
            return;
        }

        // Listen for new conversations created for this user
        Echo.private(`user.{{ Auth::id() }}`)
            .listen('conversation.created', (e) => {
                console.log('New conversation created:', e.conversation);
                
                // Add the new conversation to our local data
                this.conversations.set(e.conversation.id, e.conversation);
                
                // Re-render the conversations list
                this.renderConversations();
                
                // Show notification
                this.showNotification(`New conversation: ${e.conversation.display_name || e.conversation.name || 'New Chat'}`);
            });

        // Listen for new messages in conversations we're part of
        this.conversations.forEach((conversation) => {
            this.joinConversationChannel(conversation.id);
        });
    }

    joinConversationChannel(conversationId) {
        if (typeof Echo === 'undefined') return;
        
        Echo.private(`conversation.${conversationId}`)
            .listen('message.sent', (e) => {
                console.log('New message received:', e.message);
                
                // Update conversation with new message
                const conversation = this.conversations.get(conversationId);
                if (conversation) {
                    conversation.last_message = e.message;
                    conversation.updated_at = e.message.created_at;
                    this.conversations.set(conversationId, conversation);
                    
                    // Re-render conversations to update last message
                    this.renderConversations();
                    
                    // If this is the current conversation, add message to chat
                    if (this.currentConversation && this.currentConversation.id === conversationId) {
                        this.addMessageToChat(e.message);
                    }
                    
                    // Show notification if not current conversation
                    if (!this.currentConversation || this.currentConversation.id !== conversationId) {
                        this.showNotification(`New message from ${e.message.user.name}`);
                    }
                }
            })
            .listen('user.typing', (e) => {
                // Handle typing indicators
                if (this.currentConversation && this.currentConversation.id === conversationId) {
                    this.showTypingIndicator(e.user, e.typing);
                }
            });
    }

    addMessageToChat(message) {
        const container = document.getElementById('messages-container');
        if (!container) return;
        
        const isOwn = message.user.id === this.currentUserId;
        const messageHtml = `
            <div class="message ${isOwn ? 'own-message' : 'other-message'} mb-3">
                <div class="d-flex ${isOwn ? 'justify-content-end' : 'justify-content-start'}">
                    <div class="message-content ${isOwn ? 'bg-primary text-white' : 'bg-light'} p-2 rounded" style="max-width: 70%;">
                        ${!isOwn ? `<div class="small text-muted mb-1">${message.user.name}</div>` : ''}
                        <div>${message.content}</div>
                        <div class="small ${isOwn ? 'text-white-50' : 'text-muted'} mt-1">
                            ${this.formatTime(message.created_at)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', messageHtml);
        this.scrollToBottom();
    }

    showTypingIndicator(user, isTyping) {
        const container = document.getElementById('typing-indicator');
        if (!container) return;
        
        if (isTyping && user.id !== this.currentUserId) {
            container.innerHTML = `<div class="text-muted small">${user.name} is typing...</div>`;
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    }

    requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                }
            });
        }
    }

    showNotification(message) {
        // Simple notification - you can enhance this with toast notifications
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Chat Notification', {
                body: message,
                icon: '/favicon.ico'
            });
        } else {
            // Show in-app notification instead
            this.showInAppNotification(message);
        }
    }

    showInAppNotification(message) {
        // Create a simple toast notification
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 9999;
            max-width: 300px;
            animation: slideInRight 0.3s ease-out;
        `;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // UI Rendering Methods
    renderConversations() {
        const container = document.getElementById('conversations-list');
        const conversations = Array.from(this.conversations.values())
            .sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));

        if (conversations.length === 0) {
            container.innerHTML = `
                <div class="text-center p-4 text-muted">
                    <i class="fas fa-comments fa-2x mb-2"></i>
                    <p>No conversations yet</p>
                    <button class="btn btn-primary btn-sm" onclick="showNewConversationModal()">
                        Start chatting
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = conversations.map(conv => `
            <div class="conversation-item ${this.currentConversation?.id === conv.id ? 'active' : ''}" 
                 onclick="chatApp.switchToConversation(${conv.id})" 
                 data-conversation-id="${conv.id}">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        ${conv.type === 'group' ? 
                            '<i class="fas fa-users text-primary"></i>' : 
                            '<i class="fas fa-user text-secondary"></i>'
                        }
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${conv.display_name || conv.name}</div>
                        <div class="text-muted small">
                            ${conv.last_message ? conv.last_message.content.substring(0, 50) + '...' : 'No messages yet'}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">${this.formatTime(conv.updated_at)}</div>
                        ${conv.unread_count > 0 ? `<span class="badge bg-primary">${conv.unread_count}</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    async switchToConversation(conversationId) {
        try {
            const data = await this.loadMessages(conversationId);
            if (!data) return;

            this.currentConversation = data.conversation;
            
            // Join conversation channel for real-time updates
            this.joinConversationChannel(conversationId);
            
            // Update UI
            this.renderConversationHeader();
            this.renderMessages(data.messages);
            this.showChatArea();
            this.renderConversations(); // Update active state
            
            // Mark as read
            try {
                await axios.post(`${this.apiUrl}/conversations/${conversationId}/read`);
            } catch (error) {
                console.warn('Could not mark conversation as read:', error);
            }
            
        } catch (error) {
            console.error('Error switching conversation:', error);
        }
    }

    renderConversationHeader() {
        if (!this.currentConversation) return;

        document.getElementById('chat-header').innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        ${this.currentConversation.type === 'group' ? 
                            '<i class="fas fa-users text-primary fa-lg"></i>' : 
                            '<i class="fas fa-user text-secondary fa-lg"></i>'
                        }
                    </div>
                    <div>
                        <h6 class="mb-0">${this.currentConversation.display_name || this.currentConversation.name}</h6>
                        <small class="text-muted">
                            ${this.currentConversation.participants.length} participants
                        </small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="window.open('/meet', '_blank')">
                        <i class="fas fa-video"></i> Video Call
                    </button>
                </div>
            </div>
        `;
    }

    renderMessages(messages) {
        const container = document.getElementById('messages-list');
        container.innerHTML = messages.map(message => this.renderMessage(message)).join('');
        this.scrollToBottom();
    }

    renderMessage(message) {
        const isOwn = message.user.id === this.currentUserId;
        const messageClass = isOwn ? 'message own' : 'message';
        
        if (message.type === 'video_call') {
            const metadata = message.metadata || {};
            return `
                <div class="video-call-message">
                    <i class="fas fa-video fa-2x text-success mb-2"></i>
                    <div class="fw-bold">${message.content}</div>
                    <div class="text-muted small">Started by ${metadata.started_by} • ${this.formatTime(message.created_at)}</div>
                    <div class="mt-2">
                        <a href="${metadata.meeting_url}" target="_blank" class="btn btn-success btn-sm">
                            <i class="fas fa-video"></i> Join Video Call
                        </a>
                    </div>
                </div>
            `;
        }

        return `
            <div class="${messageClass}">
                <div class="message-content">
                    ${message.content}
                </div>
                <div class="message-info">
                    ${isOwn ? 'You' : message.user.name} • ${this.formatTime(message.created_at)}
                </div>
            </div>
        `;
    }

    showChatArea() {
        document.getElementById('messages-container').style.display = 'block';
        document.getElementById('message-input-container').style.display = 'block';
    }

    // Event Handlers
    setupEventHandlers() {
        // Message form
        document.getElementById('message-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleSendMessage();
        });

        // Auto-resize textarea
        const messageInput = document.getElementById('message-input');
        messageInput.addEventListener('input', (e) => {
            e.target.style.height = 'auto';
            e.target.style.height = e.target.scrollHeight + 'px';
            
            // Typing indicator
            if (this.currentConversation) {
                this.sendTypingIndicator();
            }
        });

        // Video call button
        document.getElementById('video-call-btn').addEventListener('click', async () => {
            if (this.currentConversation) {
                // Simple redirect to video meeting page
                window.open('/meet', '_blank');
            }
        });

        // New conversation modal
        this.setupNewConversationModal();
        
        // Online users modal
        this.setupOnlineUsersModal();
    }

    async handleSendMessage() {
        const input = document.getElementById('message-input');
        const message = input.value.trim();
        
        if (!message || !this.currentConversation) return;

        const success = await this.sendMessage(this.currentConversation.id, message);
        if (success) {
            input.value = '';
            input.style.height = 'auto';
        }
    }

    sendTypingIndicator() {
        if (!this.currentConversation) return;

        if (!this.isTyping) {
            this.isTyping = true;
            axios.post(`${this.apiUrl}/conversations/${this.currentConversation.id}/typing`, {
                typing: true
            });
        }

        clearTimeout(this.typingTimeout);
        this.typingTimeout = setTimeout(() => {
            if (this.isTyping) {
                this.isTyping = false;
                axios.post(`${this.apiUrl}/conversations/${this.currentConversation.id}/typing`, {
                    typing: false
                });
            }
        }, 1000);
    }

    // Setup polling for updates (fallback for real-time)
    setupPolling() {
        // Poll for updates every 5 seconds
        this.pollInterval = setInterval(async () => {
            // Check for new conversations
            await this.checkForNewConversations();
            
            // Check for new messages in current conversation
            if (this.currentConversation) {
                await this.checkForNewMessages();
            }
            
            // Update online users
            await this.loadOnlineUsers();
        }, 5000);
    }

    async checkForNewConversations() {
        try {
            const response = await axios.get(`${this.apiUrl}/conversations`);
            if (response.data && response.data.success) {
                const newConversations = response.data.data.conversations;
                const currentCount = this.conversations.size;
                
                // Clear and repopulate conversations
                this.conversations.clear();
                newConversations.forEach(conv => {
                    this.conversations.set(conv.id, conv);
                });
                
                // If we have new conversations, re-render
                if (newConversations.length > currentCount) {
                    this.renderConversations();
                    
                    // Show notification for new conversation
                    if (newConversations.length > currentCount) {
                        const latestConv = newConversations[0]; // Assuming newest is first
                        this.showNotification(`New conversation: ${latestConv.display_name || latestConv.name || 'New Chat'}`);
                    }
                } else if (newConversations.length !== currentCount || this.hasConversationUpdates(newConversations)) {
                    // Re-render if count changed or conversations were updated
                    this.renderConversations();
                }
            }
        } catch (error) {
            console.error('Error checking for new conversations:', error);
        }
    }

    hasConversationUpdates(newConversations) {
        // Check if any conversation has been updated (last message, etc.)
        for (const conv of newConversations) {
            const existing = this.conversations.get(conv.id);
            if (existing && existing.updated_at !== conv.updated_at) {
                return true;
            }
        }
        return false;
    }

    async checkForNewMessages() {
        if (!this.currentConversation) return;
        
        try {
            const response = await axios.get(`${this.apiUrl}/conversations/${this.currentConversation.id}/messages`);
            if (response.data.success) {
                const messages = response.data.data.messages;
                const currentMessages = document.querySelectorAll('.message').length;
                
                if (messages.length > currentMessages) {
                    // New messages found, re-render
                    this.renderMessages(messages);
                }
            }
        } catch (error) {
            console.error('Error checking for new messages:', error);
        }
    }

    showTypingIndicator(user, typing) {
        const indicator = document.getElementById('typing-indicator');
        const text = document.getElementById('typing-text');
        
        if (typing) {
            text.textContent = `${user.name} is typing...`;
            indicator.style.display = 'block';
            this.scrollToBottom();
        } else {
            indicator.style.display = 'none';
        }
    }

    // New Conversation Modal
    setupNewConversationModal() {
        const modal = document.getElementById('newConversationModal');
        const form = document.getElementById('new-conversation-form');
        const typeRadios = form.querySelectorAll('input[name="type"]');
        const groupNameField = document.getElementById('groupNameField');
        const userSearch = document.getElementById('userSearch');
        const userSearchResults = document.getElementById('userSearchResults');
        const selectedUsersContainer = document.getElementById('selectedUsers');
        const createBtn = document.getElementById('createConversationBtn');

        // Toggle group name field
        typeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                groupNameField.style.display = radio.value === 'group' ? 'block' : 'none';
            });
        });

        // User search
        let searchTimeout;
        userSearch.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                userSearchResults.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(async () => {
                const users = await this.searchUsers(query);
                this.renderUserSearchResults(users);
            }, 300);
        });

        // Create conversation
        createBtn.addEventListener('click', async () => {
            const formData = new FormData(form);
            const type = formData.get('type');
            const name = formData.get('groupName');
            const participantIds = Array.from(this.selectedUsers);

            if (participantIds.length === 0) {
                alert('Please select at least one user');
                return;
            }

            const conversation = await this.createConversation(type, participantIds, name);
            if (conversation) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
                this.switchToConversation(conversation.id);
            }
        });

        // Reset modal on hide
        modal.addEventListener('hidden.bs.modal', () => {
            form.reset();
            this.selectedUsers.clear();
            this.renderSelectedUsers();
            userSearchResults.innerHTML = '';
            groupNameField.style.display = 'none';
        });
    }

    renderUserSearchResults(users) {
        const container = document.getElementById('userSearchResults');
        
        if (users.length === 0) {
            container.innerHTML = '<div class="text-muted small p-2">No users found</div>';
            return;
        }

        container.innerHTML = users.map(user => `
            <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-1">
                <div>
                    <div class="fw-bold">${user.name}</div>
                    <div class="text-muted small">${user.email}</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick="chatApp.toggleUserSelection(${user.id}, '${user.name}')"
                        ${this.selectedUsers.has(user.id) ? 'disabled' : ''}>
                    ${this.selectedUsers.has(user.id) ? 'Selected' : 'Select'}
                </button>
            </div>
        `).join('');
    }

    toggleUserSelection(userId, userName) {
        if (this.selectedUsers.has(userId)) {
            this.selectedUsers.delete(userId);
        } else {
            this.selectedUsers.add(userId);
        }
        
        // Store user name for display
        this.selectedUserNames = this.selectedUserNames || new Map();
        this.selectedUserNames.set(userId, userName);
        
        this.renderSelectedUsers();
        this.renderUserSearchResults([]); // Refresh search results
    }

    renderSelectedUsers() {
        const container = document.getElementById('selectedUsers');
        
        if (this.selectedUsers.size === 0) {
            container.innerHTML = '<div class="text-muted small">No users selected</div>';
            return;
        }

        container.innerHTML = Array.from(this.selectedUsers).map(userId => {
            const userName = this.selectedUserNames?.get(userId) || 'Unknown';
            return `
                <div class="user-tag">
                    ${userName}
                    <span class="remove-user" onclick="chatApp.toggleUserSelection(${userId}, '${userName}')">&times;</span>
                </div>
            `;
        }).join('');
    }

    // Online Users Modal
    setupOnlineUsersModal() {
        const btn = document.getElementById('onlineUsersBtn');
        const modal = document.getElementById('onlineUsersModal');
        
        btn.addEventListener('click', () => {
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            this.loadAndRenderOnlineUsers();
        });
    }

    async loadAndRenderOnlineUsers() {
        await this.loadOnlineUsers();
        const container = document.getElementById('onlineUsersList');
        
        if (this.onlineUsers.size === 0) {
            container.innerHTML = '<div class="text-center text-muted">No users online</div>';
            return;
        }

        // This would need to be enhanced to show actual user data
        container.innerHTML = `
            <div class="text-center">
                <div class="text-muted">${this.onlineUsers.size} users online</div>
            </div>
        `;
    }

    updateOnlineCount() {
        document.getElementById('online-count').textContent = this.onlineUsers.size;
    }

    // Utility methods
    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
        
        return date.toLocaleDateString();
    }

    scrollToBottom() {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    // Cleanup method
    destroy() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }
    }
}

// Global functions
function showNewConversationModal() {
    const modal = new bootstrap.Modal(document.getElementById('newConversationModal'));
    modal.show();
}

// Initialize the app
let chatApp;
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have the required elements
    if (document.getElementById('conversations-list')) {
        chatApp = new RealtimeChatApp();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (chatApp) {
        chatApp.destroy();
    }
});
</script>
@endpush