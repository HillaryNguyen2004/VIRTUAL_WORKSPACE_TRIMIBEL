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
                <div class="mb-3 position-relative">
                    <input type="password" name="password" class="form-control" placeholder="New Password" id="password">
                    <span class="position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;" onclick="togglePassword('password', 'togglePasswordIcon')">
                        <i id="togglePasswordIcon" class="fa fa-eye"></i>
                    </span>
                </div>
                <div class="mb-3 position-relative">
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password" id="password_confirmation">
                    <span class="position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;" onclick="togglePassword('password_confirmation', 'togglePasswordIcon2')">
                        <i id="togglePasswordIcon2" class="fa fa-eye"></i>
                    </span>
                </div>
                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            </form>
            <div class="text-center mt-3">
                <a href="{{ route('login') }}">Back to Login</a>
            </div>
        </div>
    </div>
</div>
<script>
function togglePassword(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
@endsection