@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
<div class="container">
    <h1>Create Project</h1>

    <form action="{{ route('admin.projects.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="project_id" class="form-label">Project ID</label>
            <input type="number" class="form-control" name="project_id" value="{{ old('project_id') }}" required>
            @error('project_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="code" class="form-label">Code</label>
            <input type="text" class="form-control" name="code" value="{{ old('code') }}" required>
            @error('code') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="sh" class="form-label">SH</label>
            <input type="text" class="form-control" name="sh" value="{{ old('sh') }}">
        </div>

        <button class="btn btn-success">Create</button>
        <a href="{{ route('admin.projects') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
