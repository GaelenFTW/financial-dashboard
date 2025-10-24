<?php
// filepath: c:\Users\Intern01\financial-dashboard\app\Http\Controllers\NavigationController.php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NavigationController extends Controller
{
    // Cache the user’s group ids
    protected function userGroupIds(?int $userId = null): array
    {
        $uid = $userId ?? (Auth::id() ?? 0);
        if (!$uid) return [];

        return Cache::remember("nav:user-groups:$uid", 60, function () use ($uid) {
            return DB::table('user_group_access')
                ->where('user_id', $uid)
                ->pluck('group_id')
                ->map(fn ($g) => (int)$g)
                ->unique()
                ->values()
                ->all();
        });
    }

    // Central rule: decide menu mode from groups (change rules here only)
    public function menuMode(?int $userId = null): string
    {
        $groups = $this->userGroupIds($userId);
        if (in_array(1, $groups, true)) return 'full';     // group 1 => full menu
        if (in_array(2, $groups, true)) return 'limited';  // group 2 => only Purchase Letters + Management Report
        return 'none';                                     // no dropdown
    }

    // Keep the existing Blade API working (your Blade already calls userHasGroup)
    public function userHasGroup(?int $userId = null, int $groupId = 1): bool
    {
        $mode = $this->menuMode($userId);

        if ($groupId === 1) {
            // Only return true when the user should see full menu
            return $mode === 'full';
        }

        if ($groupId === 2) {
            // Limited menu users (and full menu users) satisfy "has group 2" checks
            // Blade uses if($isG1) ... elseif($isG2) so full still wins.
            return $mode === 'limited' || $mode === 'full';
        }

        // Fallback to actual membership for other groups if needed later
        return in_array($groupId, $this->userGroupIds($userId), true);
    }

    // Optional helper if you ever want to fetch allowed links programmatically
    public function allowedMenuItems(?int $userId = null): array
    {
        return match ($this->menuMode($userId)) {
            'full' => [
                'payments.view', 'payments.upload',
                'management.report',
                'purchase_letters.index', 'purchase_letters.chart',
                'admin.index', // shown only if your Blade also checks isAdmin
            ],
            'limited' => [
                'management.report',
                'purchase_letters.index', 'purchase_letters.chart',
            ],
            default => [],
        };
    }
}