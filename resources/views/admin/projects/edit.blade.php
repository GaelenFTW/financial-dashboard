@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<div class="container">
    <h1 class="mb-4">Edit Project: {{ $project->name }}</h1>

    <form method="POST" action="{{ route('admin.projects.update', $project->project_id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Project ID</label>
            <input type="number" name="project_id" class="form-control" value="{{ old('project_id', $project->project_id) }}" required>
            @error('project_id') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $project->code) }}" required>
            @error('code') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $project->name) }}" required>
            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">SH</label>
            <input type="text" name="sh" class="form-control" value="{{ old('sh', $project->sh) }}">
            @error('sh') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.projects') }}" class="btn btn-secondary">Back</a>
            <button type="submit" class="btn btn-primary">Update Project</button>
        </div>
    </form>
</div>
@endsection
