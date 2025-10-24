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
        }

        /* 🌄 Background only for login/register pages */
        body.auth-bg {
            background: url('{{ asset('images/cws.png') }}') no-repeat center center fixed;
            background-size: cover;
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
        .navbar {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .auth-card {
            backdrop-filter: blur(6px);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 1rem;
        }

        /* Opaque overlay for login/register pages */
        @if (request()->routeIs('login.form') || request()->routeIs('register.form'))
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.45);
            z-index: -1;
        }
        @endif
    </style>
</head>

<body class="@if(request()->routeIs('login.form') || request()->routeIs('register.form')) auth-bg @endif">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <!-- Left: Logo -->
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <img src="{{ asset('images/logo-ciputra.png') }}" alt="Company Logo">
                <span>Financial Dashboard</span>
            </a>

            <!-- Left: Dropdown Menu -->
            @auth
            @php
                $nav = app(\App\Http\Controllers\NavigationController::class);
                $isG1 = $nav->userHasGroup(auth()->id(), 1);
                $isG2 = $nav->userHasGroup(auth()->id(), 2);
            @endphp

            @if($isG1 || $isG2)
            <ul class="navbar-nav ms-3">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="menuDropdown"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        ☰ Menu
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="menuDropdown">
                        @if($isG1)
                            {{-- Group 1: full menu --}}
                            <li><h6 class="dropdown-header">Payments</h6></li>
                            <li><a class="dropdown-item" href="{{ route('payments.view') }}">View</a></li>
                            <li><a class="dropdown-item" href="{{ route('payments.upload') }}">Upload</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('management.report') }}">Management Report</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Purchase Letters</h6></li>
                            <li><a class="dropdown-item" href="{{ route('purchase_letters.index') }}">Table</a></li>
                            <li><a class="dropdown-item" href="{{ route('purchase_letters.chart') }}">Chart</a></li>
                            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Administration</h6></li>
                                <li><a class="dropdown-item" href="{{ route('admin.index') }}">Admin Panel</a></li>
                            @endif
                        @elseif($isG2)
                            {{-- Group 2: only Purchase Letters + Management Report --}}
                            <li><a class="dropdown-item" href="{{ route('management.report') }}">Management Report</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Purchase Letters</h6></li>
                            <li><a class="dropdown-item" href="{{ route('purchase_letters.index') }}">Table</a></li>
                            <li><a class="dropdown-item" href="{{ route('purchase_letters.chart') }}">Chart</a></li>
                        @endif
                    </ul>
                </li>
            </ul>
            @endif
            @endauth

            <!-- Right side (toggle + login/logout buttons) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarContent" aria-controls="navbarContent"
                    aria-expanded="false" aria-label="Toggle navigation">
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

    <div class="container">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
