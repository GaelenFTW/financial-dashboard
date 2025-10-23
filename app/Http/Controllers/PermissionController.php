<?php 
  
namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function checkPermission(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'menu_id' => 'required|integer',
            'action' => 'required|string',
            'project_id' => 'nullable|integer'
        ]);

        $hasPermission = $this->permissionService->checkUserPermission(
            $validated['user_id'],
            $validated['menu_id'],
            $validated['action'],
            $validated['project_id'] ?? null
        );

        return response()->json(['has_permission' => $hasPermission]);
    }

    public function getUserMenus(Request $request, $userId)
    {
        $projectId = $request->query('project_id');
        $menus = $this->permissionService->getUserMenus($userId, $projectId);
        return response()->json($menus);
    }

    public function getUserPermissions(Request $request, $userId)
    {
        $projectId = $request->query('project_id');
        $permissions = $this->permissionService->getUserPermissions($userId, $projectId);
        return response()->json($permissions);
    }

    public function assignPermission(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|integer',
            'menu_id' => 'required|integer',
            'action_id' => 'required|integer'
        ]);

        $permission = $this->permissionService->assignPermissionToGroup(
            $validated['group_id'],
            $validated['menu_id'],
            $validated['action_id']
        );

        return response()->json($permission, 201);
    }

    public function removePermission(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|integer',
            'menu_id' => 'required|integer',
            'action_id' => 'required|integer'
        ]);

        $this->permissionService->removePermissionFromGroup(
            $validated['group_id'],
            $validated['menu_id'],
            $validated['action_id']
        );

        return response()->json(['message' => 'Permission removed successfully']);
    }

    public function assignUserToGroup(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'group_id' => 'required|integer',
            'project_id' => 'required|integer'
        ]);

        $access = $this->permissionService->assignUserToGroup(
            $validated['user_id'],
            $validated['group_id'],
            $validated['project_id']
        );

        return response()->json($access, 201);
    }

    public function removeUserFromGroup(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'group_id' => 'required|integer',
            'project_id' => 'required|integer'
        ]);

        $this->permissionService->removeUserFromGroup(
            $validated['user_id'],
            $validated['group_id'],
            $validated['project_id']
        );

        return response()->json(['message' => 'User removed from group successfully']);
    }

    public function syncGroupPermissions(Request $request, $groupId)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.menu_id' => 'required|integer',
            'permissions.*.action_id' => 'required|integer'
        ]);

        $this->permissionService->syncGroupPermissions($groupId, $validated['permissions']);

        return response()->json(['message' => 'Permissions synced successfully']);
    }
}