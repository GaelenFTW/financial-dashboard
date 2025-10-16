@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <span class="badge bg-danger">Super Admin</span>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary">
                        <i class="bi bi-people-fill"></i> Total Users
                    </h5>
                    <p class="card-text display-4">{{ $total_users }}</p>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary btn-sm">Manage Users</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">
                        <i class="bi bi-folder-fill"></i> Total Projects
                    </h5>
                    <p class="card-text display-4">{{ $total_projects }}</p>
                    <a href="{{ route('admin.projects.index') }}" class="btn btn-success btn-sm">Manage Projects</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Recent Users</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent_users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->position ?? 'N/A' }}</td>
                            <td>
                                @foreach($permissions as $permission)
                                    @if($user->hasPermission($permission->value))
                                        <span class="badge bg-info">{{ $permission->label() }}</span>
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
