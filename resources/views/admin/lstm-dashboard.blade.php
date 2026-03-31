@extends('layout_dashboard')
@section('title', 'LSTM Productivity Predictions')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-brain me-2"></i>LSTM Productivity Analytics
                </h1>
                <p class="text-muted">AI-powered employee productivity predictions and insights</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Last Prediction Run
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="last-run-date">
                                Loading...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                High Performers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="high-performers">
                                Loading...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                At Risk Employees
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="at-risk-employees">
                                Loading...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Model Accuracy
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="model-accuracy">
                                87.3%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Productivity Trend Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line me-2"></i>Productivity Trend Predictions
                    </h6>
                    <div class="dropdown no-arrow">
                        <select id="timeframe-select" class="form-select form-select-sm">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="productivityTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribution Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Performance Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart"></canvas>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> High (80-100%)
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> Medium (60-80%)
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> Low (40-60%)
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-danger"></i> Critical (<40%)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Predictions Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users me-2"></i>Individual Employee Predictions
                    </h6>
                    <button id="refresh-predictions" class="btn btn-primary btn-sm">
                        <i class="fas fa-sync-alt"></i> Refresh Predictions
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="predictions-table" class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Current Score</th>
                                    <th>Predicted Score</th>
                                    <th>Trend</th>
                                    <th>Risk Level</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="predictions-table-body">
                                <!-- Data will be loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Model Performance Metrics -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cogs me-2"></i>Model Performance Metrics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-success" id="model-mse">0.0342</h4>
                                <p class="text-muted mb-0">Mean Squared Error</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-info" id="model-mae">0.1567</h4>
                                <p class="text-muted mb-0">Mean Absolute Error</p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-warning" id="training-samples">2,847</h4>
                                <p class="text-muted mb-0">Training Samples</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-primary" id="last-training">Mar 15, 2026</h4>
                                <p class="text-muted mb-0">Last Retrained</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-robot me-2"></i>Automated Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Monthly Prediction Run</span>
                            <span class="badge bg-success rounded-pill">Enabled</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Email Alerts for At-Risk Employees</span>
                            <span class="badge bg-success rounded-pill">Active</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Auto Model Retraining</span>
                            <span class="badge bg-warning rounded-pill">Quarterly</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Dashboard Data Sync</span>
                            <span class="badge bg-info rounded-pill">Real-time</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<!-- LSTM Dashboard JavaScript -->
<script src="{{ asset('js/admin/lstm-dashboard.js') }}"></script>
@endsection