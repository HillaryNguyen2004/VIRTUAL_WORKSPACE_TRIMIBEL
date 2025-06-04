@extends('layouts.app')

@section('content')
<div class="container d-flex justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="card shadow" style="width: 100%; max-width: 400px;">
        <div class="card-body">
            <h4 class="card-title mb-3 text-center">Reset Password</h4>
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" value="{{ $email ?? old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="New Password" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            </form>
            <div class="text-center mt-3">
                <a href="{{ route('login') }}">Back to Login</a>
            </div>
        </div>
    </div>
</div>
@endsection