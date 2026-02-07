@extends('layout_dashboard')

@section('title', __('app.whiteboard'))

@section('content')
<div class="flex flex-col w-full min-h-screen overflow-hidden">
    <!-- Header -->
    <div class="bg-white border-b border-muted-200 px-6 py-4 flex justify-between items-center shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-main">{{ __('app.whiteboard') }}</h1>
            <p class="text-sm text-muted-500 mt-1">
                {{ __('app.board_id') }}:
                <code class="bg-muted-100 px-2 py-1 rounded text-xs">{{ $boardId }}</code>
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="copyBoardId()"
                class="flex items-center gap-2 px-4 py-2 bg-muted-100 hover:bg-muted-200 rounded-lg transition-colors text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4 fill-current">
                    <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" />
                </svg>
                {{ __('app.copy_board_id') }}
            </button>

            <a href="{{ route('wbo.index') }}"
                class="flex items-center gap-2 px-4 py-2 bg-muted-100 hover:bg-muted-200 rounded-lg transition-colors text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4 fill-current">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                </svg>
                {{ __('app.back') }}
            </a>
        </div>
    </div>

    <!-- Whiteboard iframe fills remaining space -->
    <div class="flex-1 w-full min-h-0">
        <iframe
            src="{{ $wboUrl }}"
            class="w-full h-full border-0"
            allow="clipboard-read; clipboard-write"
            title="Whiteboard"
        ></iframe>
    </div>
</div>

<script>
function copyBoardId() {
    const boardId = '{{ $boardId }}';
    navigator.clipboard.writeText(boardId).then(() => {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg';
        toast.textContent = '{{ __("app.board_id_copied") }}';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }).catch(err => console.error('Failed to copy:', err));
}
</script>
@endsection
