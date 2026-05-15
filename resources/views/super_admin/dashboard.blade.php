@extends('layout_dashboard')

@section('title', 'Super Admin Dashboard')

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">Super Admin Dashboard</h1>
            <p class="text-muted-500 text-sm md:text-base">Technical operations overview and tools.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-muted-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-400 uppercase tracking-wide">Account</div>
                <div class="mt-2 text-lg font-semibold">{{ auth()->user()->name }}</div>
                <div class="text-sm text-muted-500">{{ auth()->user()->email }}</div>
                <div class="mt-3 inline-flex items-center px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold uppercase tracking-wide">
                    super_admin
                </div>
            </div>

            <div class="rounded-2xl border border-muted-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-400 uppercase tracking-wide">System Logs</div>
                <p class="mt-2 text-sm text-muted-600">Review recent errors and warnings.</p>
                <a href="{{ route('super_admin.logs') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary">
                    View logs
                    <span aria-hidden="true">↗</span>
                </a>
            </div>

            <div class="rounded-2xl border border-muted-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-400 uppercase tracking-wide">Database Status</div>
                <p class="mt-2 text-sm text-muted-600">Check table sizes and connections.</p>
                <a href="{{ route('super_admin.database') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary">
                    View database
                    <span aria-hidden="true">↗</span>
                </a>
            </div>

            <div class="rounded-2xl border border-muted-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-400 uppercase tracking-wide">Queues & Jobs</div>
                <p class="mt-2 text-sm text-muted-600">Monitor pending and failed jobs.</p>
                <a href="{{ route('super_admin.queues') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary">
                    View queues
                    <span aria-hidden="true">↗</span>
                </a>
            </div>

            <div class="rounded-2xl border border-muted-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-muted-400 uppercase tracking-wide">System Health</div>
                <p class="mt-2 text-sm text-muted-600">Check runtime and service status.</p>
                <a href="{{ route('super_admin.health') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary">
                    View health
                    <span aria-hidden="true">↗</span>
                </a>
            </div>
        </div>
    </div>
@endsection
