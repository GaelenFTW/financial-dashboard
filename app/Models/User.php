<?php

namespace App\Models;

use App\Enums\ProjectRole;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'password', 'permissions', 'adminid', 'role', 'employee_id', 'position',
    ];

    protected $casts = [
        'permissions' => 'array',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
    ];

    public function projects()
    {
        return $this->belongsToMany(
            MasterProject::class,  // model name
            'project_user',        // pivot table name
            'user_id',             // FK on pivot referencing users
            'project_id'           // FK on pivot referencing master_project
        )->withPivot('role')->withTimestamps();
    }

    public function hasProjectAccess(int $projectId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->projects()->where('master_project.project_id', $projectId)->exists();
    }

    /**
     * Get user's role in a specific project
     */
    public function getProjectRole(int $projectId): ?ProjectRole
    {
        $project = $this->projects()->where('master_project.project_id', $projectId)->first();

        if (! $project) {
            return null;
        }

        return ProjectRole::from($project->pivot->role);
    }

    /**
     * Check if user can edit in a specific project
     */
    public function canEditProject(int $projectId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->getProjectRole($projectId);

        return $role && $role->canEdit();
    }

    public function isProjectAdmin(int $projectId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->getProjectRole($projectId);

        return $role && $role->isAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Check if user is admin (system-level)
     */
    public function isAdmin(): bool
    {
        return $this->role && $this->role->isAdmin();
    }

    // Legacy methods
    public function canUpload(){
        return $this->permissions == 1 || $this->permissions == 2;
    }
    public function canView() {
        return $this->permissions == 1 || $this->permissions == 2 || $this->permissions == 3;
    }
    public function canExport(){
        return $this->permissions == 1 || $this->permissions == 3;
    }
}
