<?php

namespace App\Http\Controllers\Api;

use App\Models\Menu;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserMenuController extends Controller
{

    public function index()
    {
        $userId = Auth::id();

        // 1) allowed menu ids for this user (from access_groups -> user_groups)
        $allowedMenuIds = DB::table('access_groups as ag')
            ->join('user_groups as ug', 'ug.group_id', '=', 'ag.group_id')
            ->where('ug.user_id', $userId)
            ->pluck('ag.menu_id')
            ->map(function($v){ return (int) $v; }) // ensure ints
            ->unique()
            ->values()
            ->toArray();

        // If no allowed menus, return empty array quickly
        if (empty($allowedMenuIds)) {
            return response()->json([]);
        }

        // 2) Load all active (non-deleted) menus — we need parents to build the tree
        $menus = Menu::where('active', 1)
            ->where('deleted', 0)
            ->orderBy('sort_order') // if sort_order missing it's okay; remove if not present
            ->get(['menu_id', 'name', 'link', 'parent_id']);

        // Normalize collection to array of simple objects
        $items = $menus->map(function($m){
            return (object)[
                'menu_id'   => (int) $m->menu_id,
                'name'      => $m->name,
                'link'      => $m->link,
                'parent_id' => $m->parent_id === null ? null : (int) $m->parent_id,
            ];
        })->values();

        // 3) Build a map (id => node)
        $map = [];
        foreach ($items as $it) {
            $map[$it->menu_id] = [
                'menu_id'   => $it->menu_id,
                'name'      => $it->name,
                'link'      => $it->link,
                'parent_id' => $it->parent_id,
                'children'  => []
            ];
        }

        // 4) Attach children to parents
        $roots = [];
        foreach ($map as $id => &$node) {
            $parentId = $node['parent_id'];
            if ($parentId === null || !isset($map[$parentId])) {
                $roots[$id] = &$node;
            } else {
                $map[$parentId]['children'][] = &$node;
            }
        }
        unset($node); // break reference

        // 5) Prune the tree: keep only branches that contain at least one allowed menu id
        $allowedLookup = array_flip($allowedMenuIds); // quick lookup

        $prune = function($node) use (&$prune, $allowedLookup) {
            // If node itself is allowed
            $isAllowed = isset($allowedLookup[$node['menu_id']]);

            // Recurse on children and keep only those that return true
            $children = [];
            foreach ($node['children'] as $child) {
                if ($prune($child)) {
                    $children[] = $child;
                }
            }
            $node['children'] = $children;

            // Keep node if itself allowed or has any allowed descendant
            return $isAllowed || !empty($node['children']);
        };

        // Apply prune to roots and collect final result
        $final = [];
        foreach ($roots as $root) {
            if ($prune($root)) {
                // remove parent_id from top-level output if you prefer
                $final[] = $root;
            }
        }

        return response()->json(array_values($final));
    }

}
