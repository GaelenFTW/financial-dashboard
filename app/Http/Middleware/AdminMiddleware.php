<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!auth()->check() || !auth()->user()->hasPermission(Permission::SUPER_ADMIN->value)) {
            abort(403, 'Only Super Admins can access this area');
        }

        return $next($request);
    }
}
