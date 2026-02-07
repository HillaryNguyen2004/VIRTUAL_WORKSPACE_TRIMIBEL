@extends('layout_dashboard')

@section('title', __('app.whiteboard'))

@section('content')
<div class="flex flex-col gap-6 px-6 py-8">
    <!-- Header -->
    <div class="flex flex-col gap-2">
        <h1 class="text-3xl font-bold text-main">{{ __('app.whiteboard') }}</h1>
        <p class="text-muted-500">{{ __('app.whiteboard_description') }}</p>
    </div>

    <!-- Choice Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl">
        <!-- Create New Board Card -->
        <div class="bg-white rounded-xl border border-muted-200 p-8 hover:shadow-lg transition-shadow">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-center w-12 h-12 bg-primary/10 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 fill-primary">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-main">{{ __('app.create_new_board') }}</h2>
                    <p class="text-sm text-muted-500 mt-1">{{ __('app.create_new_board_description') }}</p>
                </div>
                <form action="{{ route('wbo.create') }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full btn btn-primary">
                        {{ __('app.create_new_board') }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Open Existing Board Card -->
        <div class="bg-white rounded-xl border border-muted-200 p-8 hover:shadow-lg transition-shadow">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-center w-12 h-12 bg-secondary/10 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 fill-secondary">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5.04-6.71l-2.75 3.54-2.59-3.02L6.5 17h11l-3.54-4.71z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-main">{{ __('app.open_existing_board') }}</h2>
                    <p class="text-sm text-muted-500 mt-1">{{ __('app.open_existing_board_description') }}</p>
                </div>
                <form action="{{ route('wbo.open') }}" method="POST" class="mt-4">
                    @csrf
                    <div class="flex flex-col gap-3">
                        <input 
                            type="text" 
                            name="board_id" 
                            placeholder="Enter board ID (UUID format)" 
                            class="w-full px-4 py-2 border border-muted-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary text-sm"
                            required
                        />
                        @error('board_id')
                            <p class="text-red-500 text-xs">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="w-full btn btn-secondary">
                            {{ __('app.open_board') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Boards History -->
    @if (!empty($recentBoards) && count($recentBoards) > 0)
    <div class="mt-4">
        <h2 class="text-2xl font-bold text-main mb-4">{{ __('app.recent_boards') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($recentBoards as $board)
            <a href="{{ route('wbo.board', $board['id']) }}" 
               class="bg-white rounded-xl border border-muted-200 p-4 hover:shadow-lg transition-shadow hover:border-primary group">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex-shrink-0">
                                @if ($board['action'] === 'created')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ __('app.created') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ __('app.opened') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-muted-500 truncate">
                            <code>{{ substr($board['id'], 0, 13) }}...</code>
                        </p>
                        <p class="text-xs text-muted-400 mt-1">
                            {{ \Carbon\Carbon::parse($board['accessed_at'])->diffForHumans() }}
                        </p>
                    </div>
                    <svg class="w-5 h-5 text-muted-400 group-hover:text-primary transition-colors flex-shrink-0" 
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Info Section -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-4">
        <div class="flex gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 fill-blue-600 flex-shrink-0 mt-0.5">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-semibold">{{ __('app.whiteboard_info_title') }}</p>
                <p class="mt-1">{{ __('app.whiteboard_info_description') }}</p>
            </div>
        </div>
    </div>
</div>

<style>
.btn {
    @apply px-4 py-2 rounded-lg font-medium transition-colors;
}

.btn-primary {
    @apply bg-primary text-white hover:bg-primary-hover;
}

.btn-secondary {
    @apply bg-secondary text-white hover:bg-secondary-hover;
}
</style>
@endsection
