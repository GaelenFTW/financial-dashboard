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

        body.auth-bg {
            background: url('{{ asset('images/cws.png') }}') no-repeat center center fixed;
            background-size: cover;
        }

        .navbar-brand img {
            height: 36px;
            margin-right: 10px;
            vertical-align: middle;
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
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <img src="{{ asset('images/logo-ciputra.png') }}" alt="Company Logo">
                <span>Financial Dashboard</span>
            </a>

            <!-- ✅ Dynamic dropdown -->
            @auth
            @if(!empty($menuItems))
            <ul class="navbar-nav ms-3">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="menuDropdown"
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        ☰ Menu
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="menuDropdown">
                        @foreach($menuItems as $section => $items)
                            <li><h6 class="dropdown-header">{{ $section }}</h6></li>
                            @foreach($items as $item)
                                @if(isset($item['divider']))
                                    <li><hr class="dropdown-divider"></li>
                                @else
                                    <li><a class="dropdown-item" href="{{ route($item['route']) }}">{{ $item['label'] }}</a></li>
                                @endif
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                        @endforeach
                    </ul>
                </li>
            </ul>
            @endif
            @endauth

            <!-- Toggle and auth controls -->
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
