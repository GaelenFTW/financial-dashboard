@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Project: {{ $project->code }}</h2>
        <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary">← Back to Projects</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Project Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.projects.update', $project) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="sh" class="form-label">SH <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('sh') is-invalid @enderror" 
                               id="sh" name="sh" value="{{ old('sh', $project->sh) }}" 
                               min="0" max="255" required>
                        <div class="form-text">Enter a number between 0 and 255</div>
                        @error('sh')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code', $project->code) }}" required>
                        <div class="form-text">Unique project code</div>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name', $project->name) }}" required>
                    <div class="form-text">Full project name</div>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Project
                    </button>
                    <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="card mt-4 border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Danger Zone</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Once you delete a project, there is no going back. Please be certain.</p>
            <form action="{{ route('admin.projects.destroy', $project) }}" 
                  method="POST" 
                  onsubmit="return confirm('Are you sure you want to delete this project? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Delete Project
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
