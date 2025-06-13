{{-- resources/views/settings.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">Account Settings</h1>

    <div class="row">
        <div class="col-lg-6">

            <!-- Update Name Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Update Name</h6>
                </div>
                <div class="card-body">

                    {{-- Success Message --}}
                    @if (session('success_name'))
                        <div class="alert alert-success">
                            {{ session('success_name') }}
                        </div>
                    @endif

                    {{-- Error Messages --}}
                    @if ($errors->has('first_name') || $errors->has('last_name'))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->get('first_name') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                                @foreach ($errors->get('last_name') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.update.name') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control"
                                   value="{{ old('first_name', auth()->user()->first_name) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control"
                                   value="{{ old('last_name', auth()->user()->last_name) }}" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                Save Name
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Update Avatar Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Update Profile Picture</h6>
                </div>
                <div class="card-body">

                    {{-- Success Message --}}
                    @if (session('success_avatar'))
                        <div class="alert alert-success">
                            {{ session('success_avatar') }}
                        </div>
                    @endif

                    {{-- Error Message --}}
                    @if ($errors->has('avatar'))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->get('avatar') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.update.avatar') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="avatar">Profile Photo</label>
                            <input type="file" name="avatar" id="avatar" accept=".jpg,.jpeg,.png" class="form-control-file">
                            @if(auth()->user()->avatar)
                                <div class="mt-2">
                                    <img src="{{ asset('img/user_avatar/' . auth()->user()->avatar) }}"
                                         alt="User Avatar" class="img-thumbnail" style="max-width: 120px;">
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                Upload Photo
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Password Reset -->
            <div class="text-center">
                <a href="{{ route('password.request') }}" class="btn btn-outline-secondary">
                    Reset Password
                </a>
            </div>

        </div>
    </div>

</div>
@endsection