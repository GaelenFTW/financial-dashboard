<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NavigationController extends Controller
{
    protected function userGroupIds(?int $userId = null): array
    {
        $uid = $userId ?? Auth::id() ?? 0;
        if (! $uid) return [];

        // Get the latest update timestamp for cache versioning
        $verTs = (int) optional(
            DB::table('user_group_access')
                ->where('user_id', $uid)
                ->max('updated_at')
        )->getTimestamp() ?: 0;

        $key = "nav:user-groups:$uid";
        $cached = Cache::get($key);

        // Return cached data if still valid
        if (is_array($cached) && ($cached['ver'] ?? -1) === $verTs) {
            return $cached['groups'] ?? [];
        }

        // Fetch unique group IDs for this user (across all projects)
        $groups = DB::table('user_group_access')
            ->where('user_id', $uid)
            ->distinct()
            ->pluck('group_id')
            ->map(fn($g) => (int) $g)
            ->unique()
            ->values()
            ->all();

        // Cache for 5 minutes
        Cache::put($key, ['ver' => $verTs, 'groups' => $groups], 300);

        return $groups;
    }

    /**
     * Build menu items based on user's group memberships
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

        // Group 1 - Full Access (Payments, Purchase Letters, Reports)
        if (in_array(1, $groups, true)) {
            $menu['Payments'][] = ['label' => 'View Payments', 'route' => 'payments.view'];
            $menu['Payments'][] = ['label' => 'Upload Payments', 'route' => 'payments.upload.form'];
            $menu['Purchase Letters'][] = ['label' => 'Table', 'route' => 'purchase_letters.index'];
            $menu['Purchase Letters'][] = ['label' => 'Chart', 'route' => 'purchase_letters.chart'];
            $menu['Reports'][] = ['label' => 'Management Report', 'route' => 'management.report'];
        }

        // Group 2 - Upload + Purchase Letters + Reports
        if (in_array(2, $groups, true)) {
            $menu['Payments'][] = ['label' => 'Upload Payments', 'route' => 'payments.upload.form'];
            $menu['Purchase Letters'][] = ['label' => 'Table', 'route' => 'purchase_letters.index'];
            $menu['Purchase Letters'][] = ['label' => 'Chart', 'route' => 'purchase_letters.chart'];
            $menu['Reports'][] = ['label' => 'Management Report', 'route' => 'management.report'];
        }

        // Group 3 - Purchase Letters + Reports Only
        if (in_array(3, $groups, true)) {
            $menu['Purchase Letters'][] = ['label' => 'Table', 'route' => 'purchase_letters.index'];
            $menu['Purchase Letters'][] = ['label' => 'Chart', 'route' => 'purchase_letters.chart'];
            $menu['Reports'][] = ['label' => 'Management Report', 'route' => 'management.report'];
        }

        // Group 4 - Upload Payments Only
        if (in_array(4, $groups, true)) {
            $menu['Payments'][] = ['label' => 'Upload Payments', 'route' => 'payments.upload.form'];
            $menu['Payments'][] = ['label' => 'View Payments', 'route' => 'payments.view'];

        }

        // Admin section - check user role
        $user = DB::table('users')->where('id', $uid)->first();
        if ($user && property_exists($user, 'role') && in_array($user->role, ['admin', 'super_admin'])) {
            $menu['Administration'][] = ['label' => 'Admin Panel', 'route' => 'admin.index'];
        }

        // Remove duplicate routes within each section
        foreach ($menu as $section => $items) {
            $menu[$section] = collect($items)->unique('route')->values()->all();
        }

        return $menu;
    }
}