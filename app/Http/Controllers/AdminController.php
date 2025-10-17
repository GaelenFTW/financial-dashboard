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
    /**
     * Display the admin panel dashboard
     */
    public function index()
    {
        $usersCount = User::count();
        $projectsCount = MasterProject::count();
        
        return view('admin.index', compact('usersCount', 'projectsCount'));
    }

    /**
     * Display list of users
     */
    public function users()
    {
        $users = User::with('projects')->paginate(20);
        
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show form to create a new user
     */
    public function createUser()
    {
        $roles = UserRole::cases();
        
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a new user
     */
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

    /**
     * Show form to edit a user
     */
    public function editUser(User $user)
    {
        $roles = UserRole::cases();
        $projects = MasterProject::all();
        
        return view('admin.users.edit', compact('user', 'roles', 'projects'));
    }

    /**
     * Update a user
     */
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

        // ✅ Filter & sync valid project assignments
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

    /**
     * Delete a user
     */
    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete yourself!');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully!');
    }

    /**
     * Display list of projects
     */
    public function projects()
    {
        $projects = MasterProject::paginate(20);
        return view('admin.projects.index', compact('projects'));
    }

    /**
     * Show form to create a new project
     */
    public function createProject()
    {
        return view('admin.projects.create');
    }

    /**
     * Store a new project
     */
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

    /**
     * Show form to edit a project
     */
    public function editProject(MasterProject $project)
    {
        return view('admin.projects.edit', compact('project'));
    }

    /**
     * Update a project
     */
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

    /**
     * Delete a project
     */
    public function destroyProject(MasterProject $project)
    {
        $project->delete();

        return redirect()->route('admin.projects')->with('success', 'Project removed successfully!');
    }
}
