@extends('layout_dashboard')

@section('title', 'System Health')

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">System Health</h1>
            <p class="text-muted-500 text-sm md:text-base">Live environment and services status.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-muted-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-400">Versions</div>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">PHP</span>
                        <span class="font-semibold text-main">{{ $php_version }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">Laravel</span>
                        <span class="font-semibold text-main">{{ $laravel_version }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">Environment</span>
                        <span class="font-semibold text-main">{{ $environment }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-muted-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-400">Services</div>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">Database</span>
                        <span class="font-semibold {{ $db_status === 'online' ? 'text-emerald-600' : 'text-red-600' }}">{{ $db_status }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">Cache (Redis)</span>
                        <span class="font-semibold {{ $cache_status === 'online' ? 'text-emerald-600' : 'text-red-600' }}">{{ $cache_status }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">Queue Driver</span>
                        <span class="font-semibold text-main">{{ $queue_driver }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-muted-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-400">Resources</div>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">Memory Usage</span>
                        <span class="font-semibold text-main">{{ $memory_usage }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">Disk Free</span>
                        <span class="font-semibold text-main">{{ $disk_free }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">Uptime</span>
                        <span class="font-semibold text-main">{{ $uptime }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-muted-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-muted-400">Debug</div>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted-600">Debug Mode</span>
                        <span class="font-semibold text-main">{{ $debug_mode ? 'on' : 'off' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
