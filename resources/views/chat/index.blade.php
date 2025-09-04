@extends('layouts.app')

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
</div>

<div class="container py-4">
    <div class="row">
        <!-- Conversations List -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Conversations</h5>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    @forelse($conversations as $conversation)
                        <a href="{{ route('chat.conversation', $conversation) }}" class="text-decoration-none">
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
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-chat-dots display-4"></i>
                            <p>No conversations yet. Start a new chat!</p>
                        </div>
                    @endforelse
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
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.5rem;">
                            @foreach($users as $user)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="participants[]" value="{{ $user->id }}" id="user{{ $user->id }}">
                                    <label class="form-check-label" for="user{{ $user->id }}">
                                        {{ $user->name }} ({{ $user->email }})
                                    </label>
                                </div>
                            @endforeach
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

<script>
document.getElementById('conversationType').addEventListener('change', function() {
    const groupNameField = document.getElementById('groupNameField');
    if (this.value === 'group') {
        groupNameField.style.display = 'block';
        groupNameField.querySelector('input').required = true;
    } else {
        groupNameField.style.display = 'none';
        groupNameField.querySelector('input').required = false;
    }
});
</script>

<style>
.hover-bg-light:hover {
    background-color: #f8f9fa !important;
}
</style>
@endsection
