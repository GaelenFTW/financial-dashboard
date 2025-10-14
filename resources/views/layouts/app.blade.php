<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            margin: 0;
        }

        /* 🌄 Background for login/register */
        body.auth-bg {
            background: url('{{ asset('images/cws.png') }}') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }

        /* 🩶 Semi-opaque overlay only on auth pages */
        body.auth-bg::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.60);
            z-index: 0;
        }

        /* 🧊 Navbar */
        .navbar-brand img {
            height: 36px;
            margin-right: 10px;
            vertical-align: middle;
        }

        .navbar-brand span {
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .btn-outline-light:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .navbar {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        /* 💡 Auth card styling */
        .auth-card {
            position: relative;
            z-index: 1; /* ensures it's above the white overlay */
            backdrop-filter: blur(6px);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-radius: 1rem;
        }

        /* Full height container for auth pages */
        .auth-container {
            min-height: calc(100vh - 56px);
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body class="@if(request()->routeIs('login.form') || request()->routeIs('register.form')) auth-bg @endif">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <img src="{{ asset('images/logo-ciputra.png') }}" alt="Company Logo">
                <span>Financial Dashboard</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
                <ul class="navbar-nav align-items-center">
                    @auth
                        <li class="nav-item me-2">
                            <span class="nav-link text-white-50">Hi, {{ auth()->user()->name }}</span>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-light btn-sm px-3">Logout</button>
                            </form>
                        </li>
                    @endauth

                    @guest
                        @if (!request()->routeIs('login.form') && !request()->routeIs('register.form'))
                            <li class="nav-item">
                                <a href="{{ route('login.form') }}" class="btn btn-outline-light btn-sm me-2 px-3">Login</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('register.form') }}" class="btn btn-light btn-sm px-3 fw-semibold">Register</a>
                            </li>
                        @endif
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    {{-- Use full screen container only for auth pages --}}
    @if (request()->routeIs('login.form') || request()->routeIs('register.form'))
        <div class="auth-container">
            @yield('content')
        </div>
    @else
        <div class="container my-4">
            @yield('content')
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
