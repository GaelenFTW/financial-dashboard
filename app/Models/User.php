<?php

namespace App\Models;

use App\Enums\ProjectRole;
use App\Enums\UserRole;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
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

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permission', 'user_id', 'permission_id');
    }


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

    public function isAdmin(): bool
    {
        return $this->role && $this->role->isAdmin();
    }

    public function hasPermission(string $name): bool
    {
        if ($this->isSuperAdmin()) return true;

        // Check user-specific permission
        $userHas = DB::table('user_permission')
            ->join('permissions', 'permissions.id', '=', 'user_permission.permission_id')
            ->where('user_permission.user_id', $this->id)
            ->where('permissions.name', $name)
            ->exists();

        if ($userHas) return true;

        // Check role-based permission
        $roleHas = DB::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('role_permission.role', $this->role->value)
            ->where('permissions.name', $name)
            ->exists();

        return $roleHas;
    }

    public function groupAccesses()
    {
        return $this->hasMany(UserGroupAccess::class, 'user_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'user_group_access', 'user_id', 'group_id');
    }

    // Legacy methods
    public function canUpload(){
        return $this->permissions == 1 || $this->permissions == 2;
    }
    public function canView(){
        return $this->permissions == 1 || $this->permissions == 2 || $this->permissions == 3;
    }
    public function canExport(){
        return $this->permissions == 1 || $this->permissions == 3;
    }
}
