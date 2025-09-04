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
                    <h6 class="mb-0">
                        <i class="bi bi-chat-dots text-info"></i> 
                        {{ $conversation->display_name }}
                        <small class="text-muted">({{ $conversation->participants->count() }} participants)</small>
                    </h6>
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
                                    <div class="mt-1">{{ $message->content }}</div>
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
                    <form id="messageForm" class="d-flex">
                        @csrf
                        <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                        <input type="text" 
                               class="form-control me-2" 
                               name="content"
                               id="messageInput" 
                               placeholder="Type your message..."
                               required>
                        <button type="submit" class="btn" style="background:#17a2b8;color:#fff;">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
});
</script>
@endsection
