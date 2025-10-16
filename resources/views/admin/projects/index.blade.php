@extends('layouts.app')

@section('title', 'Projects Management')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Projects Management</h2>
        <div>
            <a href="{{ route('admin.projects.create') }}" class="btn btn-success">+ Create Project</a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
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
                            <th>SH</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                        <tr>
                            <td>{{ $project->project_id }}</td>
                            <td><span class="badge bg-secondary">{{ $project->sh }}</span></td>
                            <td><strong>{{ $project->code }}</strong></td>
                            <td>{{ $project->name }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.projects.edit', $project) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.projects.destroy', $project) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this project? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <p class="text-muted mb-2">No projects found</p>
                                <a href="{{ route('admin.projects.create') }}" class="btn btn-success btn-sm">Create First Project</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $projects->links() }}
    </div>
</div>
@endsection
