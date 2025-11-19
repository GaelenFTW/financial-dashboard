<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class UserMenuService
{
    public static function getMenus($userId)
    {
        $menus = DB::table('menus as m')
            ->leftJoin('access_groups as ag', 'ag.menu_id', '=', 'm.menu_id')
            ->leftJoin('user_groups as ug', 'ug.group_id', '=', 'ag.group_id')
            ->where('ug.user_id', $userId)
            ->where('m.active', 1)
            ->where('m.deleted', 0)
            ->select('m.menu_id', 'm.name', 'm.link', 'm.parent_id', 'm.sort_order')
            ->orderBy('m.sort_order')
            ->get();

        return self::buildTree($menus);
    }

    private static function buildTree($menus, $parent = null)
    {
        $branch = [];

        foreach ($menus as $menu) {
            if ($menu->parent_id == $parent) {

                $children = self::buildTree($menus, $menu->menu_id);

                $item = [
                    'menu_id'   => $menu->menu_id,
                    'name'      => $menu->name,
                    'link'      => $menu->link,
                    'children'  => $children
                ];

                $branch[] = $item;
            }
        }

        return $branch;
    }
}
