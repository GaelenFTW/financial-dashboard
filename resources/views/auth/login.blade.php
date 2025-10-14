@extends('layouts.app')

@section('title', 'Login')

@section('content')
<style>
    body, html {
        height: 100%;
        margin: 0;
    }

    .auth-page {
        position: relative;
        height: 100vh;
        width: 100%;
        background: url('{{ asset('images/background.jpg') }}') no-repeat center center fixed;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
    }


    /* The centered card */
    .auth-card {
        position: relative;
        z-index: 1;
        width: 420px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.15);
        padding: 2rem;
    }

    .auth-card h3 {
        font-weight: 700;
    }

    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .25);
    }
</style>

<div class="auth-page">
    <div class="auth-card">
        <div class="text-center mb-4">
            <h3 class="text-primary">Welcome Back</h3>
            <p class="text-muted mb-0">Sign in to continue to your dashboard</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger rounded-3 py-2">
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <input id="email" type="email" name="email" class="form-control form-control-lg" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input id="password" type="password" name="password" class="form-control form-control-lg" required>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label small" for="remember">Remember me</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg fw-semibold">Sign In</button>
            <div class="text-center mt-4">
                <p class="mb-0 text-muted">
                    Don’t have an account?
                    <a href="{{ route('register.form') }}" class="text-primary fw-semibold text-decoration-none">Register</a>
                </p>
            </div>
        </form>
    </div>
</div>
@endsection
