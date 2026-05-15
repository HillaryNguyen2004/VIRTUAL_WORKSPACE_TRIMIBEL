@extends('layout_dashboard')

@section('title', 'Queues & Jobs')

@section('content')
    <div class="flex flex-col gap-6 w-full mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">Queues & Jobs</h1>
            <p class="text-muted-500 text-sm md:text-base">Pending queues and recent failed jobs.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-muted-200 bg-white p-4 shadow-sm">
                <div class="text-sm font-semibold text-main">Pending Jobs</div>
                <div class="mt-3 space-y-2">
                    @forelse($queues as $queue)
                        <div class="flex items-center justify-between rounded-xl border border-muted-200 px-4 py-2">
                            <span class="text-sm font-medium text-main">{{ $queue->queue }}</span>
                            <span class="text-sm text-muted-600">{{ $queue->pending }} pending</span>
                        </div>
                    @empty
                        <div class="text-sm text-muted-500">No pending queues or queue tables not installed.</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-muted-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-main">Failed Jobs</div>
                    <form method="POST" action="{{ route('super_admin.queues.retry-all') }}">
                        @csrf
                        <button type="submit" class="text-sm px-3 py-1.5 rounded-full border border-muted-200 text-muted-600 hover:border-primary/40 hover:text-primary">
                            Retry All
                        </button>
                    </form>
                </div>
                <div class="mt-3 space-y-2 max-h-[320px] overflow-auto">
                    @forelse($failedJobs as $job)
                        <div class="flex flex-col gap-2 rounded-xl border border-muted-200 px-4 py-3">
                            <div class="text-xs text-muted-500">{{ $job->failed_at }}</div>
                            <div class="text-sm font-medium text-main">{{ $job->queue }}</div>
                            <div class="text-xs text-muted-500 break-all">{{ $job->exception }}</div>
                            <form method="POST" action="{{ route('super_admin.queues.retry', $job->id) }}">
                                @csrf
                                <button type="submit" class="text-xs px-3 py-1 rounded-full border border-muted-200 text-muted-600 hover:border-primary/40 hover:text-primary">
                                    Retry
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="text-sm text-muted-500">No failed jobs or queue tables not installed.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
