@extends('layouts.app') <!-- Assuming you have a layout -->

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">User Profile</h1>

    <div class="row">
        <div class="col-lg-8">
            <!-- Profile Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Profile Details</h6>
                    <a href="{{ route('settings') }}" class="btn btn-sm btn-primary">Edit Profile</a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3 font-weight-bold text-gray-600">Full Name:</div>
                        <div class="col-sm-9">{{ Auth::user()->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 font-weight-bold text-gray-600">Email:</div>
                        <div class="col-sm-9">{{ Auth::user()->email }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 font-weight-bold text-gray-600">Joined:</div>
                        <div class="col-sm-9">{{ Auth::user()->created_at->format('F d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Optional: User Avatar -->
        <div class="col-lg-4">
            <div class="card shadow mb-4 text-center">
                <div class="card-body">
                    <img src="{{ Auth::user()->avatar ?? asset('img/undraw_profile_2.svg') }}" class="img-fluid rounded-circle mb-3" style="width: 150px;" alt="User Avatar">
                    <h5 class="text-primary">{{ Auth::user()->name }}</h5>
                    <p class="text-muted mb-0">{{ Auth::user()->email }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
