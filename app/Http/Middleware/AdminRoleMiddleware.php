<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    /**
     * Handle an incoming request.
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
        
        // Query directly from database to ensure we get the role
        $user = DB::table('users')->where('id', $userId)->first();
        
        if (!$user) {
            auth()->logout();
            return redirect()->route('login.form')
                ->with('error', 'User not found.');
        }
        
        // Check if user has admin or super_admin role
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            abort(403, 'Unauthorized. Only administrators can access this area.');
        }

        return $next($request);
    }
}