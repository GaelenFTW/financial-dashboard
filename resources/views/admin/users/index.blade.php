@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>User Management</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->position ?? 'N/A' }}</td>
                            <td>
                                @foreach($permissions as $permission)
                                    @if($user->hasPermission($permission->value))
                                        <span class="badge bg-info me-1">{{ $permission->label() }}</span>
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Edit User">
                                        Edit
                                    </a>
                                    <a href="{{ route('admin.users.projects', $user) }}" 
                                       class="btn btn-sm btn-outline-secondary" 
                                       title="Manage Projects">
                                        Projects
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $users->links() }}
    </div>
</div>
@endsection
