<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PermissionService
{
    /**
     * Check if user has permission for project CRUD operations
     * Only users with group_id = 1 can perform CRUD on projects
     */
    public function checkProjectPermission($userId, $action, $projectId = null)
    {
        // Check if user belongs to group_id = 1
        $hasAdminGroup = DB::table('user_group_access')
            ->where('user_id', $userId)
            ->where('group_id', 1)
            ->exists();

        // Only users in group_id = 1 can perform project CRUD
        if (!$hasAdminGroup) {
            return false;
        }

        // For read, update, delete - check if user has access to specific project
        if (in_array($action, ['read', 'update', 'delete']) && $projectId) {
            $hasProjectAccess = DB::table('user_group_access')
                ->where('user_id', $userId)
                ->where('group_id', 1)
                ->where(function($query) use ($projectId) {
                    $query->where('project_id', $projectId)
                          ->orWhere('project_id', 999999); // Global access
                })
                ->exists();

            return $hasProjectAccess;
        }

        // For create - just need to be in group_id = 1
        return true;
    }

    /**
     * Check if user has specific menu permission
     */
    public function checkUserPermission($userId, $menuId, $action, $projectId = null)
    {
        $query = DB::table('user_group_access as uga')
            ->join('group_menu_action_access as gmaa', 'uga.group_id', '=', 'gmaa.group_id')
            ->join('actions as a', 'gmaa.action_id', '=', 'a.action_id')
            ->where('uga.user_id', $userId)
            ->where('gmaa.menu_id', $menuId)
            ->where('a.action_name', $action);

        if ($projectId) {
            $query->where(function($q) use ($projectId) {
                $q->where('uga.project_id', $projectId)
                  ->orWhere('uga.project_id', 999999);
            });
        }

        return $query->exists();
    }

    /**x`
     * Get all menus accessible by user
     */
    public function getUserMenus($userId, $projectId = null)
    {
        $query = DB::table('user_group_access as uga')
            ->join('group_menu_action_access as gmaa', 'uga.group_id', '=', 'gmaa.group_id')
            ->join('menus as m', 'gmaa.menu_id', '=', 'm.menu_id')
            ->where('uga.user_id', $userId)
            ->distinct();

        if ($projectId) {
            $query->where(function($q) use ($projectId) {
                $q->where('uga.project_id', $projectId)
                  ->orWhere('uga.project_id', 999999);
            });
        }

        return $query->select('m.*')->get();
    }

    /**
     * Get all permissions for a user
     */
    public function getUserPermissions($userId, $projectId = null)
    {
        $query = DB::table('user_group_access as uga')
            ->join('group_menu_action_access as gmaa', 'uga.group_id', '=', 'gmaa.group_id')
            ->join('menus as m', 'gmaa.menu_id', '=', 'm.menu_id')
            ->join('actions as a', 'gmaa.action_id', '=', 'a.action_id')
            ->where('uga.user_id', $userId);

        if ($projectId) {
            $query->where(function($q) use ($projectId) {
                $q->where('uga.project_id', $projectId)
                  ->orWhere('uga.project_id', 999999);
            });
        }

        return $query->select('m.menu_name', 'a.action_name', 'uga.group_id', 'uga.project_id')
            ->distinct()
            ->get();
    }

    /**
     * Assign permission to group
     */
    public function assignPermissionToGroup($groupId, $menuId, $actionId)
    {
        return DB::table('group_menu_action_access')->insertGetId([
            'group_id' => $groupId,
            'menu_id' => $menuId,
            'action_id' => $actionId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Remove permission from group
     */
    public function removePermissionFromGroup($groupId, $menuId, $actionId)
    {
        return DB::table('group_menu_action_access')
            ->where('group_id', $groupId)
            ->where('menu_id', $menuId)
            ->where('action_id', $actionId)
            ->delete();
    }

    /**
     * Assign user to group for a project
     */
    public function assignUserToGroup($userId, $groupId, $projectId)
    {
        return DB::table('user_group_access')->insertGetId([
            'user_id' => $userId,
            'group_id' => $groupId,
            'project_id' => $projectId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Remove user from group for a project
     */
    public function removeUserFromGroup($userId, $groupId, $projectId)
    {
        return DB::table('user_group_access')
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->where('project_id', $projectId)
            ->delete();
    }

    /**
     * Sync all permissions for a group
     */
    public function syncGroupPermissions($groupId, $permissions)
    {
        DB::transaction(function () use ($groupId, $permissions) {
            // Remove existing permissions
            DB::table('group_menu_action_access')
                ->where('group_id', $groupId)
                ->delete();

            // Add new permissions
            foreach ($permissions as $permission) {
                DB::table('group_menu_action_access')->insert([
                    'group_id' => $groupId,
                    'menu_id' => $permission['menu_id'],
                    'action_id' => $permission['action_id'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        });
    }
}