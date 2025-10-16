<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;

class ProjectAccessService
{
    public function grantProjectAccess(User $user, Project|int $project): void
    {
        $projectId = $project instanceof Project ? $project->project_id : $project;
        $user->projects()->syncWithoutDetaching([$projectId]);
    }

    public function revokeProjectAccess(User $user, Project|int $project): void
    {
        $projectId = $project instanceof Project ? $project->project_id : $project;
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