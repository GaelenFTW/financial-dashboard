<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'position', 'permissions', 'employee_id'];
    protected $casts = ['permissions' => 'integer', 'employee_id' => 'integer'];
    protected $hidden = ['password', 'remember_token'];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'project_user',
            'user_id',
            'project_id',
            'id',
            'project_id'
        )->withTimestamps();
    }

    // Check if user has permission
    public function hasPermission(int $permission): bool
    {
        return ($this->permissions & $permission) === $permission;
    }

    // Check if user can access project
    public function canAccessProject(int|Project $project): bool
    {
        $projectId = $project instanceof Project ? $project->project_id : $project;
        
        // Super admin (permission = 1) has all access
        if ($this->hasPermission(1)) {
            return true;
        }
        
        return $this->projects()->where('project_id', $projectId)->exists();
    }

    // Get all accessible projects
    public function accessibleProjects()
    {
        if ($this->hasPermission(1)) {
            return Project::query();
        }
        return $this->projects();
    }

    // Check if user has access to a project
    public function hasAccessToProject(int $projectId): bool
    {
        return $this->canAccessProject($projectId);
    }

    // Check if user can view (permission 1 or 4)
    public function canView(): bool
    {
        return $this->hasPermission(1) || $this->hasPermission(4);
    }

    // Check if user can upload (permission 1 or 2)
    public function canUpload(): bool
    {
        return $this->hasPermission(1) || $this->hasPermission(2);
    }

    // Check if user can export (permission 1 or 4)
    public function canExport(): bool
    {
        return $this->hasPermission(1) || $this->hasPermission(4);
    }
}