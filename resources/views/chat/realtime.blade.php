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
            <div class="flex gap-2 w-full">
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
        </div>
        
        {{-- Search Area --}}
        <div class="px-6 py-4 border-b border-muted-100">
            <div class="relative">
                <input type="text" id="conversationSearch" placeholder="{{ __('real_time_chat.search_placeholder') }}" 
                    class="block w-full bg-canvas border border-muted-200 text-main py-3 pl-10 pr-4 rounded-xl placeholder-muted-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent transition-all text-sm">
                <svg class="w-4 h-4 text-muted-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>

        {{-- List: Messages (DMs) + Channels (Discord-like) --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-3">
            {{-- Messages Section (top) --}}
            <div id="dm-section" class="space-y-2">
                <div class="flex items-center justify-between px-2">
                    <div class="font-medium text-sm">Messages</div>
                    <button class="text-xs text-muted-400 hover:text-primary" onclick="document.getElementById('dm-list').classList.toggle('hidden')">Hide</button>
                </div>
                <div id="dm-list" class="space-y-1">
                    <div class="flex flex-col items-center justify-center h-24 text-muted-400">
                        <svg class="animate-spin h-6 w-6 mb-2 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Channels Section (bottom) --}}
            <div id="channels-section" class="mt-2 space-y-2">
                <div class="flex items-center justify-between px-2">
                    <div class="font-medium text-sm">Channels</div>
                    <div class="flex items-center gap-2">
                        <button class="text-xs px-2 py-1 rounded border text-muted-500" onclick="chatApp.loadChannels()">Refresh</button>
                        <button class="text-xs px-2 py-1 rounded bg-primary text-white" onclick="toggleModal('newChannelModal')">New</button>
                    </div>
                </div>
                <div id="channels-list" class="space-y-1">
                    <div class="flex flex-col items-center justify-center h-24 text-muted-400">
                        <svg class="animate-spin h-6 w-6 mb-2 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
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
                    <div id="mention-dropdown" class="hidden absolute bg-white border border-muted-200 rounded-md shadow-lg z-50 mt-1 max-h-56 overflow-auto" style="min-width:220px;"></div>
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

<!-- New Channel Modal (rendered inline so it's always present) -->
<div id="newChannelModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="toggleModal('newChannelModal')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white w-[95%] md:w-[600px] rounded-2xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Create Channel</h3>
                <button onclick="toggleModal('newChannelModal')" class="p-2 rounded-full">&times;</button>
            </div>
            <form id="newChannelForm" class="space-y-3">
                <div>
                    <label class="text-sm font-medium">Name</label>
                    <input id="channelName" class="w-full border px-3 py-2 rounded-lg" required />
                </div>
                <div>
                    <label class="text-sm font-medium">Description</label>
                    <textarea id="channelDescription" class="w-full border px-3 py-2 rounded-lg"></textarea>
                </div>
                @if(auth()->user() && auth()->user()->hasRole('admin'))
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="adminOnly" /> <label for="adminOnly">Admin-only (only admins can post)</label>
                </div>
                @endif
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="channelPrivate" /> <label for="channelPrivate">Private</label>
                </div>
                <div class="text-right">
                    <button type="button" onclick="toggleModal('newChannelModal')" class="px-4 py-2 mr-2">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Channel Details Modal (rendered inline) -->
<div id="channelDetailsModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="toggleModal('channelDetailsModal')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white w-[95%] md:w-[800px] rounded-2xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 id="channelDetailsTitle" class="text-lg font-bold">Channel</h3>
                <button onclick="toggleModal('channelDetailsModal')" class="p-2 rounded-full">&times;</button>
            </div>
            <div id="channelDetailsContent">
                <p id="channelDetailsDescription" class="text-sm text-muted mb-4"></p>
                <h4 class="font-medium">Members</h4>
                <div id="channelMembersList" class="mt-2 space-y-2"></div>
                <div id="channelRulesSection">
                    <h4 class="font-medium mt-4">Rules</h4>
                    <div id="channelRulesList" class="mt-2 space-y-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Members Modal -->
<div id="addMembersModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="toggleModal('addMembersModal')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white w-[95%] md:w-[640px] rounded-2xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Add Members</h3>
                <button onclick="toggleModal('addMembersModal')" class="p-2 rounded-full">&times;</button>
            </div>

            <div class="mb-3">
                <input id="addMembersSearch" class="w-full border px-3 py-2 rounded-lg" placeholder="Search users to add..." oninput="(function(e){ clearTimeout(window._addMembersSearchTimer); window._addMembersSearchTimer=setTimeout(()=>chatApp.handleAddMembersSearch(e.target.value),250); })(event)">
            </div>

            <div id="addMembersResults" class="max-h-64 overflow-auto mb-3 border rounded"></div>
            <div id="addMembersSelected" class="mb-3"></div>

            <div class="text-right">
                <button type="button" onclick="toggleModal('addMembersModal')" class="px-4 py-2 mr-2">Cancel</button>
                <button type="button" onclick="chatApp.submitAddMembers()" class="px-4 py-2 bg-primary text-white rounded-lg">Add selected</button>
            </div>
        </div>
    </div>
</div>

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
    if (!modal) {
        console.warn('toggleModal: element not found', modalID);
        return;
    }
    modal.classList.toggle('hidden');
}

window.currentUserId = {{ auth()->id() }};
window.currentUserIsAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};

class RealtimeChatApp {
    constructor() {
        this.apiUrl = '/api/chat';
        this.currentConversation = null;
        this.conversations = new Map();
        this.onlineUsers = new Set();
        this.selectedUsers = new Set();
        this.showingChannels = false;
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
        await this.loadChannels();
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
        const container = document.getElementById('dm-list');
        if (!container) return;

        const conversations = Array.from(this.conversations.values())
            .sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));

        if (conversations.length === 0) {
            container.innerHTML = `<div class="p-2 text-center text-muted-400 text-sm">No conversations found.</div>`;
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
        if (this.currentConversation.type === 'channel') {
            // channel header
            displayName = displayName || 'Channel';
        } else if (!displayName && this.currentConversation.type === 'direct') {
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
                <button class="p-2 text-muted-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors" onclick="chatApp.showConversationInfo()"><i class="fas fa-info-circle"></i></button>
                ${this.currentConversation && this.currentConversation.type === 'group' ? `<button id="add-members-btn" type="button" class="px-3 py-1 rounded bg-primary text-white text-sm" title="Add members" aria-label="Add members" onclick="chatApp.openAddMembersModal()">Add members</button>` : ''}
            </div>
        `;
        document.getElementById('chat-header').innerHTML = headerHtml;
    }

    // Open a channel in the main chat pane (show announcements/rules as messages)
    async openChannel(channelId) {
        try {
            const res = await axios.get(`${this.apiUrl}/channels/${channelId}`);
            const ch = res.data.data || res.data;

            // If channel has a backing conversation, open it via the conversation flow
            if (ch.conversation_id) {
                // switchToConversation will populate messages and header
                await this.switchToConversation(ch.conversation_id);

                // Mark this as a channel-backed conversation so posting uses the channel id when needed
                this.currentConversation.type = 'channel';
                this.currentConversation.channelId = channelId;
                this.currentConversation.allow_messages = ch.allow_messages !== false;
                this.currentConversation.is_member = !!ch.is_member;
                this.currentConversation.created_by = ch.created_by;

                // Show announcements/rules above the messages list
                const list = document.getElementById('messages-list');
                const rulesHtml = [];
                if (Array.isArray(ch.rules) && ch.rules.length) {
                    ch.rules.forEach(r => {
                        rulesHtml.push(`
                            <div class="p-3 rounded-lg border bg-muted-50">
                                <div class="text-sm font-bold">${this.escapeHtml(r.title)}</div>
                                <div class="text-xs text-muted-500 mt-1">${this.escapeHtml(r.content||'')}</div>
                            </div>
                        `);
                    });
                }
                if (rulesHtml.length) list.insertAdjacentHTML('afterbegin', rulesHtml.join('\n'));
            } else {
                // No conversation yet: render announcements as before and show an empty messages area
                this.currentConversation = {
                    id: `channel-${channelId}`,
                    channelId: channelId,
                    conversation_id: null,
                    type: 'channel',
                    name: ch.name,
                    display_name: ch.name,
                    allow_messages: ch.allow_messages !== false,
                    is_member: !!ch.is_member,
                    created_by: ch.created_by,
                };

                // leaving channels view and restore conversations list
                this.showingChannels = false;
                this.loadConversations();

                // Render header and messages container
                this.renderConversationHeader();
                const list = document.getElementById('messages-list');
                list.innerHTML = '';

                // Show announcements/rules as messages
                if (Array.isArray(ch.rules) && ch.rules.length) {
                    ch.rules.forEach(r => {
                        const html = `
                            <div class="p-3 rounded-lg border bg-muted-50">
                                <div class="text-sm font-bold">${this.escapeHtml(r.title)}</div>
                                <div class="text-xs text-muted-500 mt-1">${this.escapeHtml(r.content||'')}</div>
                            </div>
                        `;
                        list.insertAdjacentHTML('beforeend', html);
                    });
                } else {
                    list.innerHTML = `<div class="p-4 text-muted-400">No announcements yet for this channel.</div>`;
                }
            }

            document.getElementById('empty-state').classList.add('hidden');
            document.getElementById('messages-container').style.display = 'block';

            // Show or hide input based on allow_messages and membership; creators may always post
            const inputContainer = document.getElementById('message-input-container');
            const isCreator = this.currentConversation.created_by && (this.currentConversation.created_by == window.currentUserId);
            const isAdmin = (window.currentUserIsAdmin === true || window.currentUserIsAdmin === 'true');
            if (!this.currentConversation.allow_messages) {
                // admin-only channel: only admins may post
                inputContainer.style.display = isAdmin ? 'block' : 'none';
            } else if (this.currentConversation.is_member || !ch.is_private || isCreator) {
                inputContainer.style.display = 'block';
            } else {
                // private channel and not a member
                inputContainer.style.display = 'none';
            }

            // Admin: show post announcement form below messages when viewing a channel
            const adminContainerId = 'channelAdminPost';
            let adminContainer = document.getElementById(adminContainerId);
            if (adminContainer) adminContainer.remove();
            if (isAdmin) {
                adminContainer = document.createElement('div');
                adminContainer.id = adminContainerId;
                adminContainer.className = 'p-4 border-t';
                adminContainer.innerHTML = `
                    <div class="space-y-2">
                        <input id="adminRuleTitle" placeholder="Announcement title" class="w-full border px-3 py-2 rounded-lg" />
                        <textarea id="adminRuleContent" placeholder="Announcement content" class="w-full border px-3 py-2 rounded-lg"></textarea>
                        <div class="text-right"><button class="px-3 py-1 bg-primary text-white rounded-lg" onclick="chatApp.addRuleToChannel(${channelId})">Post Announcement</button></div>
                    </div>
                `;
                const messagesContainer = document.getElementById('messages-container');
                messagesContainer.parentNode.insertBefore(adminContainer, inputContainer);
            }

            // Slide chat pane in (mobile)
            this.openMobileChat();
        } catch (err) { console.error('Failed to open channel', err); alert('Unable to open channel'); }
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
                <button class="p-2 text-muted-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors" onclick="chatApp.showConversationInfo()"><i class="fas fa-info-circle"></i></button>
                ${this.currentConversation && this.currentConversation.type === 'group' ? `<button id="add-members-btn" type="button" class="px-3 py-1 rounded bg-primary text-white text-sm" title="Add members" aria-label="Add members" onclick="chatApp.openAddMembersModal()">Add members</button>` : ''}
            </div>
        `;
        document.getElementById('chat-header').innerHTML = headerHtml;
    }

    async showConversationInfo() {
        if (!this.currentConversation) return;

        // Channels use the existing channel details flow
        if (this.currentConversation.type === 'channel' && this.currentConversation.channelId) {
            await this.showChannelDetails(this.currentConversation.channelId);
            return;
        }

        // For group/direct conversations, show members list
        let conversation = this.currentConversation;
        if (!Array.isArray(conversation.participants) || conversation.participants.length === 0) {
            try {
                const res = await axios.get(`${this.apiUrl}/conversations/${conversation.id}`);
                if (res.data && res.data.success) {
                    conversation = res.data.data.conversation || conversation;
                }
            } catch (e) { console.error('Failed to load conversation info', e); }
        }

        const title = conversation.display_name || conversation.name || (conversation.type === 'direct' ? 'Direct Message' : 'Group Chat');
        document.getElementById('channelDetailsTitle').textContent = title;
        document.getElementById('channelDetailsDescription').textContent = conversation.type === 'direct' ? 'Direct message' : 'Group members';

        const rulesSection = document.getElementById('channelRulesSection');
        if (rulesSection) rulesSection.classList.add('hidden');

        const addRuleContainer = document.getElementById('channelAddRuleContainer');
        if (addRuleContainer) addRuleContainer.remove();

        const membersDiv = document.getElementById('channelMembersList');
        membersDiv.innerHTML = '';

        const participants = Array.isArray(conversation.participants) ? conversation.participants : [];
        if (participants.length) {
            participants.forEach(m => {
                const el = document.createElement('div');
                el.className = 'flex items-center gap-3';
                el.innerHTML = `<div class="h-8 w-8 rounded-full bg-muted-100 flex items-center justify-center font-bold text-xs">${(m.name||'').charAt(0).toUpperCase()}</div><div><div class="text-sm font-medium">${this.escapeHtml(m.name || m.email || 'User')}</div><div class="text-xs text-muted-400">${this.escapeHtml(m.email||'')}</div></div>`;
                membersDiv.appendChild(el);
            });
        } else {
            membersDiv.innerHTML = '<div class="text-muted">No members yet</div>';
        }

        toggleModal('channelDetailsModal');
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
            // If we're inside a conversation and participants are available, search locally
            if (this.currentConversation && Array.isArray(this.currentConversation.participants) && this.currentConversation.participants.length > 0) {
                const q = query.toLowerCase();
                const matches = this.currentConversation.participants.filter(p => {
                    const name = (p.name || '').toLowerCase();
                    const email = (p.email || '').toLowerCase();
                    return name.includes(q) || email.includes(q) || String(p.id) === query;
                }).slice(0, 10).map(p => ({ id: p.id, name: p.name, email: p.email }));
                return matches;
            }

            const res = await axios.get(`${this.apiUrl}/users/search?query=${encodeURIComponent(query)}`);
            return res.data.success ? res.data.data.users : [];
        } catch { return []; }
    }

    // Search all users via API (ignore currentConversation participants)
    async searchAllUsers(query) {
        try {
            const res = await axios.get(`${this.apiUrl}/users/search?query=${encodeURIComponent(query)}`);
            return res.data.success ? res.data.data.users : [];
        } catch (e) { console.error('searchAllUsers failed', e); return []; }
    }

    openAddMembersModal() {
        if (!this.currentConversation) return alert('No conversation selected');
        // reset modal state
        document.getElementById('addMembersSearch').value = '';
        document.getElementById('addMembersResults').innerHTML = '';
        this._addMembersSelected = new Map();
        document.getElementById('addMembersSelected').innerHTML = '';
        toggleModal('addMembersModal');
    }

    async handleAddMembersSearch(q) {
        if (!q || q.trim().length < 1) { document.getElementById('addMembersResults').innerHTML = ''; return; }
        const users = await this.searchAllUsers(q.trim());
        // filter out users already in conversation and current user
        const existing = this.currentConversation.participants ? this.currentConversation.participants.map(p => p.id) : [];
        const filtered = users.filter(u => u.id !== this.currentUserId && !existing.includes(u.id)).slice(0, 20);
        this.renderAddMembersResults(filtered);
    }

    renderAddMembersResults(users) {
        const container = document.getElementById('addMembersResults');
        if (!container) return;
        if (!users || users.length === 0) { container.innerHTML = '<div class="p-2 text-muted-400 text-sm">No users found</div>'; return; }
        container.innerHTML = users.map(u => `
            <div class="p-2 flex items-center justify-between border-b hover:bg-muted-50 cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-full bg-muted-100 flex items-center justify-center font-bold text-sm">${(u.name||'U').charAt(0).toUpperCase()}</div>
                    <div>
                        <div class="text-sm font-medium">${this.escapeHtml(u.name || u.email)}</div>
                        <div class="text-xs text-muted-400">${this.escapeHtml(u.email || '')}</div>
                    </div>
                </div>
                <div>
                    <button class="px-3 py-1 rounded text-sm bg-primary text-white" onclick="chatApp.toggleAddMemberSelection(${u.id}, '${u.name.replace(/'/g, "\'")}')">Add</button>
                </div>
            </div>
        `).join('');
    }

    toggleAddMemberSelection(userId, userName) {
        this._addMembersSelected = this._addMembersSelected || new Map();
        if (this._addMembersSelected.has(userId)) {
            this._addMembersSelected.delete(userId);
        } else {
            this._addMembersSelected.set(userId, userName);
        }
        const sel = document.getElementById('addMembersSelected');
        sel.innerHTML = Array.from(this._addMembersSelected.entries()).map(([id, name]) => `<span class="inline-flex items-center gap-2 bg-muted-100 px-2 py-1 rounded mr-1">${this.escapeHtml(name)} <button onclick="chatApp.toggleAddMemberSelection(${id}, '${name.replace(/'/g, "\'")}')" class="text-xs text-danger ml-2">x</button></span>`).join('');
    }

    async submitAddMembers() {
        if (!this.currentConversation) return alert('No conversation selected');
        const ids = this._addMembersSelected ? Array.from(this._addMembersSelected.keys()) : [];
        if (!ids || ids.length === 0) return alert('No users selected');
        try {
            const res = await axios.post(`${this.apiUrl}/conversations/${this.currentConversation.id}/participants`, { participant_ids: ids });
            if (res.data && res.data.success) {
                // refresh conversation participants
                this.currentConversation = res.data.data.conversation;
                // inform user
                toggleModal('addMembersModal');
                if (typeof showToast === 'function') showToast('Members added', 'success');
            }
        } catch (e) { console.error('submitAddMembers failed', e); alert('Failed to add members'); }
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

            // CHANNEL: allow send if viewing a channel and user is allowed
            if (this.currentConversation && this.currentConversation.type === 'channel') {
                // client-side permission checks: admin-only channels allow admins, otherwise require membership/creator
                const isAdmin = (window.currentUserIsAdmin === true || window.currentUserIsAdmin === 'true');
                if (!this.currentConversation.allow_messages && !isAdmin) { alert('This channel is admin-only.'); return; }
                const isCreator = this.currentConversation.created_by && (this.currentConversation.created_by == window.currentUserId);
                if (!this.currentConversation.is_member && !isCreator && !isAdmin) { alert('You are not allowed to post to this channel.'); return; }

                try {
                    // If channel has a backing conversation, post via conversations endpoint (reuses realtime flow)
                    const convId = this.currentConversation.conversation_id || null;
                    let res;
                    if (convId) {
                        res = await axios.post(`${this.apiUrl}/conversations/${convId}/messages`, { content: val });
                    } else {
                        // fallback to channel-specific endpoint
                        res = await axios.post(`${this.apiUrl}/channels/${this.currentConversation.channelId}/messages`, { content: val });
                    }

                    if (res && res.data && res.data.success) {
                        const msg = res.data.data.message;
                        const list = document.getElementById('messages-list');
                        list.insertAdjacentHTML('beforeend', this.renderMessage(msg));
                        this.scrollToBottom();
                        input.value = '';
                    }
                } catch (err) { 
                    console.error(err);
                    const serverMsg = err && err.response && err.response.data && (err.response.data.message || err.response.data.error) ? (err.response.data.message || err.response.data.error) : 'Failed to send message to channel';
                    if (typeof showToast === 'function') showToast(serverMsg, 'error'); else alert(serverMsg);
                }

                return;
            }

            if(await this.sendMessage(this.currentConversation.id, val)) input.value = '';
        });
        
        const ta = document.getElementById('message-input');
        ta.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        const app = this;
        ta.addEventListener('keydown', (e) => {
            const dd = document.getElementById('mention-dropdown');
            const ddOpen = dd && !dd.classList.contains('hidden');

            if (ddOpen) {
                // Navigate mention dropdown
                const items = Array.from(dd.querySelectorAll('[data-mention-index]'));
                const active = dd.querySelector('[data-active="true"]');
                let idx = active ? Number(active.getAttribute('data-mention-index')) : -1;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    idx = Math.min(items.length - 1, idx + 1);
                    app.updateMentionHighlight(idx);
                    return;
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    idx = Math.max(0, idx - 1);
                    app.updateMentionHighlight(idx);
                    return;
                }
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (idx >= 0 && items[idx]) {
                        items[idx].click();
                    }
                    return;
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    app.hideMentionDropdown();
                    return;
                }
            }

            // If Enter is pressed WITHOUT Shift (and dropdown not open)
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault(); // Prevent creating a new line
                
                // Programmatically trigger the form submission
                // We use requestSubmit() so it triggers the 'submit' event listener above
                document.getElementById('message-form').requestSubmit();
                
                // Reset height after sending
                ta.style.height = 'auto'; 
            }
        });

        // Mention helpers on app
        app.hideMentionDropdown = function() {
            const dd = document.getElementById('mention-dropdown');
            if (dd) dd.classList.add('hidden');
            this._mentionItems = [];
            this._mentionActive = -1;
        };

        app.updateMentionHighlight = function(index) {
            const dd = document.getElementById('mention-dropdown');
            if (!dd) return;
            const items = Array.from(dd.querySelectorAll('[data-mention-index]'));
            items.forEach(it => it.removeAttribute('data-active'));
            if (items[index]) items[index].setAttribute('data-active', 'true');
            this._mentionActive = index;
            // ensure visibility
            if (items[index]) items[index].scrollIntoView({ block: 'nearest' });
        };

        app.selectMention = function(user) {
            const ta = document.getElementById('message-input');
            const caret = ta.selectionStart;
            const before = ta.value.slice(0, caret);
            const after = ta.value.slice(caret);
            const lastAt = before.lastIndexOf('@');
            if (lastAt === -1) return;
            const insert = (user.id === 'all') ? '@all ' : (`@${user.name} `);
            const newVal = before.slice(0, lastAt) + insert + after;
            ta.value = newVal;
            // place caret after inserted mention
            const newPos = (before.slice(0, lastAt) + insert).length;
            ta.setSelectionRange(newPos, newPos);
            ta.focus();
            this.hideMentionDropdown();
        };

        app.renderMentionDropdown = function(users, rectAnchor) {
            const dd = document.getElementById('mention-dropdown');
            if (!dd) return;
            if (!users || users.length === 0) {
                dd.innerHTML = '';
                dd.classList.add('hidden');
                return;
            }

            dd.innerHTML = users.map((u, i) => {
                const display = u.id === 'all' ? 'All' : (u.name || u.email || ('User ' + u.id));
                return `<div data-mention-index="${i}" data-user-id="${u.id}" class="px-3 py-2 hover:bg-muted-100 cursor-pointer">${this.escapeHtml(display)}</div>`;
            }).join('');

            // Attach click handlers
            Array.from(dd.children).forEach((el, i) => {
                el.addEventListener('click', (ev) => {
                    const uid = el.getAttribute('data-user-id');
                    const u = users[Number(i)];
                    app.selectMention(u);
                });
            });

            dd.classList.remove('hidden');
            this._mentionItems = users;
            this._mentionActive = -1;
            // position dropdown under textarea
            const taRect = document.getElementById('message-input').getBoundingClientRect();
            const parentRect = document.getElementById('message-input-container').getBoundingClientRect();
            dd.style.position = 'absolute';
            dd.style.left = (taRect.left - parentRect.left) + 'px';
            dd.style.top = (taRect.bottom - parentRect.top + 6) + 'px';
        };

        app.handleMentionInput = async function(e) {
            const ta = document.getElementById('message-input');
            const caret = ta.selectionStart;
            const before = ta.value.slice(0, caret);
            const lastAt = before.lastIndexOf('@');
            if (lastAt === -1) { this.hideMentionDropdown(); return; }
            // ensure @ is start or preceded by whitespace
            if (lastAt > 0 && !/\s/.test(before.charAt(lastAt - 1))) { this.hideMentionDropdown(); return; }

            const token = before.slice(lastAt + 1);
            // stop if token contains whitespace or '@' or too long
            if (token.length === 0) { this.hideMentionDropdown(); return; }
            if (/\s/.test(token) || token.indexOf('@') !== -1) { this.hideMentionDropdown(); return; }

            const q = token;
            // quick include @all option if it matches and current conversation is a group
            const users = await this.searchUsers(q);
            const suggestions = [];
            if (this.currentConversation && this.currentConversation.type !== 'direct' && 'all'.startsWith(q.toLowerCase())) {
                suggestions.push({ id: 'all', name: 'all' });
            }
            users.forEach(u => suggestions.push(u));

            this.renderMentionDropdown(suggestions);
        };

        // Hook mention input separate from resize logic
        ta.addEventListener('input', (e) => {
            // call existing resize logic already present; now handle mentions
            app.handleMentionInput(e);
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
        // New Channel Form submit (if modal exists)
        const newChannelForm = document.getElementById('newChannelForm');
        if (newChannelForm) {
            newChannelForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = document.getElementById('channelName').value.trim();
                const description = document.getElementById('channelDescription').value.trim();
                const is_private = document.getElementById('channelPrivate').checked ? 1 : 0;
                // announcementOnly checkbox is rendered only for admins
                const adminOnlyEl = document.getElementById('adminOnly');
                const adminOnly = adminOnlyEl ? adminOnlyEl.checked : false;
                // if adminOnly checked, only admins may post (allow_messages = false)
                const allow_messages = adminOnly ? false : true;
                if (!name) return alert('Channel name is required');
                try {
                    await this.createChannel({ name, description, is_private, allow_messages });
                    toggleModal('newChannelModal');
                    // reload channels
                    this.loadChannels();
                } catch (err) { console.error(err); alert('Failed to create channel'); }
            });
        }

        // Channels toggle button
        const channelsToggleBtn = document.getElementById('channelsToggleBtn');
        if (channelsToggleBtn) channelsToggleBtn.addEventListener('click', (e) => { this.showChannels(); });
    }
    
    setupPolling() {
        setInterval(() => { 
            // Avoid overwriting the channels view while the user is browsing channels
            if (!this.showingChannels) this.loadConversations();
            this.loadOnlineUsers(); 
        }, 6000);
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
        try {
            const res = await axios.get(`${this.apiUrl}/users/online`);
            if (!res.data || !res.data.success) {
                console.warn('Failed to load online users', res.data);
                return;
            }

            const users = res.data.data.users || [];
            this.onlineUsers = new Set(users.map(u => u.id));

            const countEl = document.getElementById('online-count');
            if (countEl) countEl.textContent = users.length;

            const list = document.getElementById('onlineUsersList');
            if (!list) return;

            if (users.length === 0) {
                list.innerHTML = `<div class="min-h-[100px] flex items-center justify-center text-muted-400 text-sm">No one online</div>`;
                return;
            }

            list.innerHTML = users.map(u => `
                <div id="online-user-${u.id}" class="p-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 shrink-0 rounded-full bg-muted-100 flex items-center justify-center font-bold text-sm">${(u.name||'U').charAt(0).toUpperCase()}</div>
                        <div>
                            <div class="text-sm font-medium text-main">${u.name}</div>
                        </div>
                    </div>
                </div>
            `).join('');

        } catch (err) {
            console.error('Error loading online users:', err);
        }
    }

    // --- CHANNELS METHODS ---
    async loadChannels() {
        try {
            const res = await axios.get(`${this.apiUrl}/channels`);
            const data = res.data;
            const channels = Array.isArray(data) ? data : (data.data || []);
            this.renderChannels(channels);
        } catch (e) { console.error('Failed to load channels', e); const el = document.getElementById('channels-list'); if (el) el.innerHTML = '<div class="p-2 text-center text-muted-400 text-sm">Failed to load channels.</div>'; }
    }

    renderChannels(channels) {
        const container = document.getElementById('channels-list');
        if (!container) return;
        if (!channels || channels.length === 0) {
            container.innerHTML = `<div class="p-2 text-center text-muted-400 text-sm">No channels yet.</div>`;
            return;
        }

        container.innerHTML = channels.map(ch => {
            const isMember = !!ch.is_member;
            const membersCount = ch.members_count || 0;
            const isAdminOnly = ch.allow_messages === false;
            const outerClass = isAdminOnly ? 'group flex items-center gap-3 p-2 rounded-lg bg-primary/5 border-l-4 border-primary/10 transition-colors cursor-default' : 'group flex items-center gap-3 p-2 rounded-lg hover:bg-muted-100 transition-colors cursor-default';
            const badge = isAdminOnly ? `<span class="inline-flex items-center text-[10px] bg-primary text-white px-2 py-0.5 rounded-full ml-2 font-semibold">ADMIN</span>` : '';

            return `
                <div class="${outerClass}">
                    <div class="h-8 w-8 shrink-0 rounded-full ${isAdminOnly ? 'bg-primary text-white' : 'bg-muted-100 text-muted-600'} flex items-center justify-center font-bold text-sm">#</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline">
                            <h4 class="text-sm font-semibold text-main truncate">${this.escapeHtml(ch.name)} ${badge}</h4>
                            <span class="text-[10px] text-muted-400 font-medium ml-2 whitespace-nowrap">${membersCount}</span>
                        </div>
                        <p class="text-xs text-muted-400 truncate mt-0.5">${this.escapeHtml(ch.description || '')}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        ${isAdminOnly ? '' : `<button class="px-2 py-1 rounded text-xs ${isMember ? 'border border-danger text-danger bg-white' : 'bg-primary text-white'}" onclick="chatApp.toggleChannelMembership(${ch.id}, ${isMember})">${isMember ? 'Leave' : 'Join'}</button>`}
                        <button class="px-2 py-1 rounded text-xs border" onclick="chatApp.openChannel(${ch.id})">Open</button>
                    </div>
                </div>
            `;
        }).join('');
    }

    async createChannel(payload) {
        try {
            const res = await axios.post(`${this.apiUrl}/channels`, payload);
            return res.data;
        } catch (e) { console.error('Failed to create channel', e); throw e; }
    }

    async joinChannel(id) {
        try { const res = await axios.post(`${this.apiUrl}/channels/${id}/join`); return res.data; } catch (e) { console.error(e); throw e; }
    }

    async leaveChannel(id) {
        try { const res = await axios.post(`${this.apiUrl}/channels/${id}/leave`); return res.data; } catch (e) { console.error(e); throw e; }
    }

    async showChannelDetails(id) {
        try {
            const res = await axios.get(`${this.apiUrl}/channels/${id}`);
            const ch = Array.isArray(res.data) ? res.data[0] : (res.data.data || res.data);
            const rulesSection = document.getElementById('channelRulesSection');
            if (rulesSection) rulesSection.classList.remove('hidden');
            document.getElementById('channelDetailsTitle').textContent = ch.name || 'Channel';
            document.getElementById('channelDetailsDescription').textContent = ch.description || '';

            const membersDiv = document.getElementById('channelMembersList');
            membersDiv.innerHTML = '';
            if (Array.isArray(ch.members) && ch.members.length) {
                ch.members.forEach(m => {
                    const el = document.createElement('div');
                    el.className = 'flex items-center gap-3';
                    el.innerHTML = `<div class="h-8 w-8 rounded-full bg-muted-100 flex items-center justify-center font-bold text-xs">${(m.name||'').charAt(0).toUpperCase()}</div><div><div class="text-sm font-medium">${this.escapeHtml(m.name)}</div><div class="text-xs text-muted-400">${this.escapeHtml(m.email||'')}</div></div>`;
                    membersDiv.appendChild(el);
                });
            } else membersDiv.innerHTML = '<div class="text-muted">No members yet</div>';

            const rulesDiv = document.getElementById('channelRulesList');
            rulesDiv.innerHTML = '';
            if (Array.isArray(ch.rules) && ch.rules.length) {
                ch.rules.forEach(r => {
                    const el = document.createElement('div');
                    el.className = 'mb-2';
                    el.innerHTML = `<div class="font-medium">${this.escapeHtml(r.title)}</div><div class="text-xs text-muted-400">${this.escapeHtml(r.content||'')}</div>`;
                    rulesDiv.appendChild(el);
                });
            } else rulesDiv.innerHTML = '<div class="text-muted">No rules defined</div>';

            // If current user is admin, show add-rule form
            const isAdmin = (window.currentUserIsAdmin === true || window.currentUserIsAdmin === 'true');
            const addRuleContainerId = 'channelAddRuleContainer';
            let addRuleContainer = document.getElementById(addRuleContainerId);
            if (!addRuleContainer) {
                addRuleContainer = document.createElement('div');
                addRuleContainer.id = addRuleContainerId;
                addRuleContainer.className = 'mt-4';
                document.getElementById('channelDetailsContent').appendChild(addRuleContainer);
            }
            addRuleContainer.innerHTML = '';
            if (isAdmin) {
                addRuleContainer.innerHTML = `
                    <h4 class="font-medium mt-4">Add Announcement / Rule</h4>
                    <div class="mt-2">
                        <input id="newRuleTitle" class="w-full border px-3 py-2 rounded-lg mb-2" placeholder="Title" />
                        <textarea id="newRuleContent" class="w-full border px-3 py-2 rounded-lg mb-2" placeholder="Content"></textarea>
                        <div class="text-right"><button class="px-3 py-1 bg-primary text-white rounded-lg" onclick="chatApp.addRuleToChannel(${ch.id})">Post</button></div>
                    </div>
                `;
            }

            toggleModal('channelDetailsModal');
        } catch (e) { console.error('Failed to load channel', e); }
    }

    async addRuleToChannel(channelId) {
        const title = document.getElementById('newRuleTitle')?.value?.trim();
        const content = document.getElementById('newRuleContent')?.value?.trim();
        if (!title) return alert('Title is required');
        try {
            const res = await axios.post(`${this.apiUrl}/channels/${channelId}/rules`, { title, content });
            alert('Announcement posted');
            // refresh view in chat pane
            this.openChannel(channelId);
        } catch (err) {
            console.error(err);
            alert('Failed to post announcement (admin only)');
        }
    }

    async toggleChannelMembership(id, isMember) {
        try {
            if (isMember) await this.leaveChannel(id);
            else await this.joinChannel(id);
            await this.loadChannels();
        } catch (e) { alert('Failed to update membership'); }
    }

    showChannels() { this.showingChannels = true; this.loadChannels(); }

    hideChannels() { this.showingChannels = false; this.loadConversations(); }

    escapeHtml(str) { if(!str) return ''; return String(str).replace(/&/g, '&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

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
            return;
        }

        // Listen for global user status changes
        try {
            window.Echo.channel('user-status').listen('.user.status.changed', (e) => {
                const user = e.user || {};
                const status = e.status;
                if (!user.id) return;

                // Update local set and UI
                if (status === 'online') {
                    this.onlineUsers.add(user.id);
                    const list = document.getElementById('onlineUsersList');
                    if (list && !document.getElementById(`online-user-${user.id}`)) {
                        const html = `
                            <div id="online-user-${user.id}" class="p-3 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 shrink-0 rounded-full bg-muted-100 flex items-center justify-center font-bold text-sm">${(user.name||'U').charAt(0).toUpperCase()}</div>
                                    <div>
                                        <div class="text-sm font-medium text-main">${user.name}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                        list.insertAdjacentHTML('afterbegin', html);
                    }
                } else {
                    this.onlineUsers.delete(user.id);
                    const el = document.getElementById(`online-user-${user.id}`);
                    if (el) el.remove();
                }

                const countEl = document.getElementById('online-count');
                if (countEl) countEl.textContent = this.onlineUsers.size;
            });
        } catch (err) {
            console.warn('Failed to subscribe to user-status channel', err);
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
    document.getElementById('file-preview').classList.add('hidden');
    document.getElementById('image-input').value = '';
    document.getElementById('file-input').value = '';
}

let chatApp;
document.addEventListener('DOMContentLoaded', () => {
    // Initialize when the new DM/Channels containers exist
    if (document.getElementById('dm-list') || document.getElementById('channels-list')) {
        chatApp = new RealtimeChatApp();
    }
});
</script>
@endpush