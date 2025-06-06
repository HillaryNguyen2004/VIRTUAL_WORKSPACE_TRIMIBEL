@extends('layout_login')
@section('title', 'Login')
@section('content')
<div class="w-100" style="max-width: 400px; margin: 0 auto;">
    <h2 class="text-center mb-4">Login</h2>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email" required autofocus>
        </div>
        <div class="form-group mb-3 position-relative">
            <input type="password" name="password" class="form-control" placeholder="Password" id="password" required>
            <span class="position-absolute" style="top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;" onclick="togglePassword()">
                <i id="togglePasswordIcon" class="fa fa-eye"></i>
            </span>
        </div>

        <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
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
        <div class="form-group form-check mb-3">
            <input type="checkbox" name="remember" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Remember Me</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    <div class="text-center mt-3">
        <div class="text-center mt-3">
            <a href="{{ route('google.login') }}" class="btn btn-danger w-100 mb-2">
                <i class="fab fa-google"></i> Login with Google
            </a>
        </div>
        <div>
            <a href="{{ route('register') }}">Don't have an account yet? Register here</a>
        </div>
        <div>
            <a href="{{ route('password.request') }}">Forgot Password?</a>
        </div>
        
    </div>
</div>
@endsection