<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'group_id', 'AdminID'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function userGroupAccesses()
    {
        return $this->hasMany(UserGroupAccess::class, 'user_id');
    }

    // Get allowed project IDs for this user
    public function getAllowedProjectIds()
    {
        return $this->userGroupAccesses()->pluck('project_id')->toArray();
    }

    // Check if user has access to a specific project
    public function hasProjectAccess($projectId)
    {
        // Check for "all projects" access
        if ($this->userGroupAccesses()->where('project_id', 999999)->exists()) {
            return true;
        }
        
        return $this->userGroupAccesses()->where('project_id', $projectId)->exists();
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