@extends('layouts.app')

@section('title', 'Admin Panel')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="mb-0">
                <i class="bi bi-shield-lock"></i> Admin Panel
            </h1>
            <p class="text-muted">Manage users and projects</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">
        <!-- Users Card -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-people"></i> Users
                        </h5>
                        <span class="badge bg-primary rounded-pill">{{ $usersCount }}</span>
                    </div>
                    <p class="card-text text-muted">Manage user accounts, roles, and permissions.</p>
                    <a href="{{ route('admin.users') }}" class="btn btn-primary">
                        <i class="bi bi-gear"></i> Manage Users
                    </a>
                </div>
            </div>
        </div>

        <!-- Projects Card -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-folder"></i> Projects
                        </h5>
                        <span class="badge bg-success rounded-pill">{{ $projectsCount }}</span>
                    </div>
                    <p class="card-text text-muted">Sync projects from API and manage project data.</p>
                    <a href="{{ route('admin.projects') }}" class="btn btn-success">
                        <i class="bi bi-gear"></i> Manage Projects
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle"></i> Admin Features
                    </h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            Create, edit, and delete user accounts
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            Assign users to projects with specific roles
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            Sync projects from external API (target.php)
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            Manage project details and status
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
@endpush
@endsection
