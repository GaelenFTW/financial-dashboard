@extends('layouts.app')

@section('title', 'Register')

@section('content')
<style>

    .auth-card {
        position: relative;
        z-index: 1;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="card auth-card border-0" style="width: 420px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-success">Create Account</h3>
                <p class="text-muted mb-0">Register to access your dashboard</p>
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

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Full Name</label>
                    <input id="name" type="text" name="name" class="form-control form-control-lg" required autofocus placeholder="John Doe">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <input id="email" type="email" name="email" class="form-control form-control-lg" required placeholder="name@company.com">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input id="password" type="password" name="password" class="form-control form-control-lg" required placeholder="••••••••">
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control form-control-lg" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-success w-100 btn-lg fw-semibold">
                    Register
                </button>

                <div class="text-center mt-4">
                    <p class="mb-0 text-muted">Already have an account?
                        <a href="{{ route('login.form') }}" class="text-success fw-semibold text-decoration-none">Sign In</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
