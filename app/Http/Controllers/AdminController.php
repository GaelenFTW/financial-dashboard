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
    protected $jwtController;

    public function __construct(JWTController $jwtController)
    {
        $this->jwtController = $jwtController;
    }

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

        // Only update password if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        // Update project assignments if provided
        if ($request->has('projects')) {
            $projectData = [];
            foreach ($request->projects as $projectId => $data) {
                if (isset($data['assigned']) && $data['assigned']) {
                    $projectData[$projectId] = ['role' => $data['role'] ?? ProjectRole::VIEWER->value];
                }
            }
            $user->projects()->sync($projectData);
        }

        return redirect()->route('admin.users')->with('success', 'User updated successfully!');
    }

    /**
     * Delete a user
     */
    public function destroyUser(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete yourself!');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully!');
    }

    /**
     * Display list of projects (from external API and database)
     */
    public function projects()
    {
        // Fetch projects from external API
        $apiProjects = $this->jwtController->fetchData4();
        
        // Fetch projects from database
        $dbProjects = MasterProject::all()->keyBy('project_id');
        
        // Merge the data
        $projects = [];
        foreach ($apiProjects as $apiProject) {
            $projectId = $apiProject['id'] ?? null;
            if ($projectId) {
                $projects[] = [
                    'id' => $projectId,
                    'name' => $apiProject['name'] ?? 'Unknown',
                    'description' => $apiProject['description'] ?? '',
                    'in_db' => $dbProjects->has($projectId),
                    'db_project' => $dbProjects->get($projectId),
                ];
            }
        }
        
        return view('admin.projects.index', compact('projects'));
    }

    /**
     * Sync a project from API to database
     */
    public function syncProject(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Generate a unique code
        $code = 'PROJ-' . $validated['project_id'];

        MasterProject::updateOrCreate(
            ['project_id' => $validated['project_id']],
            [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'code' => $code,
                'is_active' => true,
            ]
        );

        return redirect()->route('admin.projects')->with('success', 'Project synced successfully!');
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string|unique:master_projects,code,' . $project->id,
            'is_active' => 'boolean',
        ]);

        $project->update($validated);

        return redirect()->route('admin.projects')->with('success', 'Project updated successfully!');
    }

    /**
     * Delete a project from database
     */
    public function destroyProject(MasterProject $project)
    {
        $project->delete();

        return redirect()->route('admin.projects')->with('success', 'Project removed from database successfully!');
    }
}
