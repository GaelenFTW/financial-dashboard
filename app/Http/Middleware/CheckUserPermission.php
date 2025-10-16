<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserPermission
{
    public function handle(Request $request, Closure $next, $action)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // 1️⃣ Check project access
        if ($request->route('project')) {
            $projectId = $request->route('project');
            if (!$user->hasAccessToProject($projectId)) {
                abort(403, 'Access denied: you do not have access to this project.');
            }
        }

        // 2️⃣ Check specific permissions
        switch ($action) {
            case 'view':
                if (!$user->canView()) abort(403, 'Access denied: cannot view.');
                break;
            case 'upload':
                if (!$user->canUpload()) abort(403, 'Access denied: cannot upload.');
                break;
            case 'export':
                if (!$user->canExport()) abort(403, 'Access denied: cannot export.');
                break;
        }

        return $next($request);
    }
}
