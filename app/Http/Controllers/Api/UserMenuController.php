<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserMenuController extends Controller
{
    /**
     * Get accessible menus for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $menus = $user->getAccessibleMenus();
            
            // Build hierarchical menu structure
            $menuTree = $this->buildMenuTree($menus);

            Log::info('User menus retrieved', [
                'user_id' => $user->id,
                'group_id' => $user->group_id,
                'menu_count' => $menuTree->count()
            ]);

            return response()->json([
                'menus' => $menuTree
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting user menus', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Failed to load menus'], 500);
        }
    }

    /**
     * Get all permissions for the authenticated user
     */
    public function permissions(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $permissions = $user->getPermissions();
            
            // Group permissions by menu
            $groupedPermissions = $permissions->groupBy('menu_id')->map(function ($items) {
                return [
                    'menu_id' => $items->first()->menu_id,
                    'menu_name' => $items->first()->menu_name,
                    'link' => $items->first()->link,
                    'actions' => $items->map(function ($item) {
                        return [
                            'action_id' => $item->action_id,
                            'action' => $item->action
                        ];
                    })->values()
                ];
            })->values();

            return response()->json([
                'permissions' => $groupedPermissions
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting user permissions', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Failed to load permissions'], 500);
        }
    }

    /**
     * Build hierarchical menu tree
     */
    private function buildMenuTree($menusByParent)
    {
        $rootMenus = $menusByParent->get(null, collect());
        
        return $rootMenus->map(function ($menu) use ($menusByParent) {
            $children = $menusByParent->get($menu->menu_id, collect());
            
            return [
                'menu_id' => $menu->menu_id,
                'name' => $menu->name,
                'link' => $menu->link,
                'sort_order' => $menu->sort_order,
                'children' => $children->map(function ($child) {
                    return [
                        'menu_id' => $child->menu_id,
                        'name' => $child->name,
                        'link' => $child->link,
                        'sort_order' => $child->sort_order,
                    ];
                })->sortBy('sort_order')->values()
            ];
        })->sortBy('sort_order')->values();
    }
}
