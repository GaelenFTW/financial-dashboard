<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectAccessService;

class ProjectController extends Controller
{
    public function __construct(private ProjectAccessService $service) {}

    public function index()
    {
        return auth()->user()->accessibleProjects()->paginate();
    }

    public function show(Project $project)
    {
        // Middleware handles access check
        return $project;
    }

    public function grantAccess(User $user, Project $project)
    {
        $this->service->grantProjectAccess($user, $project);
        return response()->json(['message' => 'Access granted']);
    }
}