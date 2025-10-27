<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Menu;

class CheckAccess
{
    public function handle(Request $request, Closure $next, $actionName = 'read')
    {
        $user = Auth::user();
        if (!$user) abort(401, 'Unauthorized');

        $path = trim($request->path(), '/'); // e.g. "admin/project"

        // 1. Find menu by link
        $menu = Menu::where('link', $path)->where('active', 1)->where('deleted', 0)->first();
        if (!$menu) return $next($request); // Not a registered menu

        // 2. Get all group_ids the user belongs to
        $groupIds = $user->groupAccesses()->pluck('group_id')->toArray();

        // 3. Check if any of those groups have access to the menu + action
        $hasAccess = \DB::table('access_group')
            ->join('action', 'action.action_id', '=', 'access_group.action_id')
            ->whereIn('access_group.group_id', $groupIds)
            ->where('access_group.menu_id', $menu->menu_id)
            ->whereRaw('LOWER(action.action) = ?', [strtolower($actionName)])
            ->exists();

        if (!$hasAccess) {
            abort(403, "You do not have {$actionName} access to this page.");
        }

        return $next($request);
    }
}
