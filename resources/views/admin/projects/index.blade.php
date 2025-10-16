@extends('layouts.app')

@section('title', 'Manage Projects')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin Panel</a></li>
                    <li class="breadcrumb-item active">Projects</li>
                </ol>
            </nav>
            <h1 class="mb-0">
                <i class="bi bi-folder"></i> Manage Projects
            </h1>
            <p class="text-muted">Projects fetched from external API (target.php). Sync them to enable user assignments.</p>
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
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                        <tr>
                            <td>{{ $project['id'] }}</td>
                            <td>
                                <strong>{{ $project['name'] }}</strong>
                                @if($project['in_db'])
                                    <br><small class="text-muted">Code: {{ $project['db_project']->code }}</small>
                                @endif
                            </td>
                            <td>
                                @if($project['in_db'] && $project['db_project']->description)
                                    {{ Str::limit($project['db_project']->description, 50) }}
                                @else
                                    {{ $project['description'] ? Str::limit($project['description'], 50) : '-' }}
                                @endif
                            </td>
                            <td>
                                @if($project['in_db'])
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Synced
                                    </span>
                                    @if(!$project['db_project']->is_active)
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle"></i> Not Synced
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($project['in_db'])
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.projects.edit', $project['db_project']) }}" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.projects.destroy', $project['db_project']) }}" 
                                              method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to remove this project from the database? This will also remove all user assignments.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Remove from DB">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <form action="{{ route('admin.projects.sync') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="project_id" value="{{ $project['id'] }}">
                                        <input type="hidden" name="name" value="{{ $project['name'] }}">
                                        <input type="hidden" name="description" value="{{ $project['description'] ?? '' }}">
                                        <button type="submit" class="btn btn-sm btn-primary" title="Sync to Database">
                                            <i class="bi bi-arrow-down-circle"></i> Sync
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No projects found from API. Check API connection and configuration.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bi bi-info-circle"></i> About Projects
            </h5>
            <ul class="mb-0">
                <li>Projects are fetched from the external API (<code>api4/target.php</code>)</li>
                <li>Click <strong>Sync</strong> to add a project to the database</li>
                <li>Only synced projects can be assigned to users</li>
                <li>Removing a project from the database does not delete it from the API</li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
@endpush
@endsection
