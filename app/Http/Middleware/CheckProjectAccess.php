<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckProjectAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $permission  The permission level required (view, edit, admin)
     */
    public function handle(Request $request, Closure $next, ?string $permission = 'view'): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        
        // Get project_id from route parameter or request
        $projectId = $request->route('project_id') ?? $request->input('project_id');
        
        if (!$projectId) {
            abort(400, 'Project ID is required.');
        }

        // Check if user has access to the project
        if (!$user->hasProjectAccess($projectId)) {
            abort(403, 'Access denied. You do not have access to this project.');
        }

        // Check specific permission level
        switch ($permission) {
            case 'admin':
                if (!$user->isProjectAdmin($projectId)) {
                    abort(403, 'Access denied. You need admin privileges for this project.');
                }
                break;
            case 'edit':
                if (!$user->canEditProject($projectId)) {
                    abort(403, 'Access denied. You cannot edit this project.');
                }
                break;
            case 'view':
                // Already checked access above
                break;
        }

        return $next($request);
    }
}
