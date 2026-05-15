@extends('layout_dashboard')

@section('title', 'System Logs')

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">System Logs</h1>
            <p class="text-muted-500 text-sm md:text-base">Recent application log entries.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @foreach(['all' => 'All', 'error' => 'Errors', 'warning' => 'Warnings', 'info' => 'Info'] as $value => $label)
                <a
                    href="{{ route('super_admin.logs', ['level' => $value]) }}"
                    class="px-3 py-1.5 rounded-full text-sm border border-muted-200 {{ $level === $value ? 'bg-primary/10 text-primary border-primary/30' : 'bg-white text-muted-600' }}"
                >
                    {{ $label }}
                </a>
            @endforeach

            <form method="POST" action="{{ route('super_admin.logs.clear') }}" class="ml-auto">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1.5 rounded-full text-sm border border-muted-200 bg-white text-muted-600 hover:border-primary/40 hover:text-primary">
                    Clear Logs
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-muted-200 bg-white p-4 shadow-sm">
            <div class="max-h-[520px] overflow-auto space-y-2">
                @forelse($logs as $log)
                    @php
                        $levelClass = match ($log['level']) {
                            'error' => 'bg-red-50 border-red-200 text-red-700',
                            'warning' => 'bg-amber-50 border-amber-200 text-amber-700',
                            'info' => 'bg-blue-50 border-blue-200 text-blue-700',
                            default => 'bg-slate-50 border-slate-200 text-slate-700',
                        };
                    @endphp
                    <div class="flex flex-col gap-1 rounded-xl border {{ $levelClass }} px-4 py-3">
                        <div class="text-xs uppercase tracking-wide">{{ $log['level'] }}</div>
                        <div class="text-sm font-mono text-muted-900">{{ $log['message'] }}</div>
                        <div class="text-xs text-muted-500">{{ $log['time'] }}</div>
                    </div>
                @empty
                    <div class="text-sm text-muted-500">No logs found.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
