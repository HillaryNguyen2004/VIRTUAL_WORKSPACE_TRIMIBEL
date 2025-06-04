@extends('layouts.app')

@section('content')
<div class="container d-flex justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="card shadow" style="width: 100%; max-width: 400px;">
        <div class="card-body">
            <h4 class="card-title mb-3 text-center">Forgot Password</h4>
            <p class="mb-4 text-center">Enter your email address and we'll send you a link to reset your password.</p>
            @if (session('status'))
                <div class="alert alert-success text-center">
                    {{ session('status') }}
                </div>
            @endif
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Email" required autofocus>
                    @error('email')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>
            <div class="text-center mt-3">
                <a href="{{ route('login') }}">Back to Login</a>
            </div>
        </div>
    </div>
</div>
@endsection