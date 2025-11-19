<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $menuName)
    {
        $user = $request->user();
        $menu = Menu::where('name', $menuName)->first();

        if (! $menu) {
            return response()->json(['error' => 'Menu not found'], 404);
        }

        $hasAccess = $user->groups()
            ->join('access_groups', 'groups.group_id', '=', 'access_groups.group_id')
            ->where('access_groups.menu_id', $menu->menu_id)
            ->exists();

        if (! $hasAccess) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }

}
