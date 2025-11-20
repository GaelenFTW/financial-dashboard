<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $menuId
     * @param  int  $actionId
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $menuId, $actionId)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user has permission
        if (!$user->hasPermission($menuId, $actionId)) {
            Log::warning('Permission denied', [
                'user_id' => $user->id,
                'menu_id' => $menuId,
                'action_id' => $actionId
            ]);

            return response()->json([
                'message' => 'You do not have permission to perform this action'
            ], 403);
        }

        return $next($request);
    }
}