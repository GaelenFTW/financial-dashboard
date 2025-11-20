<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'group_id', 'AdminID', 'employee_id', 'position', 'role', 'active'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    public function userGroupAccesses()
    {
        return $this->hasMany(UserGroupAccess::class, 'user_id', 'id');
    }

    // Many-to-many relationship with MasterProject
    public function projects()
    {
        return $this->belongsToMany(
            MasterProject::class,
            'user_group_access',  // pivot table
            'user_id',            // foreign key on pivot table for users
            'project_id',         // foreign key on pivot table for projects
            'id',                 // key on users table
            'project_id'          // key on master_projects table
        );
    }

    // Get allowed project IDs for this user
    public function getAllowedProjectIds()
    {
        $projectIds = $this->userGroupAccesses()->pluck('project_id')->toArray();
        
        // If empty, check if user has AdminID (legacy support)
        if (empty($projectIds) && $this->AdminID) {
            // Return all projects for admin users
            return [999999];
        }
        
        return $projectIds;
    }

    // Check if user has access to a specific project
    public function hasProjectAccess($projectId)
    {
        $allowedIds = $this->getAllowedProjectIds();
        
        // Check for "all projects" access
        if (in_array(999999, $allowedIds, true)) {
            return true;
        }
        
        return in_array((int)$projectId, $allowedIds, true);
    }

    // Get user permissions (menu + action)
    public function getPermissions()
    {
        if (!$this->group_id) {
            return collect([]);
        }

        return \DB::table('access_groups')
            ->join('menus', 'access_groups.menu_id', '=', 'menus.menu_id')
            ->join('actions', 'access_groups.action_id', '=', 'actions.action_id')
            ->where('access_groups.group_id', $this->group_id)
            ->where('menus.deleted', 0)
            ->where('menus.active', 1)
            ->where('actions.active', 1)
            ->select(
                'menus.menu_id',
                'menus.name as menu_name',
                'menus.link',
                'actions.action_id',
                'actions.action'
            )
            ->get();
    }

    // Check if user has permission for specific menu and action
    public function hasPermission($menuId, $actionId)
    {
        if (!$this->group_id) {
            return false;
        }

        return \DB::table('access_groups')
            ->where('group_id', $this->group_id)
            ->where('menu_id', $menuId)
            ->where('action_id', $actionId)
            ->exists();
    }

    // Check if user can access a menu
    public function canAccessMenu($menuId)
    {
        if (!$this->group_id) {
            return false;
        }

        return \DB::table('access_groups')
            ->where('group_id', $this->group_id)
            ->where('menu_id', $menuId)
            ->exists();
    }
}