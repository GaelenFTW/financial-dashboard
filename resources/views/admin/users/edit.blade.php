@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin Panel</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users') }}">Users</a></li>
                    <li class="breadcrumb-item active">Edit: {{ $user->name }}</li>
                </ol>
            </nav>
            <h1 class="mb-0">
                <i class="bi bi-pencil"></i> Edit User: {{ $user->name }}
            </h1>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">User Information</h5>

                        {{-- Permissions Button --}}
                        <a href="{{ route('admin.users.permissions', $user->id) }}" class="btn btn-outline-primary">
                            <i class="bi bi-shield-lock"></i> Manage Permissions
                        </a>
                    </div>

                    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Basic Info --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password">
                            <small class="text-muted">Leave blank to keep current password</small>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" 
                                   id="password_confirmation" name="password_confirmation">
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">System Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->value }}" 
                                        {{ old('role', $user->role) === $role->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $role->value)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Employee ID</label>
                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror" 
                                   id="employee_id" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}">
                            @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" class="form-control @error('position') is-invalid @enderror" 
                                   id="position" name="position" value="{{ old('position', $user->position) }}">
                            @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="active" class="form-label">Account Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('active') is-invalid @enderror" id="active" name="active" required>
                                <option value="1" {{ old('active', $user->active) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('active', $user->active) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>


                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update User
                            </button>
                            <a href="{{ route('admin.users') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.project-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const select = this.closest('li')?.querySelector('.project-role-select');
            if (select) select.disabled = !this.checked;
        });
    });
});
</script>

@endpush
@endsection
    