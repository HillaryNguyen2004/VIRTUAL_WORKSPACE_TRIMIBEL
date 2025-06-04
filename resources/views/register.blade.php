@extends('layout_login')

@section('title', 'Register')

@section('content')
<div class="w-100" style="max-width: 500px; margin: 0 auto;">
    <h2 class="text-center mb-4">Create an Account!</h2>

    {{-- Success & Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('register.post') }}">
        @csrf
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <input type="text" name="first_name" class="form-control" placeholder="First Name" value="{{ old('first_name') }}">
                @error('first_name')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <input type="text" name="last_name" class="form-control" placeholder="Last Name" value="{{ old('last_name') }}">
                @error('last_name')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email Address" value="{{ old('email') }}">
            @error('email')
                <span class="text-danger small">{{ $message }}</span>
            @enderror
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password">
                @error('password')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat Password">
                @error('password_confirmation')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-3">Register Account</button>
        <div class="d-flex flex-column gap-2">
            <a href="#" class="btn btn-danger w-100 mb-2">
                <i class="fab fa-google fa-fw"></i> Register with Google
            </a>
            <a href="#" class="btn btn-primary w-100">
                <i class="fab fa-facebook-f fa-fw"></i> Register with Facebook
            </a>
        </div>
    </form>
    <div class="text-center mt-3">
        <a class="small" href="{{ route('login') }}">Already have an account? Login!</a>
    </div>
</div>
@endsection