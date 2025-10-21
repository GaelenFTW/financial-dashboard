<?php

// app/Services/PermissionService.php
namespace App\Services;

use App\Models\User;
use App\Models\Group;
use App\Models\AccessGroup;
use App\Models\UserGroupAccess;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function checkUserPermission($userId, $menuId, $actionName, $projectId = null)
    {
        $user = User::findOrFail($userId);
        
        $query = UserGroupAccess::where('user_id', $userId);
        
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        
        $userGroups = $query->pluck('group_id');

        if ($userGroups->isEmpty()) {
            return false;
        }

        return AccessGroup::whereIn('group_id', $userGroups)
            ->where('menu_id', $menuId)
            ->whereHas('action', function($q) use ($actionName) {
                $q->where('action', strtolower($actionName))->where('active', 1);
            })
            ->exists();
    }

    public function getUserMenus($userId, $projectId = null)
    {
        $user = User::findOrFail($userId);
        
        $query = UserGroupAccess::where('user_id', $userId);
        
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        
        $groupIds = $query->pluck('group_id');

        if ($groupIds->isEmpty()) {
            return collect();
        }

        $menuIds = AccessGroup::whereIn('group_id', $groupIds)
            ->distinct()
            ->pluck('menu_id');

        return Menu::active()
            ->whereIn('menu_id', $menuIds)
            ->with(['actions' => function($q) use ($groupIds) {
                $q->whereHas('accessGroups', function($query) use ($groupIds) {
                    $query->whereIn('group_id', $groupIds);
                })->where('active', 1);
            }])
            ->get();
    }

    public function getUserPermissions($userId, $projectId = null)
    {
        $userGroups = UserGroupAccess::where('user_id', $userId);
        
        if ($projectId) {
            $userGroups->where('project_id', $projectId);
        }
        
        $groupIds = $userGroups->pluck('group_id');

        return AccessGroup::whereIn('group_id', $groupIds)
            ->with(['menu', 'action', 'group'])
            ->get()
            ->map(function($access) {
                return [
                    'menu' => $access->menu->menu_name,
                    'action' => $access->action->action,
                    'group' => $access->group->name,
                    'link' => $access->menu->link
                ];
            });
    }

    public function assignPermissionToGroup($groupId, $menuId, $actionId)
    {
        return AccessGroup::firstOrCreate([
            'group_id' => $groupId,
            'menu_id' => $menuId,
            'action_id' => $actionId
        ]);
    }

    public function removePermissionFromGroup($groupId, $menuId, $actionId)
    {
        return AccessGroup::where('group_id', $groupId)
            ->where('menu_id', $menuId)
            ->where('action_id', $actionId)
            ->delete();
    }

    public function assignUserToGroup($userId, $groupId, $projectId)
    {
        return UserGroupAccess::firstOrCreate([
            'user_id' => $userId,
            'group_id' => $groupId,
            'project_id' => $projectId
        ]);
    }

    public function removeUserFromGroup($userId, $groupId, $projectId)
    {
        return UserGroupAccess::where('user_id', $userId)
            ->where('group_id', $groupId)
            ->where('project_id', $projectId)
            ->delete();
    }

    public function syncGroupPermissions($groupId, array $permissions)
    {
        DB::beginTransaction();
        try {
            AccessGroup::where('group_id', $groupId)->delete();
            
            foreach ($permissions as $permission) {
                AccessGroup::create([
                    'group_id' => $groupId,
                    'menu_id' => $permission['menu_id'],
                    'action_id' => $permission['action_id']
                ]);
            }
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}