@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
@role('admin')
<div class="container py-4">
    <h2 class="mb-4 fw-bold">{{ __('checkin_logs.title') }}</h2>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle"></i>
        <strong>Check-in Policy:</strong> 
        Employees are considered <span class="badge bg-danger">LATE</span> if they check in more than 5 minutes after the company start time.
        <span class="badge bg-success">ON TIME</span> check-ins include a 5-minute grace period.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Search + Filter -->
    <form method="GET" action="{{ route('users.checkin_index') }}" class="row g-3 align-items-end mb-4">
        <div class="col-md-2">
            <label class="form-label">Search User</label>
            <input type="text" name="username" value="{{ request('username') }}" class="form-control" placeholder="{{ __('checkin_logs.search_placeholder_username') }}">
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
                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>{{ __('checkin_logs.filter_option_late') }}</option>
                <option value="on_time" {{ request('status') == 'on_time' ? 'selected' : '' }}>{{ __('checkin_logs.filter_option_on_time') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Rows per page</label>
            <select name="per_page" class="form-select">
                <option value="5" {{ request('per_page', 5) == 5 ? 'selected' : '' }}>5 rows</option>
                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 rows</option>
                <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15 rows</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 rows</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 rows</option>
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
                                    $in  = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->date . ' ' . $log->check_in_time, 'Asia/Ho_Chi_Minh');
                                    $out = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $log->date . ' ' . $log->check_out_time, 'Asia/Ho_Chi_Minh');

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

                                {{-- Day-off badges shown alongside check-in --}}
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
                                    {{-- If computedWorking is in "HH:MM" format show nicely --}}
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
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
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
</style>
