@extends('layout_dashboard')

@section('content')
    @vite(['resources/js/admin/close_info_alert.js'])
    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }
    @endphp
    @role('admin')
    <x-action-layout :route="$dashRoute" :title="'profile.back_to_dashboard'">
        {{-- title --}}
        <h2 class="font-medium text-[28px] md:text-[32px]">{{ __('checkin_logs.title') }}</h2>

        {{-- info alert --}}
        <div id="info-alert" class="flex flex-col justify-between gap-3 py-3 px-4 text-sm md:text-base rounded-xl bg-blue-50 animate-fade-in-up [animation-delay:100ms]">
            <div class="flex justify-between w-full">
                <div class="flex gap-1 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                        class="w-5 h-5 fill-blue-500">
                        <path d="M320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM288 224C288 206.3 302.3 192 320 192C337.7 192 352 206.3 352 224C352 241.7 337.7 256 320 256C302.3 256 288 241.7 288 224zM280 288L328 288C341.3 288 352 298.7 352 312L352 400L360 400C373.3 400 384 410.7 384 424C384 437.3 373.3 448 360 448L280 448C266.7 448 256 437.3 256 424C256 410.7 266.7 400 280 400L304 400L304 336L280 336C266.7 336 256 325.3 256 312C256 298.7 266.7 288 280 288z"/>
                    </svg>
                    <strong>Check-in Policy:</strong>
                </div>
                <button id="close-info" type="button" class="py-1 px-1 rounded-full hover:bg-blue-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                        class="w-5 h-5">
                        <path d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"/>
                    </svg>
                </button>
            </div>
            <p>
                Employees are considered <span class="text-red-500">LATE</span> if they check in more than 5 minutes after
                the company start time.
                <span class="text-green-500">ON TIME</span> check-ins include a 5-minute grace period.
            </p>
        </div>

        <div class="flex flex-wrap gap-2 items-end animate-fade-in-up [animation-delay:150ms]">
            {{-- search bar + filter --}}
            <form class="flex flex-col gap-2" method="GET">
                <div class="flex flex-wrap gap-2">
                    <div class="flex flex-col gap-1">
                        <label>{{ __('checkin_logs.filter_label_from') }}</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="rounded-xl text-sm md:text-base border border-gray-300 px-4 h-[42px] placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label>{{ __('checkin_logs.filter_label_to') }}</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="rounded-xl text-sm md:text-base border border-gray-300 px-4 h-[42px] placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <input type="text" name="username" value="{{ request('username') }}"
                        class="rounded-xl text-sm md:text-base border border-gray-300 px-4 h-[42px] placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition"
                        placeholder="{{ __('checkin_logs.search_placeholder_username') }}">
                    <select name="status"
                        class="rounded-xl text-sm md:text-base border border-gray-300 px-4 h-[42px] placeholder-gray-400 hover:border-gray-400 focus:outline-none focus:border-[#5D3FD3] transition">
                        <option value="">{{ __('checkin_logs.filter_option_all_statuses') }}</option>
                        <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>
                            {{ __('checkin_logs.filter_option_late') }}
                        </option>
                        <option value="on_time" {{ request('status') == 'on_time' ? 'selected' : '' }}>
                            {{ __('checkin_logs.filter_option_on_time') }}
                        </option>
                    </select>
                    <div class="flex gap-2">
                        <!-- filter -->
                        <button type="submit" title="{{ __('tasks.filter') }}"
                            class="border px-3 h-[42px] rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                                <path
                                    d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z" />
                            </svg>
                        </button>
                        <!-- reset -->
                        <a href="{{ route('users.checkin_index') }}" title="{{ __('tasks.reset') }}"
                            class="flex items-center justify-center border px-3 h-[42px] rounded-xl border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                                <path
                                    d="M88 256L232 256C241.7 256 250.5 250.2 254.2 241.2C257.9 232.2 255.9 221.9 249 215L202.3 168.3C277.6 109.7 386.6 115 455.8 184.2C530.8 259.2 530.8 380.7 455.8 455.7C380.8 530.7 259.3 530.7 184.3 455.7C174.1 445.5 165.3 434.4 157.9 422.7C148.4 407.8 128.6 403.4 113.7 412.9C98.8 422.4 94.4 442.2 103.9 457.1C113.7 472.7 125.4 487.5 139 501C239 601 401 601 501 501C601 401 601 239 501 139C406.8 44.7 257.3 39.3 156.7 122.8L105 71C98.1 64.2 87.8 62.1 78.8 65.8C69.8 69.5 64 78.3 64 88L64 232C64 245.3 74.7 256 88 256z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </form>

            {{-- export excel --}}
            <form method="GET" action="{{ route('checkins.export') }}">
                <input type="hidden" name="username" value="{{ request('username') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                <button type="submit"
                    class="flex items-center justify-center border px-3 h-[42px] rounded-xl text-[#5D3FD3] border-gray-300 hover:border-[#6b4fda] hover:bg-[#F1EFFC] transition">{{ __('checkin_logs.export_button') }}</button>
            </form>
        </div>

        {{-- summary card --}}
        @php
            $totalCheckIns = $checkIns->total();
            $onTimeCheckIns = $checkIns->where('is_late', false)->where('check_in_time', '!=', null)->count();
            $lateCheckIns = $checkIns->where('is_late', true)->count();
            $latePercentage = $totalCheckIns > 0 ? round(($lateCheckIns / $totalCheckIns) * 100, 1) : 0;
        @endphp
        <div
            class="flex flex-col md:flex-row items-center justify-between gap-4 animate-fade-in-up [animation-delay:200ms]">
            <x-admin.check-in-summary-card textColor="text-blue-400" borderColor="border-blue-400" :number="$totalCheckIns"
                :title="__('checkin_logs.total_check_in')" />
            <x-admin.check-in-summary-card textColor="text-emerald-400" borderColor="border-emerald-400"
                :number="$onTimeCheckIns" :title="__('checkin_logs.on_time')" />
            <x-admin.check-in-summary-card textColor="text-rose-400" borderColor="border-rose-400" :number="$lateCheckIns"
                :title="__('checkin_logs.late_arrivals')" />
            <x-admin.check-in-summary-card textColor="text-amber-400" borderColor="border-amber-400"
                :number="$latePercentage" :title="__('checkin_logs.late_rate')" />
        </div>

        {{-- Table --}}
        <div class="flex flex-col gap-4 animate-fade-in-up [animation-delay:250ms]">
            <div class="flex items-center gap-2 text-sm md:text-base">
                <p>Quick view:</p>
                <div class="flex">
                    <a href="{{ request()->fullUrlWithQuery(['per_page' => 5]) }}"
                        class="flex items-center justify-center w-7 h-7 md:h-8 md:w-8 rounded-l-lg transition {{ request('per_page', 5) == 5 ? 'text-white bg-blue-500' : 'border border-blue-500 hover:bg-blue-100' }}">5</a>
                    <a href="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}"
                        class="flex items-center justify-center w-7 h-7 md:h-8 md:w-8 transition {{ request('per_page') == 10 ? 'text-white bg-blue-500' : 'border-b border-t border-blue-500 hover:bg-blue-100' }}">10</a>
                    <a href="{{ request()->fullUrlWithQuery(['per_page' => 25]) }}"
                        class="flex items-center justify-center w-7 h-7 md:h-8 md:w-8 rounded-r-lg transition {{ request('per_page') == 25 ? 'text-white bg-blue-500' : 'border border-blue-500 hover:bg-blue-100' }}">25</a>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-gray-300 shadow-[0_4px_40px_0_rgba(32,27,53,0.1)]">
                <table class="w-full">
                    <thead class="bg-gray-100 text-gray-500 w uppercase tracking-wide text-sm">
                        <tr>
                            <th class="py-3 pl-4 pr-3 text-left font-medium">ID</th>
                            <th class="py-3 px-3 text-left font-medium">{{ __('checkin_logs.table_header_user_name') }}</th>
                            <th class="py-3 px-3 text-left font-medium">{{ __('checkin_logs.table_header_date') }}</th>
                            <th class="py-3 px-3 text-left font-medium">{{ __('checkin_logs.table_header_check_in_time') }}</th>
                            <th class="py-3 px-3 text-left font-medium">{{ __('checkin_logs.table_header_check_out_time') }}</th>
                            <th class="py-3 px-3 text-left font-medium">{{ __('checkin_logs.table_header_working_hours') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-[#FDFDFF] text-sm">
                        @forelse($checkIns as $index => $log)
                            @php
                                // Prepare working hours display:
                                $computedWorking = null;

                                // If an explicit working_hours value exists, use it.
                                if (!empty($log->working_hours)) {
                                    $computedWorking = $log->working_hours;
                                } 

                                elseif (!empty($log->check_in_time) && !empty($log->check_out_time)) {
                                    try {
                                        // combine date + times to ensure correct diffs
                                        $in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->date . ' ' . $log->check_in_time,
                                        'Asia/Ho_Chi_Minh');
                                        $out = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->date . ' ' . $log->check_out_time,
                                        'Asia/Ho_Chi_Minh');

                                        // if checkout is next day, Carbon will handle diffInSeconds correctly
                                        $seconds = $out->diffInSeconds($in);
                                        $hours = intdiv($seconds, 3600);
                                        $minutes = intdiv($seconds % 3600, 60);
                                        $computedWorking = sprintf('%02d:%02d', $hours, $minutes);
                                    } 
                                    catch (\Exception $e) {
                                        $computedWorking = null;
                                    }
                                }
                            @endphp

                            <tr>
                                {{-- id --}}
                                <td class="py-3 pl-4 pr-3">
                                    <div class="max-w-xs truncate" title="{{ $checkIns->firstItem() + $index }}">{{ $checkIns->firstItem() + $index }}</div>
                                </td>

                                {{-- user name --}}
                                <td class="py-3 px-3">
                                    <div class="max-w-xs truncate" title="{{ $log->user_name }}">{{ $log->user_name }}</div>
                                </td>

                                {{-- date --}}
                                <td class="py-3 px-3">
                                    <div class="max-w-xs truncate" title="{{ $log->date }}">{{ $log->date }}</div>
                                </td>

                                {{-- login time + late --}}
                                <td class="py-3 px-3">
                                    <p class="flex gap-2 {{ $log->is_late ? 'text-red-500' : 'text-green-500' }}">
                                        @if ($log->check_in_time)
                                            {{ $log->check_in_time }}
                                            @if($log->is_late)
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                                    class="w-5 h-5 fill-red-500 align-middle">
                                                    <path
                                                        d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"
                                                    class="w-5 h-5 fill-green-500 align-middle">
                                                    <path d="M286 368C384.5 368 464.3 447.8 464.3 546.3C464.3 562.7 451 576 434.6 576L78 576C61.6 576 48.3 562.7 48.3 546.3C48.3 447.8 128.1 368 226.6 368L286 368zM585.7 169.9C593.5 159.2 608.5 156.8 619.2 164.6C629.9 172.4 632.3 187.4 624.5 198.1L522.1 338.9C517.9 344.6 511.4 348.3 504.4 348.7C497.4 349.1 490.4 346.5 485.5 341.4L439.1 293.4C429.9 283.9 430.1 268.7 439.7 259.5C449.2 250.3 464.4 250.6 473.6 260.1L500.1 287.5L585.7 169.8zM256.3 312C190 312 136.3 258.3 136.3 192C136.3 125.7 190 72 256.3 72C322.6 72 376.3 125.7 376.3 192C376.3 258.3 322.6 312 256.3 312z"/>
                                                </svg>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </p>
                                </td>

                                {{-- logout time --}}
                                <td class="py-3 px-3">
                                    <div class="max-w-xs truncate">
                                        @if ($log->check_out_time)
                                            {{ $log->check_out_time }}
                                        @else
                                            -
                                        @endif
                                    </div>
                                </td>

                                {{-- working hours --}}
                                <td class="py-3 px-3">
                                    <div class="max-w-xs truncate">
                                        @if (!empty($computedWorking))
                                            {{ $computedWorking }} hrs
                                        @elseif ($log->check_in_time && !$log->check_out_time)
                                            {{ __('checkin_logs.badge_checked_in') }}
                                        @else
                                            {{ __('checkin_logs.badge_not_checked_in') }}
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400">
                                    {{ __('checkin_logs.table_no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($checkIns->hasPages())
                    <div class="my-4 flex justify-center w-full">
                        {{ $checkIns->onEachSide(1)->withQueryString()->links('vendor.pagination.tailwind') }}
                    </div>
                @endif
            </div>
        </div>
    </x-action-layout>
    @endrole
@endsection
{{-- <div class="container py-4">
    <h2 class="mb-4 fw-bold">{{ __('checkin_logs.title') }}</h2>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle"></i>
        <strong>Check-in Policy:</strong>
        Employees are considered <span class="badge bg-danger">LATE</span> if they check in more than 5 minutes after
        the company start time.
        <span class="badge bg-success">ON TIME</span> check-ins include a 5-minute grace period.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Search + Filter -->
    <form method="GET" action="{{ route('users.checkin_index') }}" class="row g-3 align-items-end mb-4">
        <div class="col-md-2">
            <label class="form-label">Search User</label>
            <input type="text" name="username" value="{{ request('username') }}" class="form-control"
                placeholder="{{ __('checkin_logs.search_placeholder_username') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('checkin_logs.filter_label_from') }}</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('checkin_logs.filter_label_to') }}</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('checkin_logs.filter_label_status') }}</label>
            <select name="status" class="form-select">
                <option value="">{{ __('checkin_logs.filter_option_all_statuses') }}</option>
                <option value="late" {{ request('status')=='late' ? 'selected' : '' }}>{{
                    __('checkin_logs.filter_option_late') }}</option>
                <option value="on_time" {{ request('status')=='on_time' ? 'selected' : '' }}>{{
                    __('checkin_logs.filter_option_on_time') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Rows per page</label>
            <select name="per_page" class="form-select">
                <option value="5" {{ request('per_page', 5)==5 ? 'selected' : '' }}>5 rows</option>
                <option value="10" {{ request('per_page')==10 ? 'selected' : '' }}>10 rows</option>
                <option value="15" {{ request('per_page')==15 ? 'selected' : '' }}>15 rows</option>
                <option value="25" {{ request('per_page')==25 ? 'selected' : '' }}>25 rows</option>
                <option value="50" {{ request('per_page')==50 ? 'selected' : '' }}>50 rows</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">
                <i class="bi bi-search"></i> {{ __('checkin_logs.filter_button') }}
            </button>
        </div>
    </form>

    <!-- Summary Cards -->
    @php
    $totalCheckIns = $checkIns->total();
    $lateCheckIns = $checkIns->where('is_late', true)->count();
    $onTimeCheckIns = $checkIns->where('is_late', false)->where('check_in_time', '!=', null)->count();
    $latePercentage = $totalCheckIns > 0 ? round(($lateCheckIns / $totalCheckIns) * 100, 1) : 0;
    @endphp

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary">{{ $totalCheckIns }}</h5>
                    <p class="card-text">Total Check-ins</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">{{ $onTimeCheckIns }}</h5>
                    <p class="card-text">On Time</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">{{ $lateCheckIns }}</h5>
                    <p class="card-text">Late Arrivals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">{{ $latePercentage }}%</h5>
                    <p class="card-text">Late Rate</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Check-in Records</h6>
            <div class="d-flex align-items-center">
                <small class="text-muted me-3">Quick view:</small>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ request()->fullUrlWithQuery(['per_page' => 5]) }}"
                        class="btn {{ request('per_page', 5) == 5 ? 'btn-primary' : 'btn-outline-primary' }}">5</a>
                    <a href="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}"
                        class="btn {{ request('per_page') == 10 ? 'btn-primary' : 'btn-outline-primary' }}">10</a>
                    <a href="{{ request()->fullUrlWithQuery(['per_page' => 25]) }}"
                        class="btn {{ request('per_page') == 25 ? 'btn-primary' : 'btn-outline-primary' }}">25</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-hover table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('checkin_logs.table_header_number') }}</th>
                        <th>{{ __('checkin_logs.table_header_user_name') }}</th>
                        <th>{{ __('checkin_logs.table_header_date') }}</th>
                        <th>{{ __('checkin_logs.table_header_check_in_time') }}</th>
                        <th>{{ __('checkin_logs.table_header_check_out_time') }}</th>
                        <th>{{ __('checkin_logs.table_header_working_hours') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checkIns as $index => $log)
                    @php
                    // Prepare working hours display:
                    $computedWorking = null;

                    // If an explicit working_hours value exists, use it.
                    if (!empty($log->working_hours)) {
                    $computedWorking = $log->working_hours;
                    } elseif (!empty($log->check_in_time) && !empty($log->check_out_time)) {
                    try {
                    // combine date + times to ensure correct diffs
                    $in = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->date . ' ' . $log->check_in_time,
                    'Asia/Ho_Chi_Minh');
                    $out = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->date . ' ' . $log->check_out_time,
                    'Asia/Ho_Chi_Minh');

                    // if checkout is next day, Carbon will handle diffInSeconds correctly
                    $seconds = $out->diffInSeconds($in);
                    $hours = intdiv($seconds, 3600);
                    $minutes = intdiv($seconds % 3600, 60);
                    $computedWorking = sprintf('%02d:%02d', $hours, $minutes);
                    } catch (\Exception $e) {
                    $computedWorking = null;
                    }
                    }
                    @endphp

                    <tr>
                        <td>{{ $checkIns->firstItem() + $index }}</td>
                        <td>{{ $log->user_name }}</td>
                        <td>{{ $log->date }}</td>

                        <!-- Check In Time + Badges -->
                        <td>
                            @if ($log->check_in_time)
                            <span class="{{ $log->is_late ? 'text-danger fw-bold' : 'text-success' }}">
                                {{ $log->check_in_time }}
                            </span>

                            @if ($log->is_late)
                            <span class="badge bg-danger ms-1">
                                <i class="bi bi-clock"></i> LATE
                            </span>
                            @else
                            <span class="badge bg-success ms-1">
                                <i class="bi bi-check-circle"></i> ON TIME
                            </span>
                            @endif
                            @else
                            <span class="text-muted">-</span>
                            @endif

                            @if (!empty($log->is_half_day_off) && $log->is_half_day_off)
                            <span class="badge bg-info text-dark ms-1">Half Day Off</span>
                            @elseif (!empty($log->is_full_day_off) && $log->is_full_day_off)
                            <span class="badge bg-secondary ms-1">{{ __('checkin_logs.badge_full_day_off') }}</span>
                            @endif
                        </td>

                        <!-- Check Out Time -->
                        <td>
                            @if ($log->check_out_time)
                            {{ $log->check_out_time }}
                            @else
                            -
                            @endif
                        </td>

                        <!-- Working Hours -->
                        <td>
                            @if (!empty($computedWorking))
                            <span class="badge bg-primary">
                                {{ $computedWorking }} hrs
                            </span>
                            @elseif ($log->check_in_time && !$log->check_out_time)
                            <span class="badge bg-warning text-dark">{{ __('checkin_logs.badge_checked_in') }}</span>
                            @else
                            <span class="badge bg-secondary">{{ __('checkin_logs.badge_not_checked_in') }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">{{ __('checkin_logs.table_no_records') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination Info & Links -->
            <div class="row mt-3 align-items-center">
                <div class="col-md-6">
                    <div class="pagination-info">
                        <small class="text-muted">
                            Showing {{ $checkIns->firstItem() ?? 0 }} to {{ $checkIns->lastItem() ?? 0 }}
                            of {{ $checkIns->total() }} results
                        </small>
                    </div>
                </div>
                <div class="col-md-6 d-flex justify-content-end">
                    {{ $checkIns->withQueryString()->links() }}
                </div>
            </div>

            <!-- Export -->
            <form method="GET" action="{{ route('checkins.export') }}">
                <input type="hidden" name="username" value="{{ request('username') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                <button type="submit" class="btn btn-success mt-2">{{ __('checkin_logs.export_button') }}</button>
            </form>
        </div>
    </div>
</div>
@else
<div class="container py-4">
    <h4 class="text-danger">{{ __('checkin_logs.access_denied_title') }}</h4>
    <p>{{ __('checkin_logs.access_denied_message') }}</p>
</div>
@endrole
@endsection

<style>
    /* Highlight late check-ins */
    .text-danger.fw-bold {
        background-color: #ffe6e6;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #f5c6cb;
    }

    /* On-time styling */
    .text-success {
        background-color: #e6ffe6;
        padding: 2px 6px;
        border-radius: 4px;
        border: 1px solid #c3e6cb;
    }

    /* Badge animations */
    .badge {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.8);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Late row highlighting */
    tr:has(.badge.bg-danger) {
        background-color: #fff5f5 !important;
    }

    /* Hover effects */
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
    }

    .table-hover tbody tr:has(.badge.bg-danger):hover {
        background-color: #ffebee !important;
    }

    /* Pagination info styling */
    .pagination-info {
        display: flex;
        align-items: center;
        height: 100%;
    }

    /* Improve form layout */
    .row.g-3 .col-md-2 label {
        font-size: 0.875rem;
        font-weight: 500;
    }

    /* Per page selector styling */
    select[name="per_page"] {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
    }
</style> --}}