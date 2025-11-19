<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActionAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $menuName, $action)
    {
        $user = $request->user();

        $menu = Menu::where('name', $menuName)->first();
        if (! $menu) return response()->json(['error'=>'Menu not found'],404);

        $action = Action::where('menu_id', $menu->menu_id)
                        ->where('action', $action)
                        ->first();

        if (! $action) return response()->json(['error'=>'Action not found'],404);

        $hasAccess = $user->groups()
            ->join('access_groups','groups.group_id','=','access_groups.group_id')
            ->where('access_groups.menu_id',$menu->menu_id)
            ->where('access_groups.action_id',$action->action_id)
            ->exists();

        if (! $hasAccess)
            return response()->json(['error'=>'No permission'],403);

        return $next($request);
    }

}
