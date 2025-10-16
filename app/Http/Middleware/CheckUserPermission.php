<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserPermission
{
    public function handle(Request $request, Closure $next, $action)
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Super admins bypass all permission checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        switch ($action) {
            case 'upload':
                if (! $user->canUpload()) {
                    abort(403, 'Access denied. You cannot upload files.');
                }
                break;
            case 'view':
                if (! $user->canView()) {
                    abort(403, 'Access denied. You cannot view data.');
                }
                break;
            case 'export':
                if (! $user->canExport()) {
                    abort(403, 'Access denied. You cannot export data.');
                }
                break;
        }

        return $next($request);
    }
}
