@extends('layout_dashboard')

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1"><i class="fas fa-brain text-primary"></i> LSTM Productivity Dashboard</h3>
            <p class="text-muted mb-0">AI-powered employee productivity insights</p>
        </div>
        <button id="refresh-predictions" class="btn btn-primary">
            <i class="fas fa-sync-alt"></i> Refresh All Predictions
        </button>
    </div>

    {{-- STATS CARDS --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card border-0 shadow-sm h-100">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-info">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">Last Model Run</h6>
                        <h4 class="mb-0" id="last-run-date">-</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card border-0 shadow-sm h-100">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-success">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">High Performers</h6>
                        <h4 class="mb-0 text-success" id="high-performers">-</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card border-0 shadow-sm h-100">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">At Risk Employees</h6>
                        <h4 class="mb-0 text-warning" id="at-risk-employees">-</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card border-0 shadow-sm h-100">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-0">Model Accuracy</h6>
                        <h4 class="mb-0 text-primary" id="model-accuracy">-</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTERS & CONTROLS --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" placeholder="Search employees..." id="employee-search">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="department-filter">
                <option value="">All Departments</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="risk-filter">
                <option value="">All Risk Levels</option>
                <option value="Low">Low Risk</option>
                <option value="Medium">Medium Risk</option>
                <option value="High">High Risk</option>
                <option value="Critical">Critical Risk</option>
            </select>
        </div>
        <div class="col-md-2">
            <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="viewMode" id="cards-view" value="cards" checked>
                <label class="btn btn-outline-primary" for="cards-view" title="Cards View">
                    <i class="fas fa-th-large"></i>
                </label>
                <input type="radio" class="btn-check" name="viewMode" id="table-view" value="table">
                <label class="btn btn-outline-primary" for="table-view" title="Table View">
                    <i class="fas fa-table"></i>
                </label>
            </div>
        </div>
    </div>

    {{-- RESULTS INFO --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted" id="employee-count">0 employees</span>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="items-per-page" style="width: auto;">
                <option value="12">12 per page</option>
                <option value="24">24 per page</option>
                <option value="48">48 per page</option>
                <option value="all">Show all</option>
            </select>
        </div>
    </div>

    {{-- EMPLOYEE CARDS --}}
    <div id="employees-cards" class="row">
        <!-- Employee cards will be rendered here -->
    </div>

    {{-- PAGINATION --}}
    <div id="pagination-cards" class="d-flex justify-content-center mt-4" style="display: none !important;">
        <nav>
            <ul class="pagination">
                <!-- Pagination will be rendered here -->
            </ul>
        </nav>
    </div>

    {{-- TABLE VIEW --}}
    <div id="employees-table" class="card border-0 shadow-sm" style="display: none;">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-users"></i> Employee Predictions</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
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

    {{-- PAGINATION TABLE --}}
    <div id="pagination-table" class="d-flex justify-content-center mt-4" style="display: none !important;">
        <nav>
            <ul class="pagination">
                <!-- Pagination will be rendered here -->
            </ul>
        </nav>
    </div>

    {{-- LOADING SPINNER --}}
    <div id="loading-spinner" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted mt-2">Loading employee data...</p>
    </div>

</div>

<style>
.stats-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    transition: transform 0.2s;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
    font-size: 20px;
}

.employee-card {
    background: white;
    border-radius: 12px;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
    height: 240px; /* Increased height to prevent overflow */
    display: flex;
    flex-direction: column;
    overflow: hidden; /* Prevent content overflow */
}

.employee-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.score-display {
    font-size: 1.4rem;
    font-weight: bold;
    text-align: center;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
}

.mini-chart {
    height: 22px;
    background: #e9ecef;
    border-radius: 15px;
    position: relative;
    overflow: hidden;
    margin: 8px 0;
}

.mini-chart::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: var(--progress, 50%);
    background: var(--chart-color, #007bff);
    border-radius: 15px;
    transition: width 0.8s ease;
}

.trend-indicator {
    font-size: 0.85em;
    font-weight: bold;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trend-up { color: #28a745; }
.trend-down { color: #dc3545; }
.trend-stable { color: #6c757d; }

.risk-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 50px;
    white-space: nowrap;
    flex-shrink: 0;
}

.employee-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(45deg, #007bff, #28a745);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.employee-name {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.employee-department {
    font-size: 0.75rem;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

.card-header-section {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    min-height: 35px;
}

.card-info-section {
    flex: 1;
    min-width: 0; /* Allow text truncation */
}

.card-scores-section {
    margin-bottom: 10px;
}

.card-chart-section {
    margin-bottom: 10px;
}

.card-footer-section {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 5px;
}

.card-date {
    font-size: 0.7rem;
    color: #6c757d;
    white-space: nowrap;
}

.pagination .page-link {
    color: #007bff;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}

/* Responsive adjustments */
@media (max-width: 1400px) {
    .employee-card {
        height: 220px;
    }
    .score-display {
        font-size: 1.3rem;
    }
}

@media (max-width: 1200px) {
    .employee-card {
        height: 200px;
    }
    .score-display {
        font-size: 1.2rem;
    }
    .employee-name {
        font-size: 0.85rem;
    }
}

@media (max-width: 992px) {
    .employee-card {
        height: 180px;
    }
    .score-display {
        font-size: 1.1rem;
    }
    .employee-name {
        font-size: 0.8rem;
    }
}

@media (max-width: 768px) {
    .stats-card {
        margin-bottom: 1rem;
    }
    .employee-card {
        height: 160px;
        margin-bottom: 1rem;
    }
    .score-display {
        font-size: 1rem;
    }
    .employee-name {
        font-size: 0.75rem;
    }
    .employee-department {
        font-size: 0.7rem;
    }
}

/* Animation for new cards */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.employee-card.fade-in {
    animation: fadeInUp 0.3s ease-out;
}

/* Keep table content inside the card and avoid overlap on long text */
#employees-table .table {
    width: 100%;
    table-layout: auto;
}

#employees-table .table th,
#employees-table .table td {
    vertical-align: middle;
}

#employees-table .table td:first-child,
#employees-table .table th:first-child {
    width: 260px;
    min-width: 220px;
}

#employees-table .table td:nth-child(2),
#employees-table .table th:nth-child(2) {
    width: 180px;
    min-width: 140px;
}

#employees-table .table td:last-child,
#employees-table .table th:last-child {
    width: 80px;
    text-align: center;
}

#employees-table .table .employee-name-cell,
#employees-table .table .department-cell {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

#employees-table .table .trend-cell {
    white-space: nowrap;
}

/* Ensure proper spacing between elements */
.d-flex.gap-2 {
    gap: 0.5rem !important;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>

{{-- MODAL FOR CHART --}}
<div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="chartModalLabel">
                    <i class="fas fa-chart-line text-primary"></i> Productivity History: <span id="chart-employee-name" class="fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="productivityChart"></canvas>
                </div>
            </div>
            <div class="modal-footer border-top">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> Blue line = Historical Performance | Green dashed line = LSTM Prediction
                </small>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="{{ asset('js/admin/lstm-dashboard.js') }}"></script>
@endpush
