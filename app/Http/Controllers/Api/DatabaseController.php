<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Menu;
use App\Models\Action;
use App\Models\AccessGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseController extends Controller
{
    // ============= GROUPS =============
    
    public function getGroups()
    {
        try {
            $groups = Group::orderBy('group_id')->get();
            return response()->json(['groups' => $groups]);
        } catch (\Exception $e) {
            Log::error('Error fetching groups', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch groups'], 500);
        }
    }

    public function storeGroup(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:groups,name',
            ]);

            $group = Group::create($validated);

            return response()->json([
                'message' => 'Group created successfully',
                'group' => $group
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating group', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create group'], 500);
        }
    }

    public function updateGroup(Request $request, $id)
    {
        try {
            $group = Group::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:groups,name,' . $id . ',group_id',
            ]);

            $group->update($validated);

            return response()->json([
                'message' => 'Group updated successfully',
                'group' => $group
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating group', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update group'], 500);
        }
    }

    public function deleteGroup($id)
    {
        try {
            $group = Group::findOrFail($id);
            
            // Check if group has users
            $hasUsers = DB::table('users')->where('group_id', $id)->exists();
            if ($hasUsers) {
                return response()->json(['error' => 'Cannot delete group with assigned users'], 400);
            }

            // Delete associated access groups
            DB::table('access_groups')->where('group_id', $id)->delete();
            
            $group->delete();

            return response()->json(['message' => 'Group deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting group', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete group'], 500);
        }
    }

    // ============= MENUS =============
    
    public function getMenus()
    {
        try {
            $menus = Menu::orderBy('sort_order')->get();
            return response()->json(['menus' => $menus]);
        } catch (\Exception $e) {
            Log::error('Error fetching menus', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch menus'], 500);
        }
    }

    public function storeMenu(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'link' => 'nullable|string|max:255',
                'parent_id' => 'nullable|exists:menus,menu_id',
                'sort_order' => 'required|integer|min:0',
                'active' => 'boolean'
            ]);

            $validated['deleted'] = 0;
            $validated['active'] = $validated['active'] ?? true;

            DB::beginTransaction();
            
            $menu = Menu::create($validated);

            // Automatically create CRUD actions for the new menu
            $crudActions = ['create', 'read', 'update', 'delete'];
            foreach ($crudActions as $actionName) {
                Action::create([
                    'menu_id' => $menu->menu_id,
                    'action' => $actionName,
                    'active' => true
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Menu and actions created successfully',
                'menu' => $menu
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating menu', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create menu'], 500);
        }
    }

    public function updateMenu(Request $request, $id)
    {
        try {
            $menu = Menu::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'link' => 'nullable|string|max:255',
                'parent_id' => 'nullable|exists:menus,menu_id',
                'sort_order' => 'required|integer|min:0',
                'active' => 'boolean'
            ]);

            $menu->update($validated);

            return response()->json([
                'message' => 'Menu updated successfully',
                'menu' => $menu
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating menu', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update menu'], 500);
        }
    }

    public function deleteMenu($id)
    {
        try {
            $menu = Menu::findOrFail($id);
            
            // Check if menu has children
            $hasChildren = Menu::where('parent_id', $id)->exists();
            if ($hasChildren) {
                return response()->json(['error' => 'Cannot delete menu with sub-menus'], 400);
            }

            DB::beginTransaction();

            // Delete associated actions
            Action::where('menu_id', $id)->delete();

            // Delete associated access groups
            DB::table('access_groups')->where('menu_id', $id)->delete();

            // Soft delete menu (set deleted = 1)
            $menu->update(['deleted' => 1, 'active' => 0]);

            DB::commit();

            return response()->json(['message' => 'Menu deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting menu', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete menu'], 500);
        }
    }

    // ============= ACTIONS =============
    
    public function getActions()
    {
        try {
            $actions = Action::with('menu:menu_id,name')
                ->orderBy('menu_id')
                ->orderBy('action')
                ->get();
            return response()->json(['actions' => $actions]);
        } catch (\Exception $e) {
            Log::error('Error fetching actions', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch actions'], 500);
        }
    }

    public function storeAction(Request $request)
    {
        try {
            $validated = $request->validate([
                'menu_id' => 'required|exists:menus,menu_id',
                'action' => 'required|string|max:255',
                'active' => 'boolean'
            ]);

            // Check if action already exists for this menu
            $exists = Action::where('menu_id', $validated['menu_id'])
                ->where('action', $validated['action'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'This action already exists for the selected menu'
                ], 400);
            }

            $validated['active'] = $validated['active'] ?? true;

            $action = Action::create($validated);

            return response()->json([
                'message' => 'Action created successfully',
                'action' => $action->load('menu')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating action', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create action'], 500);
        }
    }

    public function updateAction(Request $request, $id)
    {
        try {
            $action = Action::findOrFail($id);
            
            $validated = $request->validate([
                'menu_id' => 'required|exists:menus,menu_id',
                'action' => 'required|string|max:255',
                'active' => 'boolean'
            ]);

            // Check if action already exists for this menu (excluding current action)
            $exists = Action::where('menu_id', $validated['menu_id'])
                ->where('action', $validated['action'])
                ->where('action_id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'This action already exists for the selected menu'
                ], 400);
            }

            $action->update($validated);

            return response()->json([
                'message' => 'Action updated successfully',
                'action' => $action->load('menu')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating action', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update action'], 500);
        }
    }

    public function deleteAction($id)
    {
        try {
            $action = Action::findOrFail($id);
            
            // Check if action is used in access_groups
            $isUsed = DB::table('access_groups')->where('action_id', $id)->exists();
            if ($isUsed) {
                return response()->json(['error' => 'Cannot delete action that is in use'], 400);
            }

            $action->delete();

            return response()->json(['message' => 'Action deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting action', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete action'], 500);
        }
    }

    // ============= OVERVIEW =============
    
    public function overview()
    {
        try {
            $stats = [
                'total_groups' => Group::count(),
                'total_menus' => Menu::where('deleted', 0)->count(),
                'total_actions' => Action::count(),
                'total_permissions' => DB::table('access_groups')->count(),
                'active_menus' => Menu::where('active', 1)->where('deleted', 0)->count(),
                'active_actions' => Action::where('active', 1)->count(),
            ];

            return response()->json(['stats' => $stats]);
        } catch (\Exception $e) {
            Log::error('Error fetching overview', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch overview'], 500);
        }
    }
}