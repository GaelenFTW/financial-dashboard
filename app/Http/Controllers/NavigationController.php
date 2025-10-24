<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class NavigationController extends Controller
{
    protected function userGroupIds(?int $userId = null): array
    {
        $uid = $userId ?? (Auth::id() ?? 0);
        if (!$uid) return [];

        $verTs = (int) optional(
            DB::table('user_group_access')->where('user_id', $uid)->max('updated_at')
        )->getTimestamp() ?: 0;

        $key = "nav:user-groups:$uid";
        $cached = Cache::get($key);

        if (is_array($cached) && ($cached['ver'] ?? -1) === $verTs) {
            return $cached['groups'] ?? [];
        }

        $groups = DB::table('user_group_access')
            ->where('user_id', $uid)
            ->pluck('group_id')
            ->map(fn ($g) => (int) $g)
            ->unique()
            ->values()
            ->all();

        Cache::put($key, ['ver' => $verTs, 'groups' => $groups], 300);
        return $groups;
    }

    public function loadMenu(): void
    {
        $groups = $this->userGroupIds();
        
        if (empty($groups)) {
            View::share('menuItems', []);
            return;
        }

        $menuItems = [];

        // Group 1: Full Access
        if (in_array(1, $groups, true)) {
            $menuItems['Payments'] = [
                ['label' => 'View', 'route' => 'payments.view'],
                ['label' => 'Upload', 'route' => 'payments.upload'],
                ['divider' => true],
                ['label' => 'Management Report', 'route' => 'management.report'],
            ];
            
            $menuItems['Purchase Letters'] = [
                ['label' => 'Table', 'route' => 'purchase_letters.index'],
                ['label' => 'Chart', 'route' => 'purchase_letters.chart'],
            ];
            
            // Admin section only for admins
            if (auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin()) {
                $menuItems['Administration'] = [
                    ['label' => 'Admin Panel', 'route' => 'admin.index']
                ];
            }
        }

        // Group 2: Limited Access
        if (in_array(2, $groups, true) && !in_array(1, $groups, true)) {
            $menuItems['Management Report'] = [
                ['label' => 'Management Report', 'route' => 'management.report'],
            ];
            
            $menuItems['Purchase Letters'] = [
                ['label' => 'Table', 'route' => 'purchase_letters.index'],
                ['label' => 'Chart', 'route' => 'purchase_letters.chart'],
            ];
        }

        // Group 3: Restricted Access
        if (in_array(3, $groups, true) && !in_array(1, $groups, true) && !in_array(2, $groups, true)) {
            $menuItems['Purchase Letters'] = [
                ['label' => 'Table', 'route' => 'purchase_letters.index'],
            ];
        }

        // Group 4: Upload Only
        if (in_array(4, $groups, true) && !in_array(1, $groups, true)) {
            $menuItems['Payments'] = [
                ['label' => 'Upload', 'route' => 'payments.upload'],
            ];
        }

        View::share('menuItems', $menuItems);
    }
}