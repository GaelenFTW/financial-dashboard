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
        }

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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            {{-- ✅ Brand + Logo --}}
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <img src="{{ asset('images/pt_ciputra.png') }}" alt="Company Logo">
                <span>Financial Dashboard</span>
            </a>

            {{-- ✅ Responsive toggle for mobile --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- ✅ Right-aligned menu --}}
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
                        {{-- 🧠 Hide Login/Register if on login or register page --}}
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

    <div class="container">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
