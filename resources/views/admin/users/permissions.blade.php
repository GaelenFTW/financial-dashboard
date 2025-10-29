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

                {{-- Project Assignments --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-diagram-3"></i> Project Assignments
                        </h5>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#projectAssignmentsCollapse" aria-expanded="true"
                            aria-controls="projectAssignmentsCollapse">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>

                    <div id="projectAssignmentsCollapse" class="collapse show">
                        <div class="card-body">
                            <p class="text-muted mb-3">Assign this user to specific projects.</p>

                            @php
                                // Fetch all assigned project IDs directly from user_group_access
                                $assignedProjects = \Illuminate\Support\Facades\DB::table('user_group_access')
                                    ->where('user_id', $user->id)
                                    ->pluck('project_id')
                                    ->toArray();
                            @endphp

                            @if($projects->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Assign</th>
                                                <th>Project Name</th>
                                                <th>Code</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($projects as $project)
                                                @php
                                                    $isAssigned = in_array($project->project_id, $assignedProjects);
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="projects[{{ $project->project_id }}][assigned]" value="0">
                                                        <input type="checkbox"
                                                            name="projects[{{ $project->project_id }}][assigned]"
                                                            value="1"
                                                            class="form-check-input project-checkbox"
                                                            id="proj_{{ $project->project_id }}"
                                                            {{ $isAssigned ? 'checked' : '' }}>
                                                    </td>
                                                    <td>{{ $project->name }}</td>
                                                    <td><code>{{ $project->code }}</code></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Group Selection --}}
                <div class="mb-4">
                    <h4>Assign Group</h4>
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Group ID</th>
                                <th>Name</th>
                                <th>Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $group)
                                <tr>
                                    <td>
                                        <input type="radio" name="group_id" value="{{ $group->group_id }}"
                                            {{ isset($user->group_id) && $user->group_id == $group->group_id ? 'checked' : '' }}>
                                    </td>
                                    <td>{{ $group->group_id }}</td>
                                    <td>{{ $group->name }}</td>
                                    <td>{{ $group->active ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Menu / Action Permissions --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-list-check"></i> Menu & Action Permissions</h5>
                    </div>
                    <div class="card-body">
                        @if($menus->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Menu</th>
                                            <th>Name</th>
                                            <th>Link</th>
                                            <th>Create</th>
                                            <th>Read</th>
                                            <th>Update</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($menus as $menu)
                                        <tr>
                                            <td class="text-center">
                                                <input type="hidden" name="permissions[{{ $menu->menu_id }}][menu]" value="0">
                                                <input type="checkbox"
                                                    name="permissions[{{ $menu->menu_id }}][menu]"
                                                    value="1"
                                                    class="form-check-input menu-checkbox"
                                                    data-menu-id="{{ $menu->menu_id }}">
                                            </td>
                                            <td><strong>{{ ucfirst($menu->menu_name) }}</strong></td>
                                            <td><code>{{ $menu->link }}</code></td>
                                            @foreach(['create','read','update','delete'] as $actionName)
                                                <td class="text-center">
                                                    <input type="hidden" name="permissions[{{ $menu->menu_id }}][{{ $actionName }}]" value="0">
                                                    <input type="checkbox"
                                                        name="permissions[{{ $menu->menu_id }}][{{ $actionName }}]"
                                                        value="1"
                                                        class="form-check-input crud-checkbox menu-{{ $menu->menu_id }}">
                                                </td>
                                            @endforeach
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-secondary">
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
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.project-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const row = this.closest('tr');
            const select = row?.querySelector('.project-role-select');
            if (select) select.disabled = !this.checked;
        });
    });
});
</script>
@endpush
@endsection
