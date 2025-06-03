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
        <div class="form-group mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <div class="form-group form-check mb-3">
            <input type="checkbox" name="remember" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Remember Me</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>
@endsection