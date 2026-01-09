@extends('layout_dashboard')
@section('title', __('staff_pending_day_off.title'))

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $dashRoute = 'user.dashboard';
    if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
        $dashRoute = 'admin.dashboard';
    } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
        $dashRoute = 'staff.dashboard';
    }
@endphp

<div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
    
    @role('staff')
        {{-- Header Section --}}
        <div class="mb-8">
            <div class="md:flex md:items-center md:justify-between mb-6">
                <div class="flex flex-row min-w-0 items-center gap-4">
                    <x-back-btn :route="$dashRoute" />
                    <div>
                        <h2 class="text-2xl font-bold leading-7 text-main sm:text-3xl sm:truncate tracking-tight">
                            {{ __('staff_pending_day_off.title') }}
                        </h2>
                        <p class="mt-1 text-sm text-muted-500">
                            {{ __('staff_pending_day_off.subtitle') ?? 'Manage your pending leave requests' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Flash Message --}}
            @if(session('success'))
                <div class="mb-6 rounded-xl bg-green-50 p-4 border border-green-200 animate-fade-in-up">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Requests Grid --}}
        <div class="relative z-10 grid grid-cols-1 @2xl:grid-cols-2 @4xl:grid-cols-3 gap-6 animate-fade-in-up [animation-delay:150ms]">
            {{-- Decorative blurry blob (Background) --}}
            <div class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-primary/5 rounded-full blur-3xl opacity-50 pointer-events-none -z-10"></div>

            @forelse($groupedRequests as $groupId => $group)
                @php
                    $firstReq = $group->first();
                    $isMultiple = $group->count() > 1;
                    $dates = $group->pluck('date')->sort();
                    $pillClass = match($firstReq->leave_type) {
                        'OFF_HALF' => 'bg-accent/10 text-accent ring-accent/20',
                        'OFF_FULL' => 'bg-secondary/10 text-secondary ring-secondary/20',
                        default => 'bg-muted-100 text-muted-600 ring-muted-500/10',
                    };
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-muted-200 hover:shadow-md hover:border-primary/30 transition-all duration-300 flex flex-col h-full group">
                    {{-- Card Header: User & Date --}}
                    <div class="px-6 py-5 border-b border-muted-100 flex justify-between items-start">
                        <div>
                            <span class="block text-xs font-bold text-muted-400 uppercase tracking-wider">
                                @if($isMultiple)
                                    {{ $dates->first()->format('M d') }} - {{ $dates->last()->format('M d, Y') }}
                                @else
                                    {{ $firstReq->date->format('M d, Y') }}
                                @endif
                            </span>
                            <span class="block text-lg font-bold text-main mt-1 group-hover:text-primary transition-colors">
                                {{ $firstReq->user->name }} ({{ $firstReq->user->username }})
                            </span>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pillClass }} ring-1 ring-inset">
                            {{ __('staff_pending_day_off.' . $firstReq->leave_type) }}
                            @if($isMultiple)
                                ({{ $group->count() }} days)
                            @endif
                        </span>
                    </div>

                    {{-- Card Body: Reason --}}
                    <div class="px-6 py-5 flex-grow">
                        <div class="text-sm text-muted-500">
                            <span class="block font-medium text-main mb-1">{{ __('staff_pending_day_off.reason_label') }}</span> 
                            <p class="line-clamp-3 leading-relaxed">
                                {{ $firstReq->reason ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    {{-- Card Footer: Actions --}}
                    @if(!$isMultiple)
                        <div class="bg-muted-50/50 px-6 py-4 border-t border-muted-100 rounded-b-2xl flex items-center justify-between gap-3">
                            
                            {{-- Approve Button --}}
                            <form action="{{ route('dayoff.approve', $firstReq->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-bold rounded-xl text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all active:scale-95 shadow-sm shadow-primary/25">
                                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    {{ __('staff_pending_day_off.accepted_btn') }}
                                </button>
                            </form>

                            {{-- Reject Button --}}
                            <form action="{{ route('dayoff.reject', $firstReq->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-muted-200 text-sm font-bold rounded-xl text-muted-600 bg-white hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-all active:scale-95">
                                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    {{ __('staff_pending_day_off.rejected_btn') }}
                                </button>
                            </form>

                        </div>
                    @else
                        {{-- Expandable Dates List --}}
                        <div class="bg-muted-50/50 border-t border-muted-100 rounded-b-2xl">
                            <button type="button" class="w-full px-6 py-3 text-left text-sm font-medium text-main hover:bg-muted-100/50 transition-colors flex items-center justify-between" onclick="toggleDates(this)">
                                <span>{{ __('staff_pending_day_off.view_dates') }} ({{ $group->count() }})</span>
                                <svg class="h-4 w-4 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="dates-list hidden px-6 pb-4 space-y-3">
                                @foreach($group as $req)
                                    <div class="flex items-center justify-between bg-white rounded-lg p-3 border border-muted-100">
                                        <span class="text-sm font-medium text-main">{{ $req->date->format('M d, Y') }}</span>
                                        <div class="flex gap-2">
                                            <form action="{{ route('dayoff.approve', $req->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-md text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all active:scale-95">
                                                    <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    {{ __('staff_pending_day_off.accepted_btn') }}
                                                </button>
                                            </form>
                                            <form action="{{ route('dayoff.reject', $req->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-md text-muted-600 bg-white border border-muted-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-all active:scale-95">
                                                    <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    {{ __('staff_pending_day_off.rejected_btn') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                {{-- Empty State --}}
                <div class="col-span-full py-16 flex flex-col items-center justify-center bg-white border-2 border-dashed border-muted-200 rounded-2xl">
                    <div class="p-4 rounded-full bg-muted-50 text-muted-400 mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <span class="block text-sm font-medium text-muted-500">
                        {{ __('staff_pending_day_off.no_request_pending') }}
                    </span>
                </div>
            @endforelse
        </div>

    @else
        {{-- No Permission State --}}
        <div class="flex flex-col items-center justify-center min-h-[400px] text-center">
            <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-main">{{ __('Error') }}</h3>
            <p class="text-muted-500 mt-2">{{ __('staff_pending_day_off.no_permission') }}</p>
        </div>
@endrole
</div>
@endsection

<script>
function toggleDates(button) {
    const list = button.nextElementSibling;
    const icon = button.querySelector('svg');
    list.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}
</script>