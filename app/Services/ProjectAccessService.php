<?php

namespace App\Services;

use App\Models\MasterProject;
use App\Models\User;

class ProjectAccessService
{
    public function grantProjectAccess(User $user, MasterProject|int $project): void
    {
        $projectId = $project instanceof MasterProject ? $project->project_id : $project;
        $user->projects()->syncWithoutDetaching([$projectId]);
    }

    public function revokeProjectAccess(User $user, MasterProject|int $project): void
    {
        $projectId = $project instanceof MasterProject ? $project->project_id : $project;
        $user->projects()->detach($projectId);
    }

    public function grantMultipleProjects(User $user, array $projectIds): void
    {
        $user->projects()->syncWithoutDetaching($projectIds);
    }

    public function getProjectsForUser(User $user)
    {
        return $user->accessibleProjects()->paginate();
    }
}