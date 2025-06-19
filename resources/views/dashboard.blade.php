@extends('layout_dashboard')
@section('title', 'Dashboard')
@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
        </a>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Earnings (Monthly)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$40,000</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings (Annual) Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Earnings (Annual)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$215,000</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tasks</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">50%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 50%"
                                            aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Requests Card Example -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Requests
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">18</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Info Section -->
    <div class="row">
        <!-- Team Leader -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    Team Leader
                </div>
                <div class="card-body">
                    @if($teamLeader)
                        <p><strong>Name:</strong> {{ $teamLeader->name }}</p>
                        <p><strong>Email:</strong> {{ $teamLeader->email }}</p>
                    @else
                        <p class="text-muted">No team leader assigned.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Team Members -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    Team Members
                </div>
                <div class="card-body">
                    @if($teamMembers->count())
                        <ul class="list-group">
                            @foreach($teamMembers as $member)
                                <li class="list-group-item">
                                    {{ $member->name }} ({{ $member->email }})
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">No other team members.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>


    <!-- Content Row -->
    <div class="row">
        <!-- Content Column -->
        <div class="col-lg-6 mb-4">
            <!-- Project Card Example -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Assigned Projects</h6>
                </div>
                <div class="card-body">
                    @if($assignedTasks->count())
                        @foreach($assignedTasks as $task)
                            <h5 class="small font-weight-bold">{{ $task->title }} 
                                <span class="float-right">{{ ucfirst($task->status) }}</span>
                            </h5>
                            <div class="progress mb-4">
                                <div 
                                    class="progress-bar 
                                        @if($task->status === 'pending') bg-warning 
                                        @elseif($task->status === 'in_progress') bg-info 
                                        @elseif($task->status === 'completed') bg-success 
                                        @else bg-secondary 
                                        @endif"
                                    role="progressbar" 
                                    style="width:
                                        @if($task->status === 'pending') 20%
                                        @elseif($task->status === 'in_progress') 60%
                                        @elseif($task->status === 'completed') 100%
                                        @else 0%
                                        @endif"
                                    aria-valuenow="
                                        @if($task->status === 'pending') 20
                                        @elseif($task->status === 'in_progress') 60
                                        @elseif($task->status === 'completed') 100
                                        @else 0
                                        @endif"
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No projects assigned to you.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->
@endsection