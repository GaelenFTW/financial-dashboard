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
        $uid = $userId ?? Auth::id() ?? 0;
        if (! $uid) return [];

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
            ->map(fn($g) => (int) $g)
            ->unique()
            ->values()
            ->all();

        Cache::put($key, ['ver' => $verTs, 'groups' => $groups], 300);

        return $groups;
    }

    /**
     * Return menu items **only** for the provided user id (or authenticated user)
     */
    public function buildMenuForUser(?int $userId = null): array
    {
        $uid = $userId ?? Auth::id() ?? 0;
        if (! $uid) return [];

        $groups = $this->userGroupIds($uid);
        if (empty($groups)) {
            return [];
        }

        $menu = [];

        // Merge items allowed by any group the user belongs to
        if (in_array(1, $groups, true)) {
            $menu['Payments'][] = ['label' => 'View Payments', 'route' => 'payments.view'];
            $menu['Payments'][] = ['label' => 'Upload Payments', 'route' => 'payments.upload.form'];
            $menu['Purchase Letters'][] = ['label' => 'Table', 'route' => 'purchase_letters.index'];
            $menu['Purchase Letters'][] = ['label' => 'Chart', 'route' => 'purchase_letters.chart'];
            $menu['Reports'][] = ['label' => 'Management Report', 'route' => 'management.report'];
        }

        if (in_array(2, $groups, true)) {
            $menu['Payments'][] = ['label' => 'Upload Payments', 'route' => 'payments.upload.form'];
            $menu['Purchase Letters'][] = ['label' => 'Table', 'route' => 'purchase_letters.index'];
            $menu['Purchase Letters'][] = ['label' => 'Chart', 'route' => 'purchase_letters.chart'];
            $menu['Reports'][] = ['label' => 'Management Report', 'route' => 'management.report'];
        }

        if (in_array(3, $groups, true)) {
            $menu['Purchase Letters'][] = ['label' => 'Table', 'route' => 'purchase_letters.index'];
            $menu['Purchase Letters'][] = ['label' => 'Chart', 'route' => 'purchase_letters.chart'];
            $menu['Reports'][] = ['label' => 'Management Report', 'route' => 'management.report'];
        }

        if (in_array(4, $groups, true)) {
            $menu['Payments'][] = ['label' => 'Upload Payments', 'route' => 'payments.upload.form'];
        }

        // Admin section only for actual admin flag on user (separate from group)
        $user = DB::table('users')->where('id', $uid)->first();
        if ($user && property_exists($user, 'role') && ($user->role === 'admin' || $user->role === 'super_admin')) {
            $menu['Administration'][] = ['label' => 'Admin Panel', 'route' => 'admin.index'];
        }

        // Remove duplicate routes inside each section
        foreach ($menu as $section => $items) {
            $menu[$section] = collect($items)->unique('route')->values()->all();
        }

        return $menu;
    }
}
