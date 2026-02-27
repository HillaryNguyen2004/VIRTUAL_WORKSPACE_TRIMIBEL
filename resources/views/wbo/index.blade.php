@extends('layout_dashboard')

@section('title', __('app.whiteboard'))

@section('content')
<div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
    <!-- Header -->
    <div class="flex flex-col">
        <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">{{ __('app.whiteboard') }}</h1>
        <p class="text-muted-500 text-sm md:text-base mt-1">{{ __('app.whiteboard_description') }}</p>
    </div>

    {{-- Join/Create Card (matches video-chat layout) --}}
    <div class="mb-10">
        <div class="bg-white border border-muted-300 rounded-2xl overflow-hidden relative animate-fade-in-up">
            <div class="p-8">
                <div class="grid grid-cols-1 @4xl:grid-cols-2 gap-10 items-center relative z-10">

                    {{-- Open Existing Board (left) --}}
                    <div class="flex flex-col gap-4">
                        <div>
                            <h4 class="text-md md:text-lg font-semibold text-main">{{ __('app.open_existing_board') }}</h4>
                            <p class="text-muted-500 text-xs md:text-sm mt-1">{{ __('app.open_existing_board_description') }}</p>
                        </div>

                        <form action="{{ route('wbo.open') }}" method="POST">
                            @csrf
                            <div class="flex rounded-xl shadow-sm">
                                <div class="relative flex-grow focus-within:z-10">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-muted-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
                                        </svg>
                                    </div>
                                    <input type="text" name="board_id"
                                           class="focus:ring-2 focus:ring-primary/30 focus:border-primary block w-full rounded-l-xl pl-11 sm:text-sm bg-canvas border-muted-200 text-main placeholder-muted-400 py-3 transition-all"
                                           placeholder="{{ __('app.board_id_placeholder') }}" required>
                                </div>
                                <button type="submit" class="-ml-px relative inline-flex items-center space-x-2 px-6 py-3 border border-muted-200 text-sm font-medium rounded-r-xl text-muted-600 bg-muted-50 hover:bg-muted-100 focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary transition-colors">
                                    <span>{{ __('app.open_board') }}</span>
                                </button>
                            </div>
                            @error('board_id')
                                <p class="text-danger text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </form>
                    </div>

                    {{-- Create New Board (right) --}}
                    <div class="flex flex-row @4xl:flex-col justify-between items-start border-t @4xl:border-t-0 @4xl:border-l border-muted-200 @4xl:pl-10 pt-8 @4xl:pt-0">
                        <div class="mb-4 text-left">
                            <h4 class="text-md md:text-lg font-semibold text-main">{{ __('app.create_new_board') }}</h4>
                            <p class="text-muted-500 text-xs md:text-sm mt-1">{{ __('app.create_new_board_description') }}</p>
                        </div>

                        <form action="{{ route('wbo.create') }}" method="POST" class="w-auto">
                            @csrf
                            <button type="submit" class="group flex items-center justify-center gap-2 rounded-xl bg-primary-gradient px-6 py-3 text-white text-md md:text-base font-semibold shadow-lg shadow-primary/20 transition-all hover:bg-primary-hover focus:ring-4 focus:ring-primary/30 active:scale-95">
                                <svg class="h-5 w-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/>
                                </svg>
                                {{ __('app.create_board') }}
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Recent Boards History --}}
    @if (!empty($recentBoards) && count($recentBoards) > 0)
    <div class="flex items-center justify-between mb-6 animate-fade-in-up [animation-delay:150ms]">
        <h4 class="text-md md:text-lg font-semibold text-main">{{ __('app.recent_boards') }}</h4>
    </div>
    <div class="grid grid-cols-1 @2xl:grid-cols-2 @5xl:grid-cols-3 gap-4 animate-fade-in-up [animation-delay:200ms]">
        @foreach ($recentBoards as $board)
        <a href="{{ route('wbo.board', $board['id']) }}"
           class="bg-white rounded-2xl border border-muted-300 hover:border-primary/30 transition-all duration-300 flex flex-col h-full group p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="mb-2">
                        @if ($board['action'] === 'created')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                {{ __('app.created') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted-100 text-muted-600">
                                {{ __('app.opened') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-muted-500 truncate"><code>{{ substr($board['id'], 0, 13) }}...</code></p>
                    <p class="text-xs text-muted-400 mt-1">{{ \Carbon\Carbon::parse($board['accessed_at'])->diffForHumans() }}</p>
                </div>
                <svg class="w-5 h-5 text-muted-400 group-hover:text-primary transition-colors flex-shrink-0"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
        @endforeach
    </div>
    @endif

    {{-- Info Section --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-4">
        <div class="flex gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 fill-blue-600 flex-shrink-0 mt-0.5">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-semibold">{{ __('app.whiteboard_info_title') }}</p>
                <p class="mt-1">{{ __('app.whiteboard_info_description') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
