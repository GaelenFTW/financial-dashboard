@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container mt-4">
    <h2>User Project Management</h2>

    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered mt-3 align-middle">
        <thead class="table-light">
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Projects</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <form action="{{ route('admin.users.updateProjects', $user) }}" method="POST">
                            @csrf
                            <select name="projects[]" multiple class="form-select" size="4">
                                @foreach($projects as $project)
                                    <option value="{{ $project->project_id }}"
                                        {{ $user->projects->pluck('project_id')->contains($project->project_id) ? 'selected' : '' }}>
                                        {{ $project->code }} — {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                    </td>
                    <td>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
