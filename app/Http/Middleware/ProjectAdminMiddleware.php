<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProjectAdminMiddleware
{
    /**
     * Handle an incoming request.
     * Only allows users with group_id = 1 to manage projects
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login.form')
                ->with('error', 'Please login to access this page.');
        }

        $userId = auth()->id();
        
        // Check if user belongs to group_id = 1
        $hasProjectAccess = DB::table('user_group_access')
            ->where('user_id', $userId)
            ->where('group_id', 1)
            ->exists();

        if (!$hasProjectAccess) {
            abort(403, 'Unauthorized. Only users in admin group (group_id = 1) can manage projects.');
        }

        return $next($request);
    }
}