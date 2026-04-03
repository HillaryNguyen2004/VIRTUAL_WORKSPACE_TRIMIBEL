@extends('layout_dashboard')

@section('content')
<div class="container">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>LSTM Productivity Dashboard</h4>

        {{-- ✅ FIX: REFRESH ALL BUTTON --}}
        <button id="refresh-predictions" class="btn btn-primary">
            <i class="fas fa-sync-alt"></i> Refresh All Predictions
        </button>
    </div>

    {{-- STATS --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3">
                <h6>Last Run</h6>
                <h4 id="last-run-date">-</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <h6>High Performers</h6>
                <h4 id="high-performers">-</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <h6>At Risk</h6>
                <h4 id="at-risk-employees">-</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <h6>Model Accuracy</h6>
                <h4 id="model-accuracy">-</h4>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <div class="card-header">
            Employee Predictions
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Dept</th>
                        <th>Current</th>
                        <th>Predicted</th>
                        <th>Trend</th>
                        <th>Risk</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="predictions-table-body"></tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/lstm-dashboard.js') }}"></script>
@endpush
