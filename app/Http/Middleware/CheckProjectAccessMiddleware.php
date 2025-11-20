<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckProjectAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get requested project_id from query, route, or body
        $projectId = $request->route('project_id') 
                  ?? $request->input('project_id') 
                  ?? $request->query('project_id');

        if ($projectId) {
            // Check if user has access to this project
            if (!$user->hasProjectAccess($projectId)) {
                Log::warning('Project access denied', [
                    'user_id' => $user->id,
                    'project_id' => $projectId
                ]);

                return response()->json([
                    'message' => 'You do not have access to this project'
                ], 403);
            }   
        }

        return $next($request);
    }
}