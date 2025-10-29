@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin Panel</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="bi bi-people"></i> Manage Users
                </h1>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add User
                </a>
            </div>
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

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Projects</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @php
                                    $roleVal = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
                                @endphp

                                @if($roleVal)
                                    <span class="badge bg-{{ $roleVal === 'super_admin' ? 'danger' : ($roleVal === 'admin' ? 'warning' : 'secondary') }}">
                                        {{ \Illuminate\Support\Str::headline($roleVal) }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">No Role</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge bg-info">
                                    {{ DB::table('user_group_access')->where('user_id', $user->id)->distinct('project_id')->count('project_id') }}
                                </span>
                            </td>

                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-outline-primary">
                                        Edit
                                    </a>
                                    @if($user->id !== auth()->id() && ($user->role ?? '') !== 'super_admin')
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            Delete
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>

                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No users found. <a href="{{ route('admin.users.create') }}">Create one now</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
@endpush
@endsection
