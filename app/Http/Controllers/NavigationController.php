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

        // ✅ Group 1: Full access (can see everything)
        if (in_array(1, $groups, true)) {


            $menuItems['Payments'] = [
                ['label' => 'View Payments', 'route' => 'payments.view'],
                ['label' => 'Upload Payments', 'route' => 'payments.upload.form'],
            ];

            $menuItems['Purchase Letters'] = [
                ['label' => 'Table', 'route' => 'purchase_letters.index'],
                ['label' => 'Chart', 'route' => 'purchase_letters.chart'],
            ];

            $menuItems['Reports'] = [
                ['label' => 'Management Report', 'route' => 'management.report'],
            ];

            // Admins only
            if (auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin()) {
                $menuItems['Administration'] = [
                    ['label' => 'Admin Panel', 'route' => 'admin.index']
                ];
            }
        }

        // ✅ Group 2: Upload + Management + Purchase Letters
        elseif (in_array(2, $groups, true)) {


            $menuItems['Payments'] = [
                ['label' => 'Upload Payments', 'route' => 'payments.upload.form'],
            ];

            $menuItems['Purchase Letters'] = [
                ['label' => 'Table', 'route' => 'purchase_letters.index'],
                ['label' => 'Chart', 'route' => 'purchase_letters.chart'],
            ];

            $menuItems['Reports'] = [
                ['label' => 'Management Report', 'route' => 'management.report'],
            ];
        }

        // ✅ Group 3: View-only (Purchase Letters + Management + Export)
        elseif (in_array(3, $groups, true)) {

            $menuItems['Purchase Letters'] = [
                ['label' => 'Table', 'route' => 'purchase_letters.index'],
                ['label' => 'Chart', 'route' => 'purchase_letters.chart'],
            ];

            $menuItems['Reports'] = [
                ['label' => 'Management Report', 'route' => 'management.report'],
            ];
        }

        // ✅ Group 4: Upload only
        elseif (in_array(4, $groups, true)) {


            $menuItems['Payments'] = [
                ['label' => 'Upload Payments', 'route' => 'payments.upload.form'],
            ];
        }

        View::share('menuItems', $menuItems);
    }
}
