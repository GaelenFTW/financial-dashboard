@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Projects</h1>
        <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">+ New Project</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Project ID</th>
                <th>Code</th>
                <th>Name</th>
                <th>SH</th>
                <th width="180">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($projects as $project)
                <tr>
                    <td>{{ $project->project_id }}</td>
                    <td>{{ $project->code }}</td>
                    <td>{{ $project->name }}</td>
                    <td>{{ $project->sh }}</td>
                    <td>
                        <a href="{{ route('admin.projects.edit', $project->project_id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.projects.destroy', $project->project_id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this project?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No projects found.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $projects->links('pagination::bootstrap-5') }}
</div>
@endsection
