<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'group_id';
    
    protected $fillable = ['name', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function accessGroups()
    {
        return $this->hasMany(AccessGroup::class, 'group_id', 'group_id');
    }

    public function userGroupAccess()
    {
        return $this->hasMany(UserGroupAccess::class, 'group_id', 'group_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_group_access', 'group_id', 'user_id', 'group_id', 'user_id')
            ->withPivot('project_id')->withTimestamps();
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'access_groups', 'group_id', 'menu_id', 'group_id', 'menu_id')
            ->withPivot('action_id')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    // Get all permissions for this group
    public function getPermissions()
    {
        return $this->accessGroups()->with(['menu', 'action'])->get();
    }

    // Check if group has specific permission
    public function hasPermission($menuId, $actionName)
    {
        return $this->accessGroups()
            ->whereHas('action', function($query) use ($actionName) {
                $query->where('action', $actionName);
            })
            ->where('menu_id', $menuId)
            ->exists();
    }
}