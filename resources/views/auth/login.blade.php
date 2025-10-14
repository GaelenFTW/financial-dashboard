@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="card shadow-lg border-0 rounded-4" style="width: 420px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-primary">Welcome Back</h3>
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
                    <input id="email" type="email" name="email" class="form-control form-control-lg" required autofocus placeholder="name@company.com">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input id="password" type="password" name="password" class="form-control form-control-lg" required placeholder="••••••••">
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="remember">
                        <label class="form-check-label small" for="remember">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="small text-decoration-none text-primary">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg fw-semibold">
                    Sign In
                </button>

                <div class="text-center mt-4">
                    <p class="mb-0 text-muted">Don’t have an account?
                        <a href="{{ route('register.form') }}" class="text-primary fw-semibold text-decoration-none">Register</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
