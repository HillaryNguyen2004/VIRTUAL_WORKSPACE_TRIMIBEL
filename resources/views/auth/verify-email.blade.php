@extends('layouts.app')

@section('content')
<div class="container">
    <div class="alert alert-info mt-4">
        Please check your email for a verification link before continuing.
    </div>
    @if (session('resent'))
        <div class="alert alert-success" role="alert">
            A fresh verification link has been sent to your email address.
        </div>
    @endif
    <form class="d-inline" method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">Resend verification email</button>.
    </form>
</div>
@endsection