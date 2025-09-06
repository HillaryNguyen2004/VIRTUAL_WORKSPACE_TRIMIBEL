@extends('layouts.app')

@section('header')
    @include('partials.headers.admin')
@endsection

@section('content')
@role('admin')
<div class="container py-4">
    <h2 class="mb-4 fw-bold">{{ __('checkin_logs.title') }}</h2>

    <!-- Search + Filter -->
    <form method="GET" action="{{ route('users.checkin_index') }}" class="row g-3 align-items-end mb-4">
        <div class="col-md-2">
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
        <div class="col-md-3">
            <label class="form-label">{{ __('checkin_logs.filter_label_status') }}</label>
            <select name="status" class="form-select">
                <option value="">{{ __('checkin_logs.filter_option_all_statuses') }}</option>
                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>{{ __('checkin_logs.filter_option_late') }}</option>
                <option value="on_time" {{ request('status') == 'on_time' ? 'selected' : '' }}>{{ __('checkin_logs.filter_option_on_time') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit">
                <i class="bi bi-search"></i> {{ __('checkin_logs.filter_button') }}
            </button>
        </div>
    </form>

    <!-- Table -->
    <div class="card shadow-sm">
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
                                    <span class="{{ $log->is_late ? 'text-danger fw-bold' : '' }}">
                                        {{ $log->check_in_time }}
                                    </span>
                                @else
                                    -
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

            <!-- Pagination -->
            <div class="mt-3">
                {{ $checkIns->withQueryString()->links() }}
            </div>

            <!-- Export -->
            <form method="GET" action="{{ route('checkins.export') }}">
                <input type="hidden" name="username" value="{{ request('username') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
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
