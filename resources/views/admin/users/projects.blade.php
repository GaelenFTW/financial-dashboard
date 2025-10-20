<!-- @extends('layouts.app')

@section('title', 'Manage User Projects')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Projects for: {{ $user->name }}</h2>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">← Back to Users</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Project Access</h5>
                <div>
                    <span class="badge bg-info">{{ $user->email }}</span>
                    @if($user->hasPermission(1))
                        <span class="badge bg-danger">Super Admin - Has access to all projects</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($user->hasPermission(1))
                <div class="alert alert-info">
                    <strong>Note:</strong> This user is a Super Admin and has automatic access to all projects. 
                    Project assignments are not applicable.
                </div>
            @endif

            <form action="{{ route('admin.users.projects.update', $user) }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">Select Projects <span class="text-danger">*</span></label>
                    <p class="text-muted small">Hold Ctrl (Windows) or Cmd (Mac) to select multiple projects</p>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                @foreach($allProjects as $project)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               name="project_ids[]" 
                                               value="{{ $project->project_id }}"
                                               id="project_{{ $project->project_id }}"
                                               {{ in_array($project->project_id, $userProjects) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="project_{{ $project->project_id }}">
                                            <strong>{{ $project->code }}</strong> - {{ $project->name }}
                                            <span class="badge bg-secondary">SH: {{ $project->sh }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Deselect All</button>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Project Access
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('click', function() {
    document.querySelectorAll('input[name="project_ids[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
});

document.getElementById('deselectAll').addEventListener('click', function() {
    document.querySelectorAll('input[name="project_ids[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
});
</script>
@endpush
@endsection -->
