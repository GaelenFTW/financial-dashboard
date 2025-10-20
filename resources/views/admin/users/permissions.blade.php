@extends('layouts.app')

@section('title', 'Edit User Permissions')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin Panel</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users') }}">Users</a></li>
                    <li class="breadcrumb-item active">Manage: {{ $user->name }}</li>
                </ol>
            </nav>
            <h1 class="mb-0">
                <i class="bi bi-shield-lock"></i> Manage {{ $user->name }}
            </h1>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <form action="{{ route('admin.users.permissions.update', $user->id) }}" method="POST">
                @csrf

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Project Assignments</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Assign this user to specific projects and choose their role.</p>

                        @if($projects->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60px;">Assign</th>
                                            <th>Project Name</th>
                                            <th>Code</th>
                                            <th style="width: 160px;">Role</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($projects as $project)
                                            @php
                                                $userProject = $user->projects->firstWhere('project_id', $project->project_id);
                                                $isAssigned = $userProject !== null;
                                                $role = $isAssigned ? $userProject->pivot->role : \App\Enums\ProjectRole::VIEWER->value;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="projects[{{ $project->project_id }}][assigned]" value="0">
                                                    <input
                                                        type="checkbox"
                                                        name="projects[{{ $project->project_id }}][assigned]"
                                                        value="1"
                                                        class="form-check-input project-checkbox"
                                                        id="proj_{{ $project->project_id }}"
                                                        {{ $isAssigned ? 'checked' : '' }}
                                                    >
                                                </td>
                                                <td>{{ $project->name }}</td>
                                                <td><code>{{ $project->code }}</code></td>
                                                <td>
                                                    <select
                                                        name="projects[{{ $project->project_id }}][role]"
                                                        class="form-select form-select-sm"
                                                    >
                                                        @foreach(\App\Enums\ProjectRole::cases() as $roleCase)
                                                            <option
                                                                value="{{ $roleCase->value }}"
                                                                {{ $role === $roleCase->value ? 'selected' : '' }}
                                                            >
                                                                {{ ucfirst($roleCase->value) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">No projects available. 
                                <a href="{{ route('admin.projects') }}">Create projects first</a>.
                            </p>
                        @endif
                    </div>
                </div>


                {{-- Role Permissions Card --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-lock"></i> Role Permissions</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Select permissions for this user across all projects.</p>
                        
                        @if(isset($permissions) && $permissions->count() > 0)
                            <div class="row g-3">
                                @foreach ($permissions as $permission)
                                    <div class="col-md-6 col-lg-4">
                                        <div class="p-3 border rounded-2 h-100">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="permissions[]"
                                                       value="{{ $permission->id }}"
                                                       id="permission_{{ $permission->id }}"
                                                       {{ in_array($permission->id, $userPermissions ?? []) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="permission_{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                </label>
                                            </div>
                                            @if ($permission->description)
                                                <div class="text-muted small mt-2">{{ $permission->description }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No permissions available.</p>
                        @endif
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                    <a href="{{ route('admin.users') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<script>
document.querySelectorAll('.project-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        const row = this.closest('tr');
        const select = row.querySelector('.project-role-select');
        if (this.checked) {
            select.removeAttribute('disabled');
        } else {
            select.setAttribute('disabled', true);
        }
    });
});
</script>
@endpush
@endsection
