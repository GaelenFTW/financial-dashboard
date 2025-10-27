<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckGroupAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$allowedGroups)
    {
        $user = Auth::user();

        // Not logged in
        if (!$user) {
            return redirect()->route('login.form');
        }

        // Get user's group IDs
        $userGroupIds = DB::table('user_group_access')
            ->where('user_id', $user->id)
            ->pluck('group_id')
            ->toArray();

        // Check if at least one group matches
        if (!empty(array_intersect($allowedGroups, $userGroupIds))) {
            return $next($request);
        }

        // Show a "403" blade with navbar (not plain text)
        return response()->view('errors.403', [], 403);
    }
}
