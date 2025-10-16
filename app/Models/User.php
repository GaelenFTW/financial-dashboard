<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'position', 'permissions'];
    protected $casts = ['permissions' => 'integer'];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'project_user',
            'user_id',
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
}