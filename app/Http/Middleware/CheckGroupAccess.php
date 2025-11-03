<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckGroupAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|string  ...$allowedGroups
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$allowedGroups): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login.form')->with('error', 'Please login to access this page.');
        }

        // Admin users bypass group checks
        if (in_array($user->role ?? '', ['admin', 'super_admin'])) {
            return $next($request);
        }

        // Convert allowed groups to integers for strict comparison
        $allowedGroups = array_map('intval', $allowedGroups);

        $userGroupIds = $this->getUserGroupIds($user->id);

        // Check if user belongs to at least one allowed group
        if (!empty(array_intersect($allowedGroups, $userGroupIds))) {
            return $next($request);
        }

        // Show 403 error view with navbar
        return response()->view('errors.403', [
            'message' => 'You don\'t have permission to access this page.',
            'requiredGroups' => $allowedGroups,
        ], 403);
    }

    /**
     * Get user's group IDs with caching
     *
     * @param  int  $userId
     * @return array
     */
    protected function getUserGroupIds(int $userId): array
    {
        $cacheKey = "user_groups:{$userId}";

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            return DB::table('user_group_access')
                ->where('user_id', $userId)
                ->distinct()
                ->pluck('group_id')
                ->map(fn($g) => (int) $g)
                ->toArray();
        });
    }
}