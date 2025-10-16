<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccessService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private ProjectAccessService $service) {}

    // Dashboard
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_projects' => Project::count(),
            'recent_users' => User::latest()->limit(5)->get(),
            'permissions' => Permission::cases(),
        ];
        return view('admin.dashboard', $stats);
    }

    // ===== USERS MANAGEMENT =====

    public function usersIndex()
    {
        $users = User::paginate(15);
        $permissions = Permission::cases();
        return view('admin.users.index', compact('users', 'permissions'));
    }

    public function usersEdit(User $user)
    {
        $permissions = Permission::cases();
        $userPermissions = [];
        
        foreach ($permissions as $permission) {
            $userPermissions[$permission->name] = $user->hasPermission($permission->value);
        }

        return view('admin.users.edit', compact('user', 'permissions', 'userPermissions'));
    }

    public function usersUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'position' => 'nullable|string|max:255',
            'permissions' => 'required|array',
        ]);

        $permissionValue = 0;

        foreach ($validated['permissions'] as $permissionName => $checked) {
            if ($checked) {
                // Access enum dynamically via static property
                $permissionValue |= Permission::$permissionName->value;
            }
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'position' => $validated['position'],
            'permissions' => $permissionValue,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully');
    }

    public function userProjects(User $user)
    {
        $allProjects = Project::orderBy('name')->get();
        $userProjects = $user->projects()->pluck('projects.project_id')->toArray();

        return view('admin.users.projects', compact('user', 'allProjects', 'userProjects'));
    }

    public function updateUserProjects(Request $request, User $user)
    {
        $validated = $request->validate([
            'project_ids' => 'required|array',
            'project_ids.*' => 'integer|exists:projects,project_id',
        ]);

        $this->service->setProjectPermissions($user, $validated['project_ids']);

        return redirect()->route('admin.users.index')->with('success', 'User project access updated');
    }

    // ===== PROJECTS MANAGEMENT =====

    public function projectsIndex()
    {
        $projects = Project::paginate(15);
        return view('admin.projects.index', compact('projects'));
    }

    public function projectsCreate()
    {
        return view('admin.projects.create');
    }

    public function projectsStore(Request $request)
    {
        $validated = $request->validate([
            'sh' => 'required|integer|min:0|max:255',
            'code' => 'required|string|max:255|unique:projects,code',
            'name' => 'required|string|max:255',
        ]);

        Project::create($validated);

        return redirect()->route('admin.projects.index')->with('success', 'Project created successfully');
    }

    public function projectsEdit(Project $project)
    {
        return view('admin.projects.edit', compact('project'));
    }

    public function projectsUpdate(Request $request, Project $project)
    {
        $validated = $request->validate([
            'sh' => 'required|integer|min:0|max:255',
            'code' => 'required|string|max:255|unique:projects,code,' . $project->project_id . ',project_id',
            'name' => 'required|string|max:255',
        ]);

        $project->update($validated);

        return redirect()->route('admin.projects.index')->with('success', 'Project updated successfully');
    }

    public function projectsDestroy(Project $project)
    {
        $project->delete();
        return redirect()->route('admin.projects.index')->with('success', 'Project deleted successfully');
    }
}