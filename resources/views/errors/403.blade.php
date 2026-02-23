@extends('layout_dashboard')

@section('title', '403 Forbidden')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        // Determine dashboard route based on role
        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('subadmin') && Route::has('subadmin.dashboard')) {
            $dashRoute = 'subadmin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        } elseif (auth()->user()->hasRole('substaff') && Route::has('substaff.dashboard')) {
            $dashRoute = 'substaff.dashboard';
        }
    @endphp
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-xl bg-white border border-muted-300 rounded-2xl shadow-lg shadow-main/5 p-8 text-center">
            <div class="mx-auto mb-5 w-16 h-16 rounded-full bg-danger/10 text-danger flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-7.938 4h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L2.33 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <p class="text-sm font-semibold text-danger tracking-wide uppercase">403</p>
            <h1 class="mt-2 text-2xl md:text-3xl font-bold text-main">Access denied</h1>
            <p class="mt-3 text-muted-500">
                You do not have permission to access this page.
            </p>

            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route($dashRoute) }}"
                   class="px-4 py-2 rounded-xl bg-primary text-white hover:bg-primary-hover transition">
                    Go to dashboard
                </a>
            </div>
        </div>
    </div>
@endsection
