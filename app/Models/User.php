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

    /**
     * Get the projects that the user belongs to.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            MasterProject::class,
            'project_user',
            'user_id',        // FK on pivot for users
            'project_id',     // FK on pivot for master_projects
            'id',             // local key on users
            'project_id'      // local key on master_projects 👈 IMPORTANT
        )
        ->withPivot('role')
        ->withTimestamps();
    }

    /**
     * Check if user has access to a specific project
     */
    public function hasProjectAccess(int $projectId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->projects()->where('master_projects.project_id', $projectId)->exists();
    }

    /**
     * Get user's role in a specific project
     */
    public function getProjectRole(int $projectId): ?ProjectRole
    {
        $project = $this->projects()->where('master_projects.project_id', $projectId)->first();

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

    /**
     * Check if user is project admin
     */
    public function isProjectAdmin(int $projectId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->getProjectRole($projectId);

        return $role && $role->isAdmin();
    }

    /**
     * Check if user is super admin
     */
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
    public function canUpload()
    {
        return $this->permissions == 1 || $this->permissions == 2;
    }

    public function canView()
    {
        return $this->permissions == 1 || $this->permissions == 2 || $this->permissions == 3;
    }

    public function canExport()
    {
        return $this->permissions == 1 || $this->permissions == 3;
    }
}
