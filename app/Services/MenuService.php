<?php 

namespace App\Services;

use App\Models\Menu;
use App\Models\Action;

class MenuService
{
    public function getAllActiveMenus()
    {
        return Menu::active()->with('actions')->get();
    }

    public function getMenusByParent($parent)
    {
        return Menu::active()->byParent($parent)->with('actions')->get();
    }

    public function getMenuHierarchy()
    {
        $menus = Menu::active()->with('actions')->get();
        $hierarchy = [];

        foreach ($menus as $menu) {
            if (!isset($hierarchy[$menu->parent])) {
                $hierarchy[$menu->parent] = [];
            }
            $hierarchy[$menu->parent][] = $menu;
        }

        return $hierarchy;
    }

    public function createMenu(array $data)
    {
        return Menu::create($data);
    }

    public function updateMenu($menuId, array $data)
    {
        $menu = Menu::findOrFail($menuId);
        $menu->update($data);
        return $menu;
    }

    public function softDeleteMenu($menuId)
    {
        $menu = Menu::findOrFail($menuId);
        $menu->update(['deleted' => 1]);
        return $menu;
    }

    public function toggleMenuStatus($menuId)
    {
        $menu = Menu::findOrFail($menuId);
        $menu->update(['active' => !$menu->active]);
        return $menu;
    }

    public function addActionToMenu($menuId, $actionName, $active = true)
    {
        return Action::create([
            'menu_id' => $menuId,
            'action' => $actionName,
            'active' => $active
        ]);
    }
}