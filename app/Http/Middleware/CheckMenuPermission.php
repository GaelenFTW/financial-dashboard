<?php 

namespace App\Http\Middleware;

use Closure;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class CheckMenuPermission
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function handle(Request $request, Closure $next, $menuId, $action)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $projectId = session('current_project_id');

        if (!$this->permissionService->checkUserPermission($user->user_id, $menuId, $action, $projectId)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}