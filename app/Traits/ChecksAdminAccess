<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait ChecksAdminAccess
{
    /**
     * Check if current user has admin role (admin or super_admin)
     */
    protected function hasAdminRole(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return in_array(auth()->user()->role, ['admin', 'super_admin']);
    }

    /**
     * Check if current user is super admin
     */
    protected function isSuperAdmin(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->user()->role === 'super_admin';
    }

    /**
     * Check if current user belongs to group_id = 1
     */
    protected function isInAdminGroup(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return DB::table('user_group_access')
            ->where('user_id', auth()->id())
            ->where('group_id', 1)
            ->exists();
    }

    /**
     * Check if current user can manage projects
     * Requires BOTH: admin/super_admin role AND group_id = 1
     */
    protected function canManageProjects(): bool
    {
        return $this->hasAdminRole() && $this->isInAdminGroup();
    }

    /**
     * Require project management access or abort with 403
     */
    protected function requireProjectAccess(): void
    {
        if (!$this->hasAdminRole()) {
            abort(403, 'Unauthorized. Only administrators can access project management.');
        }

        if (!$this->isInAdminGroup()) {
            abort(403, 'Unauthorized. Only users in admin group (group_id = 1) can manage projects.');
        }
    }

    /**
     * Check if current user has access to a specific project
     */
    protected function hasProjectAccess(int $projectId): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // Must be in admin group (group_id = 1)
        return DB::table('user_group_access')
            ->where('user_id', auth()->id())
            ->where('group_id', 1)
            ->where(function($query) use ($projectId) {
                $query->where('project_id', $projectId)
                      ->orWhere('project_id', 999999); // Global access
            })
            ->exists();
    }

    /**
     * Get all project IDs that current user has access to
     * Only returns projects if user is in group_id = 1
     */
    protected function getUserProjectIds(): array
    {
        if (!auth()->check() || !$this->isInAdminGroup()) {
            return [];
        }

        $projectIds = DB::table('user_group_access')
            ->where('user_id', auth()->id())
            ->where('group_id', 1)
            ->pluck('project_id')
            ->toArray();

        // If user has global access (999999), return all project IDs
        if (in_array(999999, $projectIds)) {
            return DB::table('projects')->pluck('project_id')->toArray();
        }

        return $projectIds;
    }

    /**
     * Check if user is regular admin (not super_admin)
     */
    protected function isRegularAdmin(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->user()->role === 'admin';
    }
}