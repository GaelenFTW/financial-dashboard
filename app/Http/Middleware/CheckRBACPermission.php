<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckRBACPermission
{
    /**
     * Handle an incoming request.
     * 
     * Usage: ->middleware('rbac:menu_id,action_name')
     * Example: ->middleware('rbac:1,read') for Dashboard read access
     */
    public function handle(Request $request, Closure $next, $menuId, $actionName)
    {
        $user = auth()->user();

        if (!$user) {
            Log::warning('RBAC: Unauthenticated user attempted access', [
                'menu_id' => $menuId,
                'action' => $actionName
            ]);
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (!$user->canPerformAction($menuId, $actionName)) {
            Log::warning('RBAC: Permission denied', [
                'user_id' => $user->id,
                'group_id' => $user->group_id,
                'menu_id' => $menuId,
                'action' => $actionName
            ]);
            return response()->json(['error' => 'Forbidden: Insufficient permissions'], 403);
        }

        Log::info('RBAC: Permission granted', [
            'user_id' => $user->id,
            'menu_id' => $menuId,
            'action' => $actionName
        ]);

        return $next($request);
    }
}