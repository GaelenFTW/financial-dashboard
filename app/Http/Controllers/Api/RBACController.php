<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RBACController extends Controller
{
    public function index()
    {
        try {
            $roles = DB::table('groups')
                ->select('group_id', 'name')
                ->get();

            $menus = DB::table('menus')
                ->select('menu_id', 'name', 'link', 'parent_id', 'sort_order', 'active')
                ->where('deleted', 0)
                ->where('active', 1)
                ->orderByRaw('COALESCE(parent_id, 0)')
                ->orderBy('sort_order')
                ->get()
                ->map(function($menu) {
                    $menu->parent_id = $menu->parent_id == 0 ? null : $menu->parent_id;
                    return $menu;
                });

            // Get actions with their menu_id relationship
            $actions = DB::table('actions')
                ->select('action_id', 'menu_id', 'action', 'active')
                ->where('active', 1)
                ->get();

            // Get access permissions
            $access = DB::table('access_groups')
                ->select('group_id', 'menu_id', 'action_id')
                ->get();

            Log::info('RBAC Data Retrieved', [
                'roles_count' => $roles->count(),
                'menus_count' => $menus->count(),
                'actions_count' => $actions->count(),
                'access_count' => $access->count()
            ]);

            return response()->json([
                'roles' => $roles,
                'menus' => $menus,
                'actions' => $actions,
                'access' => $access
            ]);

        } catch (Exception $e) {
            Log::error('RBAC Index Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'message' => 'Database error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'group_id' => 'required|integer|exists:groups,group_id',
                'permissions' => 'required|array',
                'permissions.*.menu_id' => 'required|integer|exists:menus,menu_id',
                'permissions.*.action_id' => 'required|integer|exists:actions,action_id',
            ]);

            $groupId = $validated['group_id'];
            $permissions = $validated['permissions'];

            DB::beginTransaction();

            // Delete old access for this group
            DB::table('access_groups')->where('group_id', $groupId)->delete();

            // Insert new menu-action combinations
            foreach ($permissions as $permission) {
                DB::table('access_groups')->insert([
                    'group_id' => $groupId,
                    'menu_id' => $permission['menu_id'],
                    'action_id' => $permission['action_id'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            Log::info('RBAC Updated Successfully', [
                'group_id' => $groupId,
                'permissions_count' => count($permissions)
            ]);

            return response()->json([
                'message' => 'RBAC updated successfully',
                'permissions_updated' => count($permissions)
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('RBAC Update Error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error updating RBAC: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserPermissions($userId)
    {
        try {
            $user = DB::table('users')->where('id', $userId)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $permissions = DB::table('access_groups')
                ->join('menus', 'access_groups.menu_id', '=', 'menus.menu_id')
                ->join('actions', 'access_groups.action_id', '=', 'actions.action_id')
                ->where('access_groups.group_id', $user->group_id)
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

            return response()->json(['permissions' => $permissions]);

        } catch (Exception $e) {
            Log::error('Get User Permissions Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error getting user permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}