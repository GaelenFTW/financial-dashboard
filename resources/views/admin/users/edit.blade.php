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
                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
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
                                        {{ old('role', $user->role?->value) === $role->value ? 'selected' : '' }}>
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

                        {{-- Project Assignments --}}
                        <hr class="my-4">
                        <h5>Project Assignments</h5>
                        <p class="text-muted">Assign this user to projects with specific roles.</p>

                        @if($projects->count() > 0)
                            <div class="mb-3">
                                <div class="dropdown w-100">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" 
                                            type="button" id="projectDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Choose projects...
                                    </button>

                                    <ul class="dropdown-menu p-3 w-100" aria-labelledby="projectDropdown" 
                                        style="max-height: 300px; overflow-y: auto;">

                                        @foreach($projects as $project)
                                            @php
                                                // Match based on master_project.project_id
                                                $userProject = $user->projects->firstWhere('project_id', $project->project_id);
                                                $isAssigned = $userProject !== null;
                                                $role = $isAssigned ? $userProject->pivot->role : 'viewer';
                                            @endphp

                                            <li class="mb-2 border-bottom pb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input project-checkbox" 
                                                           type="checkbox" 
                                                           id="project_{{ $project->project_id }}" 
                                                           name="projects[{{ $project->project_id }}][assigned]" 
                                                           value="1" 
                                                           {{ $isAssigned ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="project_{{ $project->project_id }}">
                                                        {{ $project->name }} 
                                                        <small class="text-muted">({{ $project->code }})</small>
                                                    </label>
                                                </div>

                                                <div class="mt-2 ms-4">
                                                    <select class="form-select form-select-sm project-role-select" 
                                                            name="projects[{{ $project->project_id }}][role]" 
                                                            {{ $isAssigned ? '' : 'disabled' }}>
                                                        <option value="viewer" {{ $role === 'viewer' ? 'selected' : '' }}>Viewer</option>
                                                        <option value="editor" {{ $role === 'editor' ? 'selected' : '' }}>Editor</option>
                                                        <option value="admin"  {{ $role === 'admin'  ? 'selected' : '' }}>Admin</option>
                                                    </select>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @else
                            <p class="text-muted">No projects available. 
                                <a href="{{ route('admin.projects') }}">Create projects first</a>.
                            </p>
                        @endif

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
document.querySelectorAll('.project-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        const select = this.closest('li').querySelector('.project-role-select');
        select.disabled = !this.checked;
    });
});
</script>
@endpush
@endsection
    