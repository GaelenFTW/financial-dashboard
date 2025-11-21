<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

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

    public function projects()
    {
        return $this->belongsToMany(
            MasterProject::class,
            'user_group_access',
            'user_id',
            'project_id',
            'id',
            'project_id'
        );
    }

    // ============= RBAC Methods =============

    /**
     * Get all permissions for this user based on their group
     */
    public function getPermissions()
    {
        if (!$this->group_id) {
            return collect([]);
        }

        return DB::table('access_groups')
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
                'menus.parent_id',
                'actions.action_id',
                'actions.action'
            )
            ->get();
    }

    /**
     * Get accessible menus for this user (for sidebar navigation)
     */
    public function getAccessibleMenus()
    {
        if (!$this->group_id) {
            return collect([]);
        }

        return DB::table('menus')
            ->join('access_groups', 'menus.menu_id', '=', 'access_groups.menu_id')
            ->where('access_groups.group_id', $this->group_id)
            ->where('menus.deleted', 0)
            ->where('menus.active', 1)
            ->select('menus.*')
            ->distinct()
            ->orderBy('menus.sort_order')
            ->get()
            ->groupBy('parent_id');
    }

    /**
     * Check if user can access a specific menu
     */
    public function canAccessMenu($menuId)
    {
        if (!$this->group_id) {
            return false;
        }

        return DB::table('access_groups')
            ->where('group_id', $this->group_id)
            ->where('menu_id', $menuId)
            ->exists();
    }

    /**
     * Check if user can perform a specific action on a menu
     */
    public function canPerformAction($menuId, $actionName)
    {
        if (!$this->group_id) {
            return false;
        }

        return DB::table('access_groups')
            ->join('actions', 'access_groups.action_id', '=', 'actions.action_id')
            ->where('access_groups.group_id', $this->group_id)
            ->where('access_groups.menu_id', $menuId)
            ->where('actions.action', $actionName)
            ->exists();
    }

    /**
     * Check if user has a specific permission (menu + action combination)
     */
    public function hasPermission($menuId, $actionId)
    {
        if (!$this->group_id) {
            return false;
        }

        return DB::table('access_groups')
            ->where('group_id', $this->group_id)
            ->where('menu_id', $menuId)
            ->where('action_id', $actionId)
            ->exists();
    }

    /**
     * Check if user can create on a menu
     */
    public function canCreate($menuId)
    {
        return $this->canPerformAction($menuId, 'create');
    }

    /**
     * Check if user can read/view a menu
     */
    public function canRead($menuId)
    {
        return $this->canPerformAction($menuId, 'read');
    }

    /**
     * Check if user can update on a menu
     */
    public function canUpdate($menuId)
    {
        return $this->canPerformAction($menuId, 'update');
    }

    /**
     * Check if user can delete on a menu
     */
    public function canDelete($menuId)
    {
        return $this->canPerformAction($menuId, 'delete');
    }

    // ============= Project Access Methods =============

    public function getAllowedProjectIds()
    {
        $projectIds = $this->userGroupAccesses()->pluck('project_id')->toArray();
        
        if (empty($projectIds) && $this->AdminID) {
            return [999999]; // All projects access
        }
        
        return $projectIds;
    }

    public function hasProjectAccess($projectId)
    {
        $allowedIds = $this->getAllowedProjectIds();
        
        if (in_array(999999, $allowedIds, true)) {
            return true;
        }
        
        return in_array((int)$projectId, $allowedIds, true);
    }
}