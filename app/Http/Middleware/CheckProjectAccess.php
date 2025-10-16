<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProjectAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        $projectId = $request->route('project_id') ?? $request->route('project')?->project_id;

        if (!$projectId) {
            return $next($request);
        }

        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!auth()->user()->canAccessProject($projectId)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
