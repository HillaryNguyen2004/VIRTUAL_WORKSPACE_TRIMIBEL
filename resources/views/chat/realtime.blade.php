@extends('layout_dashboard')
@section('title', 'Tin nhắn')

@section('content')
<div class="flex w-full overflow-hidden h-[calc(100vh-4rem)] text-main relative transition-all">
    
    {{-- Pane 1: Conversation List --}}
    {{-- Mobile: w-full. Desktop (@4xl): ~28.5% width (matches col-span-2 of 7) --}}
    <div class="@container w-full @4xl:w-[28.57%] h-full overflow-hidden @4xl:border-r border-muted-200 flex flex-col p-auto shrink-0 bg-white z-auto">
        <div class="px-6 pt-8">
            <h2 class="font-bold text-3xl text-main tracking-tight">{{ __('real_time_chat.title') }}</h2>
        </div>

        <div class="flex flex-col @xs:flex-row px-6 pt-2 justify-between items-center gap-2">
            <button onclick="toggleModal('onlineUsersModal')" 
                class="group flex items-center justify-center gap-2 rounded-xl bg-white border border-muted-200 w-full px-auto py-1.5 text-muted-500 font-medium shadow-sm hover:border-accent  transition-all active:scale-95">
                <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-accent"></span>
                </span>
                <span>{{ __('real_time_chat.online') }} (<span id="online-count">0</span>)</span>
            </button>

            <button onclick="toggleModal('newConversationModal')"
                class="group flex items-center justify-center gap-2 rounded-xl bg-accent w-full px-auto py-1.5 text-white font-medium shadow-lg shadow-accent/20 transition-all hover:bg-accent-hover focus:ring-4 focus:ring-accent/30 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('real_time_chat.new_chat') }}
            </button>
        </div>
        
        {{-- Search Area --}}
        <div class="px-6 py-4 border-b border-muted-100">
            <div class="relative">
                <input type="text" id="conversationSearch" placeholder="{{ __('real_time_chat.search_placeholder') }}" 
                    class="block w-full bg-canvas border border-muted-200 text-main py-3 pl-10 pr-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all text-sm">
                <svg class="w-4 h-4 text-muted-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>

        {{-- List --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-1" id="conversations-list">
                {{-- Loading State --}}
                <div class="flex flex-col items-center justify-center h-40 text-muted-400">
                <svg class="animate-spin h-6 w-6 mb-2 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    </div>

    {{-- Pane 2: Chat Window --}}
    {{-- 
        Mobile Logic: fixed inset-0 (fullscreen), translate-x-full (hidden to right), z-20 (above list).
        Desktop Logic (@4xl): relative (normal flow), translate-x-0 (visible), flex-1 (takes remaining space), z-0.
    --}}
    <div id="chat-pane" class="absolute inset-0 z-5 bg-white transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col 
        @4xl:relative @4xl:translate-x-0 @4xl:flex-1 @4xl:inset-auto @4xl:z-0">
        
        {{-- Chat Header --}}
        <div class="h-20 px-6 py-4 border-b border-muted-200 flex justify-between items-center bg-white z-5" id="chat-header">
            <div class="flex items-center gap-3 text-muted-400">
                
                {{-- Back Button (Mobile Only) --}}
                <button onclick="chatApp.closeMobileChat()" class="mr-2 rounded-full hover:bg-muted-100 text-muted-500 transition-colors @4xl:hidden">
                    <i class="fas fa-chevron-left text-lg"></i>
                </button>

                <div class="w-10 h-10 rounded-full bg-muted-100 flex items-center justify-center">
                    <i class="far fa-comments text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-medium">{{ __('real_time_chat.select_conversation') }}</p>
                </div>
            </div>
        </div>

        {{-- Messages Area --}}
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar" id="messages-container" style="display: none;">
            <div id="messages-list" class="flex flex-col gap-4"></div>
            
            {{-- Typing Indicator --}}
            <div id="typing-indicator" class="hidden mt-4 pl-2">
                <div class="inline-flex items-center gap-2 bg-white border border-muted-200 px-3 py-1.5 rounded-full shadow-sm">
                    <span class="flex gap-1">
                        <span class="w-1.5 h-1.5 bg-muted-400 rounded-full animate-bounce"></span>
                        <span class="w-1.5 h-1.5 bg-muted-400 rounded-full animate-bounce [animation-delay:0.1s]"></span>
                        <span class="w-1.5 h-1.5 bg-muted-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    </span>
                    <span class="text-xs text-muted-500 font-medium">{{ __('real_time_chat.typing') }}</span>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="p-5 bg-white border-t border-muted-200" id="message-input-container" style="display: none;">
            
            {{-- File Preview (Styled like dashboard card) --}}
            <div id="file-preview" class="hidden mb-4 p-3 bg-canvas border border-muted-200 rounded-xl relative max-w-sm animate-fade-in-up">
                    <button onclick="clearFileSelection()" class="absolute -top-2 -right-2 bg-white text-muted-400 hover:text-danger border border-muted-200 rounded-full p-1 shadow-sm transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white rounded-lg border border-muted-200 text-primary" id="file-icon-wrapper">
                            <i id="file-icon" class="fas fa-file"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                        <div id="file-name" class="text-sm font-bold text-main truncate"></div>
                        <div id="file-size" class="text-xs text-muted-500"></div>
                        </div>
                    </div>
                    <img id="preview-img" class="hidden mt-3 h-24 rounded-lg border border-muted-200 object-cover w-full">
            </div>

            <form id="message-form" class="flex items-end gap-3">
                <div class="flex gap-1 pb-1">
                    <button type="button" onclick="document.getElementById('image-input').click()" class="p-2.5 text-muted-400 hover:text-primary hover:bg-primary/10 rounded-xl transition-colors" title="{{ __('real_time_chat.send_image') }}">
                        <i class="far fa-image text-xl"></i>
                    </button>
                    <button type="button" onclick="document.getElementById('file-input').click()" class="p-2.5 text-muted-400 hover:text-primary hover:bg-primary/10 rounded-xl transition-colors" title="{{ __('real_time_chat.attach_file') }}">
                        <i class="fas fa-paperclip text-xl"></i>
                    </button>
                </div>
                
                <input type="file" id="image-input" accept="image/*" class="hidden" onchange="handleFileSelectRealtime(this, 'image')">
                <input type="file" id="file-input" class="hidden" onchange="handleFileSelectRealtime(this, 'file')">

                <div class="flex-1 bg-canvas border border-muted-200 rounded-xl focus-within:bg-white focus-within:ring-2 focus-within:ring-accent/20 focus-within:border-accent transition-all">
                    <textarea 
                        id="message-input" 
                        rows="1" 
                        placeholder="Type a message..." 
                        class="w-full bg-transparent border-0 py-2 px-3 text-main placeholder-muted-400 outline-none focus:outline-none resize-none max-h-32 leading-relaxed"
                    ></textarea>
                </div>

                <button type="submit" id="send-btn" class="mb-0.5 p-3.5 bg-primary hover:bg-primary-hover text-white rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
        </div>
        
        {{-- Empty State (Matches Dashboard Empty States) --}}
        <div id="empty-state" class="absolute inset-0 flex items-center justify-center  z-0 pointer-events-none">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-canvas text-muted-300 mb-4">
                        <i class="far fa-comments text-3xl"></i>
                    </div>
                    <h3 class="text-main font-bold text-lg">{{ __('real_time_chat.title') }}</h3>
                    <p class="text-muted-500 text-sm mt-1">{{ __('real_time_chat.start_conversation') }}</p>
                </div>
        </div>
    </div>
</div>

{{-- New Conversation Modal --}}
<div id="newConversationModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="toggleModal('newConversationModal')"></div>
    
    {{-- Modal Container --}}
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="flex flex-col bg-white w-[95%] md:w-[600px] max-h-[85vh] rounded-2xl shadow-2xl animate-fade-in-up overflow-hidden">
            
            {{-- Header --}}
            <div class="flex items-center justify-between px-8 py-5 border-b border-muted-200 bg-white z-20 shrink-0">
                <p class="text-xl font-bold text-main">{{ __('real_time_chat.new_chat') }}</p>
                <button onclick="toggleModal('newConversationModal')" class="p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="new-conversation-form" class="flex flex-col flex-1 min-h-0">
                
                {{-- Toolbar (User Search) --}}
                <div class="px-8 py-3 bg-muted-50/50 border-b border-muted-200 flex flex-col gap-3 shrink-0">
                    {{-- Chat Type Selection --}}
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center justify-center gap-2 p-2 rounded-lg border border-muted-200 bg-white cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-all has-[:checked]:text-primary has-[:checked]:bg-primary/5 has-[:checked]:outline-none has-[:checked]:ring-2 has-[:checked]:ring-primary/20 has-[:checked]:border-primary">
                            <input type="radio" name="type" value="direct" checked class="hidden"> 
                            <span class="text-sm font-bold"><i class="far fa-user mr-1"></i> {{ __('real_time_chat.direct') }}</span>
                        </label>
                        <label class="flex items-center justify-center gap-2 p-2 rounded-lg border border-muted-300 bg-white cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-all has-[:checked]:text-primary has-[:checked]:bg-primary/5 has-[:checked]:outline-none has-[:checked]:ring-2 has-[:checked]:ring-primary/20 has-[:checked]:border-primary">
                            <input type="radio" name="type" value="group" class="hidden"> 
                            <span class="text-sm font-bold"><i class="fas fa-users mr-1"></i> {{ __('real_time_chat.group') }}</span>
                        </label>
                    </div>

                    {{-- Group Name Input (Conditional) --}}
                    <div id="groupNameField" class="hidden animate-fade-in-up">
                        <input type="text" id="groupName" placeholder="{{ __('real_time_chat.enter_group_name') }}" 
                            class="block w-full px-3 py-2 border border-muted-300 rounded-lg leading-5 bg-white placeholder-muted-400 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm transition-shadow">
                    </div>

                    {{-- Search Input (Styled like reference) --}}
                    <div class="relative w-full group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-muted-400 group-focus-within:text-primary transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" id="userSearch" 
                            class="block w-full pl-10 pr-3 py-2 border border-muted-300 rounded-lg leading-5 bg-white placeholder-muted-400 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm transition-shadow" 
                            placeholder="{{ __('real_time_chat.select_users') }}">
                    </div>
                </div>

                {{-- Scrollable Content Area --}}
                <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                    {{-- Selected Users Badge Area --}}
                    <div id="selectedUsers" class="flex flex-wrap gap-2 mb-2 min-h-[0px] transition-all"></div>
                    
                    {{-- Search Results --}}
                    <div id="userSearchResults" class="space-y-1">
                        <div class="text-center text-muted-400 text-sm py-8 italic">{{ __('real_time_chat.type_to_search') }}</div>
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="px-8 py-4 border-t flex justify-end gap-3 shrink-0">
                    <button type="button" onclick="toggleModal('newConversationModal')" class="px-4 py-2 rounded-lg text-sm font-bold text-muted-500 hover:text-main hover:bg-muted-200 transition-colors">
                        {{ __('real_time_chat.cancel') }}
                    </button>
                    <button type="button" id="createConversationBtn" class="px-6 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-sm font-bold shadow-lg shadow-primary/20 transition-all active:scale-95">
                        {{ __('real_time_chat.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Online Users Modal --}}
<div id="onlineUsersModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="toggleModal('onlineUsersModal')"></div>
    
    {{-- Modal Container --}}
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="flex flex-col bg-white w-[95%] md:w-[600px] h-[60vh] rounded-2xl shadow-2xl animate-fade-in-up overflow-hidden">
            
            {{-- Header --}}
            <div class="flex items-center justify-between px-8 py-5 border-b border-muted-200 bg-white z-20 shrink-0">
                <div class="flex items-center gap-4">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-accent"></span>
                    </span>
                    <p class="text-xl font-bold text-main">{{ __('real_time_chat.online_users') }}</p>
                </div>
                <button onclick="toggleModal('onlineUsersModal')" class="p-2 rounded-full text-muted-400 hover:text-primary hover:bg-muted-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Toolbar (Optional Search for Online Users could go here, currently just spacing) --}}
            <div class="px-8 py-3 bg-muted-50/50 border-b border-muted-200 text-xs font-bold text-muted-400 uppercase tracking-wider">
                Active Now
            </div>

            {{-- List --}}
            <div class="flex-1 overflow-y-auto custom-scrollbar p-0">
                <div id="onlineUsersList" class="divide-y divide-muted-100">
                    <div class="min-h-[100px] flex items-center justify-center text-muted-400 text-sm">{{ __('real_time_chat.loading') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Dashboard-style Scrollbar */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>
@endpush

@push('scripts')

<script>
function toggleModal(modalID) {
    const modal = document.getElementById(modalID);
    modal.classList.toggle('hidden');
}

window.currentUserId = {{ auth()->id() }};

class RealtimeChatApp {
    constructor() {
        this.apiUrl = '/api/chat';
        this.currentConversation = null;
        this.conversations = new Map();
        this.onlineUsers = new Set();
        this.selectedUsers = new Set();
        this.currentUserId = window.currentUserId;
        this.init();
    }

    async init() {
        // Setup Axios
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.withCredentials = true;

        // Ensure Sanctum CSRF cookie is set for authenticated API requests
        try {
            await axios.get('/sanctum/csrf-cookie');
        } catch (err) {
            console.warn('Failed to fetch CSRF cookie for Sanctum', err);
        }

        await this.loadConversations();
        await this.loadOnlineUsers();
        this.setupPolling();
        this.setupEventHandlers();
        this.setupRealtimeListeners();
        
        // Modal logic for radio buttons
        document.querySelectorAll('input[name="type"]').forEach(r => {
            r.addEventListener('change', (e) => {
                // Toggle Group Name Input
                document.getElementById('groupNameField').classList.toggle('hidden', e.target.value !== 'group');
                
                // UX FIX: Clear selected users when switching modes
                // This prevents confusion (e.g., having 5 users selected then switching to Direct)
                this.selectedUsers.clear();
                if(this.selectedUserNames) this.selectedUserNames.clear();
                this.renderSelectedUsers();
                
                // Re-trigger search render to remove checkmarks from the list
                const input = document.getElementById('userSearch');
                if (input.value.length >= 2) input.dispatchEvent(new Event('input'));
            });
        });
    }

    // --- RENDER METHODS (Updated with Dashboard Tokens) ---

    renderConversations() {
        const container = document.getElementById('conversations-list');
        if (!container) return;
        
        const conversations = Array.from(this.conversations.values())
            .sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));

        if (conversations.length === 0) {
            container.innerHTML = `<div class="p-4 text-center text-muted-400 text-sm">No conversations found.</div>`;
            return;
        }

        container.innerHTML = conversations.map(conv => {
            let displayName = conv.display_name || conv.name;
            if (!displayName && conv.type === 'direct') {
                const other = conv.participants?.find(p => p.id !== this.currentUserId);
                displayName = other ? other.name : 'Unknown User';
            } else if (!displayName) {
                displayName = conv.type === 'group' ? 'Group Chat' : 'Conversation';
            }
            
            const isActive = this.currentConversation?.id === conv.id;
            // Theme colors: Active uses Primary/Accent mix
            const bgClass = isActive ? 'bg-primary/5 border-primary/20' : 'bg-transparent border-transparent hover:bg-muted-100';
            const avatarBg = isActive ? 'bg-primary text-white shadow-primary/20' : 'bg-muted-100 text-muted-500';
            const textClass = isActive ? 'text-main' : 'text-main';
            const subTextClass = isActive ? 'text-primary' : 'text-muted-500';
            
            const lastMsg = conv.last_message ? conv.last_message.content : 'No messages yet';
            const time = this.formatTimeShort(conv.updated_at);

            return `
                <div class="group flex items-center gap-3 p-3 rounded-xl border transition-all cursor-pointer ${bgClass}" 
                     onclick="chatApp.switchToConversation(${conv.id})">
                    <div class="h-10 w-10 shrink-0 rounded-full ${avatarBg} flex items-center justify-center font-bold text-sm shadow-sm transition-colors">
                        ${displayName.charAt(0).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline">
                            <h4 class="text-sm font-bold ${textClass} truncate">${displayName}</h4>
                            <span class="text-[10px] text-muted-400 font-medium ml-2 whitespace-nowrap">${time}</span>
                        </div>
                        <div class="flex justify-between items-center mt-0.5">
                            <p class="text-xs ${subTextClass} truncate pr-2 flex-1 opacity-90">
                                ${conv.last_message && conv.last_message.user.id === this.currentUserId ? '<span class="opacity-70">You:</span> ' : ''}${lastMsg}
                            </p>
                            ${conv.unread_count > 0 ? `<span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-accent text-white text-[10px] font-bold shadow-sm shadow-accent/20">${conv.unread_count}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    renderConversationHeader() {
        if (!this.currentConversation) return;

        let displayName = this.currentConversation.display_name || this.currentConversation.name;
        if (!displayName && this.currentConversation.type === 'direct') {
            const other = this.currentConversation.participants?.find(p => p.id !== this.currentUserId);
            displayName = other ? other.name : 'Unknown User';
        } else if (!displayName) {
            displayName = this.currentConversation.type === 'group' ? 'Group Chat' : 'Conversation';
        }

        const headerHtml = `
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm border border-primary/20">
                    ${displayName.charAt(0).toUpperCase()}
                </div>
                <div>
                    <h3 class="font-bold text-main text-sm leading-tight">${displayName}</h3>
                    <p class="text-xs text-accent flex items-center gap-1.5 font-medium">
                        <span class="relative flex h-1.5 w-1.5">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-accent"></span>
                        </span> 
                        Active now
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="p-2 text-muted-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors"><i class="fas fa-phone-alt"></i></button>
                <button class="p-2 text-muted-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors"><i class="fas fa-video"></i></button>
                <button class="p-2 text-muted-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors"><i class="fas fa-info-circle"></i></button>
            </div>
        `;
        document.getElementById('chat-header').innerHTML = headerHtml;
    }

    renderMessage(message) {
        const isOwn = message.user.id === this.currentUserId;
        
        let contentHtml = message.content;
        if (message.type === 'image' && message.file_path) {
            contentHtml = `
                <div class="rounded-lg overflow-hidden border border-white/20 mb-1 bg-black/5">
                    <img src="/storage/${message.file_path}" class="max-h-64 w-auto object-cover cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open(this.src)">
                </div>
                ${message.content ? `<div class="mt-1">${message.content}</div>` : ''}
            `;
        } else if (message.type === 'file') {
            contentHtml = `
                <div class="flex items-center gap-3 p-3 bg-black/5 rounded-xl border border-black/5 hover:bg-black/10 transition-colors cursor-pointer" onclick="window.location.href='/storage/${message.file_path}'">
                    <div class="h-8 w-8 bg-white rounded-lg flex items-center justify-center text-primary shadow-sm"><i class="fas fa-file-alt"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold truncate max-w-[150px]">${message.file_name}</div>
                        <div class="text-[10px] opacity-70">${this.formatFileSize(message.file_size)}</div>
                    </div>
                    <div class="opacity-70"><i class="fas fa-download"></i></div>
                </div>
                ${message.content ? `<div class="mt-2 text-sm">${message.content}</div>` : ''}
            `;
        }

        const containerClass = isOwn ? 'justify-end' : 'justify-start';
        
        // Dashboard Theme Bubbles
        // Own: Primary Color (Purple), Text White, Shadow
        // Other: White, Text Main, Border Muted
        const bubbleClass = isOwn 
            ? 'bg-primary text-white rounded-2xl rounded-tr-sm shadow-lg shadow-primary/20' 
            : 'bg-white border border-muted-200 text-main rounded-2xl rounded-tl-sm shadow-sm';

        return `
            <div class="flex ${containerClass} mb-4 group animate-fade-in-up">
                ${!isOwn ? `
                <div class="h-8 w-8 rounded-full bg-muted-100 text-muted-500 border border-muted-200 flex items-center justify-center text-xs font-bold mr-2 self-end mb-1 shrink-0">
                    ${message.user.name.charAt(0)}
                </div>` : ''}
                
                <div class="max-w-[70%] flex flex-col ${isOwn ? 'items-end' : 'items-start'}">
                    ${!isOwn ? `<div class="text-[10px] text-muted-400 ml-1 mb-1 font-medium">${message.user.name}</div>` : ''}
                    <div class="px-5 py-3 ${bubbleClass} text-sm leading-relaxed break-words">
                        ${contentHtml}
                    </div>
                    <div class="text-[10px] text-muted-300 mt-1 ${isOwn ? 'mr-1' : 'ml-1'} font-medium">
                        ${this.formatTime(message.created_at)}
                    </div>
                </div>
            </div>
        `;
    }

    renderUserSearchResults(users) {
        const container = document.getElementById('userSearchResults');
        if (users.length === 0) {
            container.innerHTML = '<div class="text-muted-400 text-xs p-2 text-center italic">No users found</div>';
            return;
        }

        container.innerHTML = users.map(user => `
            <div class="flex justify-between items-center p-2 rounded-lg hover:bg-canvas cursor-pointer transition-colors group" 
                onclick="chatApp.toggleUserSelection(${user.id}, '${user.name}')">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full bg-muted-200 text-muted-600 flex items-center justify-center font-bold text-xs group-hover:shadow-sm">
                        ${user.name.charAt(0)}
                    </div>
                    <div>
                        <p class="text-sm font-bold text-main">${user.name}</p>
                        <p class="text-xs text-muted-400">${user.email}</p>
                    </div>
                </div>
                <div class="${this.selectedUsers.has(user.id) ? 'text-primary' : 'text-muted-300'}">
                    <i class="fa ${this.selectedUsers.has(user.id) ? 'fa-check-circle' : 'fa-circle-o'}"></i>
                </div>
            </div>
        `).join('');
    }

    renderSelectedUsers() {
        const container = document.getElementById('selectedUsers');
        if (this.selectedUsers.size === 0) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = Array.from(this.selectedUsers).map(userId => {
            const userName = this.selectedUserNames?.get(userId) || 'User';
            return `
                <div class="inline-flex items-center gap-1.5 bg-primary/10 text-primary border border-primary/20 px-2.5 py-1 rounded-lg text-xs font-bold shadow-sm">
                    ${userName}
                    <button type="button" class="hover:text-danger focus:outline-none" onclick="chatApp.toggleUserSelection(${userId}, '${userName}')">&times;</button>
                </div>
            `;
        }).join('');
    }

    // --- LOGIC METHODS (Unchanged but ensuring connectivity) ---
    async loadConversations() {
        try {
            const res = await axios.get(`${this.apiUrl}/conversations`);
            if (res.data.success) {
                this.conversations.clear();
                res.data.data.conversations.forEach(c => this.conversations.set(c.id, c));
                this.renderConversations();
            }
        } catch (e) { console.error(e); }
    }

    async switchToConversation(id) {
        try {
            const res = await axios.get(`${this.apiUrl}/conversations/${id}`);
            if (res.data.success) {
                this.currentConversation = res.data.data.conversation;
                this.renderConversationHeader();
                document.getElementById('messages-list').innerHTML = res.data.data.messages.map(m => this.renderMessage(m)).join('');
                document.getElementById('empty-state').classList.add('hidden');
                document.getElementById('messages-container').style.display = 'block';
                document.getElementById('message-input-container').style.display = 'block';
                this.scrollToBottom();
                this.renderConversations();
                
                // NEW: Slide the chat pane in on mobile
                this.openMobileChat();
                // subscribe to realtime updates for this conversation
                if (this.subscribeToConversation) this.subscribeToConversation(id);
            }
        } catch (e) { console.error(e); }
    }

    // NEW METHODS for Mobile Responsiveness
    openMobileChat() {
        const pane = document.getElementById('chat-pane');
        pane.classList.remove('translate-x-full'); // Slide in
        pane.classList.add('translate-x-0');
    }

    closeMobileChat() {
        const pane = document.getElementById('chat-pane');
        pane.classList.remove('translate-x-0');
        pane.classList.add('translate-x-full'); // Slide out
    }

    // UPDATED: Added the Back Button rendering
    renderConversationHeader() {
        if (!this.currentConversation) return;

        let displayName = this.currentConversation.display_name || this.currentConversation.name;
        if (!displayName && this.currentConversation.type === 'direct') {
            const other = this.currentConversation.participants?.find(p => p.id !== this.currentUserId);
            displayName = other ? other.name : 'Unknown User';
        } else if (!displayName) {
            displayName = this.currentConversation.type === 'group' ? 'Group Chat' : 'Conversation';
        }

        const headerHtml = `
            <div class="flex items-center gap-3">
                
                <button onclick="chatApp.closeMobileChat()" class="mr-1 p-2 rounded-full hover:bg-muted-100 text-muted-500 transition-colors @4xl:hidden">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="h-10 w-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm border border-primary/20">
                    ${displayName.charAt(0).toUpperCase()}
                </div>
                <div>
                    <h3 class="font-bold text-main text-sm leading-tight">${displayName}</h3>
                    <p class="text-xs text-accent flex items-center gap-1.5 font-medium">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-accent"></span>
                        </span> 
                        Active now
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="p-2 text-muted-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors"><i class="fas fa-phone-alt"></i></button>
                <button class="p-2 text-muted-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors"><i class="fas fa-video"></i></button>
                <button class="p-2 text-muted-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors"><i class="fas fa-info-circle"></i></button>
            </div>
        `;
        document.getElementById('chat-header').innerHTML = headerHtml;
    }

    async sendMessage(id, content, type='text') {
        try {
            const res = await axios.post(`${this.apiUrl}/conversations/${id}/messages`, { content, type });
            if (res.data.success) {
                const msg = res.data.data.message;
                const conv = this.conversations.get(id);
                if(conv) { conv.last_message = msg; conv.updated_at = msg.created_at; this.conversations.set(id, conv); }
                this.renderConversations();
                if(this.currentConversation && this.currentConversation.id === id) {
                    const list = document.getElementById('messages-list');
                    list.insertAdjacentHTML('beforeend', this.renderMessage(msg));
                    this.scrollToBottom();
                }
                return true;
            }
        } catch(e) { console.error(e); return false; }
    }

    async searchUsers(query) {
        try {
            const res = await axios.get(`${this.apiUrl}/users/search?query=${encodeURIComponent(query)}`);
            return res.data.success ? res.data.data.users : [];
        } catch { return []; }
    }

    toggleUserSelection(userId, userName) {
        // 1. Check current chat type
        const type = document.querySelector('input[name="type"]:checked').value;
        this.selectedUserNames = this.selectedUserNames || new Map();

        if (this.selectedUsers.has(userId)) {
            // CASE: Deselecting a user (always allowed)
            this.selectedUsers.delete(userId);
            this.selectedUserNames.delete(userId);
        } else {
            // CASE: Selecting a new user
            if (type === 'direct') {
                // If Direct mode, clear ALL previous selections first
                this.selectedUsers.clear();
                this.selectedUserNames.clear();
            }
            
            // Add the new user
            this.selectedUsers.add(userId);
            this.selectedUserNames.set(userId, userName);
        }

        // 2. Render the badge list
        this.renderSelectedUsers();

        // 3. Force re-render of search results to update the checkmark icons visually
        // (We trigger the input event to reuse the existing search/render logic)
        const input = document.getElementById('userSearch');
        if (input.value.length >= 2) {
            input.dispatchEvent(new Event('input'));
        }
    }

    async createConversation(type, participantIds, name = null) {
        try {
            const res = await axios.post(`${this.apiUrl}/conversations`, { type, participant_ids: participantIds, name });
            if (res.data.success) {
                const conv = res.data.data.conversation;
                this.conversations.set(conv.id, conv);
                this.renderConversations();
                return conv;
            }
        } catch (e) { console.error(e); return null; }
    }

    formatTimeShort(dateStr) {
        return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    formatTime(dateStr) { return this.formatTimeShort(dateStr); }
    formatFileSize(bytes) { return (bytes/1024).toFixed(1) + ' KB'; }
    scrollToBottom() { const c = document.getElementById('messages-container'); c.scrollTop = c.scrollHeight; }

    setupEventHandlers() {
        document.getElementById('message-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('message-input');
            const val = input.value.trim();
            if (window.selectedFile) return this.sendFileMessage(); 
            if(!val || !this.currentConversation) return;
            if(await this.sendMessage(this.currentConversation.id, val)) input.value = '';
        });
        
        const ta = document.getElementById('message-input');
        ta.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        ta.addEventListener('keydown', (e) => {
            // If Enter is pressed WITHOUT Shift
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault(); // Prevent creating a new line
                
                // Programmatically trigger the form submission
                // We use requestSubmit() so it triggers the 'submit' event listener above
                document.getElementById('message-form').requestSubmit();
                
                // Reset height after sending
                ta.style.height = 'auto'; 
            }
        });

        let searchTimeout;
        document.getElementById('userSearch').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            if(query.length < 2) {
                document.getElementById('userSearchResults').innerHTML = '';
                return;
            }
            searchTimeout = setTimeout(async () => {
                const users = await this.searchUsers(query);
                this.renderUserSearchResults(users);
            }, 300);
        });

        document.getElementById('createConversationBtn').addEventListener('click', async () => {
            const type = document.querySelector('input[name="type"]:checked').value;
            const name = document.getElementById('groupName').value;
            const ids = Array.from(this.selectedUsers);
            if(ids.length === 0) return alert('Please select at least one user.');
            
            const conv = await this.createConversation(type, ids, name);
            if(conv) {
                toggleModal('newConversationModal');
                this.switchToConversation(conv.id);
                document.getElementById('new-conversation-form').reset();
                this.selectedUsers.clear();
                this.renderSelectedUsers();
                document.getElementById('userSearchResults').innerHTML = '';
            }
        });
    }
    
    setupPolling() {
        setInterval(() => { this.loadConversations(); this.loadOnlineUsers(); }, 6000);
    }
    
    async sendFileMessage() {
         if(!this.currentConversation || !window.selectedFile) return;
         const formData = new FormData();
         formData.append('content', document.getElementById('message-input').value.trim());
         formData.append(window.selectedFileType === 'image' ? 'image' : 'file', window.selectedFile);
         
         const sendBtn = document.getElementById('send-btn');
         const originalIcon = sendBtn.innerHTML;
         sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
         sendBtn.disabled = true;

         try {
             const ep = `${this.apiUrl}/conversations/${this.currentConversation.id}/${window.selectedFileType === 'image' ? 'images' : 'files'}`;
             const res = await axios.post(ep, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
             if(res.data.success) {
                 clearFileSelection();
                 this.switchToConversation(this.currentConversation.id);
             }
         } catch(e) { console.error(e); } 
         finally {
             sendBtn.innerHTML = originalIcon;
             sendBtn.disabled = false;
         }
    }
    
    async loadOnlineUsers() { 
        // Implement loading online users here
    }

    // --- REALTIME (Laravel Echo / Pusher) ---
    setupRealtimeListeners() {
        // expose server-side env to client for Pusher/Echo init
        window.PUSHER = {
            key: "{{ env('VITE_PUSHER_APP_KEY') }}",
            host: "{{ env('VITE_PUSHER_HOST') }}",
            port: "{{ env('VITE_PUSHER_PORT') }}",
            scheme: "{{ env('VITE_PUSHER_SCHEME') }}",
            cluster: "{{ env('VITE_PUSHER_APP_CLUSTER') }}"
        };

        // Use Echo instance provided by bundled JS (resources/js/bootstrap.js)
        // window.Echo is initialized in the app bundle using import.meta.env VITE_PUSHER_* vars
        if (!window.Echo) {
            console.warn('Laravel Echo not initialized in JS bundle. Ensure `resources/js/bootstrap.js` imports Echo and is built.');
        }
    }

    subscribeToConversation(conversationId) {
        if (!window.Echo || !conversationId) return;

        // unsubscribe previous if exists
        if (this._currentEchoChannel && this._currentEchoChannelName === `conversation.${conversationId}`) return;
        if (this._currentEchoChannel) {
            try { this._currentEchoChannel.leave(); } catch(e){}
            this._currentEchoChannel = null;
            this._currentEchoChannelName = null;
        }

        const channelName = `private-conversation.${conversationId}`; // Laravel Echo private channel naming
        try {
            this._currentEchoChannel = window.Echo.private(`conversation.${conversationId}`);
            this._currentEchoChannelName = `conversation.${conversationId}`;

            this._currentEchoChannel.listen('.message.sent', (e) => {
                // Append incoming message if it's for the current conversation
                if (!this.currentConversation || this.currentConversation.id !== e.conversation_id) return;
                const msg = e.message;
                const list = document.getElementById('messages-list');
                list.insertAdjacentHTML('beforeend', this.renderMessage(msg));
                this.scrollToBottom();

                // update conv list
                const conv = this.conversations.get(e.conversation_id);
                if (conv) { conv.last_message = msg; conv.updated_at = msg.created_at; this.conversations.set(conv.id, conv); this.renderConversations(); }
            });

            this._currentEchoChannel.listen('.user.typing', (e) => {
                // optional: handle typing indicator
            });
        } catch (err) {
            console.warn('Echo subscribe failed', err);
        }
    }

    unsubscribeFromConversation(conversationId) {
        if (!this._currentEchoChannel) return;
        try { this._currentEchoChannel.leave(); } catch(e){}
        this._currentEchoChannel = null;
        this._currentEchoChannelName = null;
    }
}

window.selectedFile = null;
window.selectedFileType = null;
function handleFileSelectRealtime(input, type) {
    const file = input.files[0];
    if(!file) return;
    window.selectedFile = file;
    window.selectedFileType = type;
    
    document.getElementById('file-preview').classList.remove('hidden');
    document.getElementById('file-icon').className = type === 'image' ? 'far fa-image' : 'fas fa-file-alt';
    document.getElementById('file-name').textContent = file.name;
    document.getElementById('file-size').textContent = (file.size/1024).toFixed(1) + ' KB';
    
    if(type === 'image') {
        const r = new FileReader();
        r.onload = e => { 
            document.getElementById('preview-img').src = e.target.result; 
            document.getElementById('preview-img').classList.remove('hidden'); 
        };
        r.readAsDataURL(file);
    } else {
        document.getElementById('preview-img').classList.add('hidden');
    }
}
function clearFileSelection() {
    window.selectedFile = null;
    document.getElementById('file-preview').classList.add('hidden');
    document.getElementById('image-input').value = '';
    document.getElementById('file-input').value = '';
}

let chatApp;
document.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById('conversations-list')) chatApp = new RealtimeChatApp();
});
</script>
@endpush