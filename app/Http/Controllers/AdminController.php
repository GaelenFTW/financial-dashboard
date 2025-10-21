<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MasterProject;
use App\Enums\UserRole;
use App\Enums\ProjectRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;

class AdminController extends Controller
{
    public function index()
    {
        $usersCount = User::count();
        $projectsCount = MasterProject::count(); 
        
        return view('admin.index', compact('usersCount', 'projectsCount'));
    }

    public function users()
    {
        $users = User::with('projects')->paginate(20);
        
        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        $roles = UserRole::cases();
        
        return view('admin.users.create', compact('roles'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', new Enum(UserRole::class)],
            'employee_id' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users')->with('success', 'User created successfully!');
    }

    public function editUser(User $user)
    {
        $roles = UserRole::cases();
        $projects = MasterProject::all();
        
        return view('admin.users.edit', compact('user', 'roles', 'projects'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => ['required', new Enum(UserRole::class)],
            'employee_id' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        // Filter & sync valid project assignments
        if ($request->has('projects')) {
            $validProjectIds = \DB::table('master_project')->pluck('project_id')->toArray();

            $projectData = [];
            foreach ($request->projects as $projectId => $data) {
                // Only add if project_id exists in master_project
                if (
                    isset($data['assigned']) && 
                    $data['assigned'] && 
                    in_array((int) $projectId, $validProjectIds)
                ) {
                    $projectData[$projectId] = [
                        'role' => $data['role'] ?? ProjectRole::VIEWER->value,
                    ];
                }
            }

            $user->projects()->sync($projectData);
        }
        // dd(request()->input('projects'));

        return redirect()->route('admin.users')->with('success', 'User updated successfully!');
    }

    public function editUserPermissions(User $user)
    {
        $permissions = \App\Models\Permission::all();
        $userPermissions = $user->permissions()->pluck('permissions.id')->toArray();
        $projects = MasterProject::all();

        return view('admin.users.permissions', compact('user', 'permissions', 'userPermissions', 'projects'));
    }

    public function updateUserPermissions(Request $request, User $user)
    {
        // 1. Sync permissions
        $permissionIds = $request->input('permissions', []);
        $user->permissions()->sync($permissionIds);

        // 2. Sync project assignments (from table)
        if ($request->has('projects')) {
            $validProjectIds = \DB::table('master_project')->pluck('project_id')->toArray();

            $projectData = [];
            foreach ($request->projects as $projectId => $data) {
                if (
                    isset($data['assigned']) && 
                    $data['assigned'] && 
                    in_array((int) $projectId, $validProjectIds)
                ) {
                    $projectData[$projectId] = [
                        'role' => $data['role'] ?? \App\Enums\ProjectRole::VIEWER->value,
                    ];
                }
            }

            $user->projects()->sync($projectData);
        }

        return redirect()->route('admin.users')->with('success', 'Permissions and project assignments updated successfully!');
    }


    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete yourself!');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully!');
    }

    // Display projects
    public function projects()
    {
        $projects = MasterProject::paginate(20);
        return view('admin.projects.index', compact('projects'));
    }

    public function createProject()
    {
        return view('admin.projects.create');
    }

    public function storeProject(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|unique:master_project,project_id',
            'sh' => 'nullable|string|max:255',
            'code' => 'required|string|unique:master_project,code',
            'name' => 'required|string|max:255',
        ]);

        MasterProject::create($validated);

        return redirect()->route('admin.projects')->with('success', 'Project created successfully!');
    }

    public function editProject(MasterProject $project)
    {
        return view('admin.projects.edit', compact('project'));
    }

    public function updateProject(Request $request, MasterProject $project)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|unique:master_project,project_id,' . $project->project_id . ',project_id',
            'code' => 'required|string|unique:master_project,code,' . $project->project_id . ',project_id',
            'sh' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
        ]);

        $project->update($validated);

        return redirect()->route('admin.projects')->with('success', 'Project updated successfully!');
    }

    public function destroyProject(MasterProject $project)
    {
        $project->delete();

        return redirect()->route('admin.projects')->with('success', 'Project removed successfully!');
    }
}
