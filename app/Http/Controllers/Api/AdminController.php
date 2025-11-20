<?php

namespace App\Http\Controllers\Api;


use App\http\Controllers\Controller;
use App\Models\User;
use App\Models\MasterProject;
use App\Models\Menu;
use App\Models\Action;
use Illuminate\Support\Facades\DB;

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
        
        return response()->json(['view' => 'admin.index', 'data' => compact('usersCount', 'projectsCount')]);
    }

    public function users(Request $request)
    {
        $search = $request->input('search');

        $query = User::with('projects');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('employee_id', 'LIKE', "%{$search}%")
                ->orWhere('position', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(20);

        return response()->json(['view' => 'admin.users.index', 'data' => compact('users')]);
    }

    //create user view (get)
    public function createUser(){
        $roles = UserRole::cases();
        
        return response()->json(['view' => 'admin.users.create', 'data' => compact('roles')]);
    }

    //store user (post)
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

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully!',
            'user' => $user
        ], 201);
    }

    //edit user view (get)
    public function editUser(User $user)
    {
        $roles = UserRole::cases();
        $projects = MasterProject::all();
        
        return response()->json(['view' => 'admin.users.edit', 'data' => compact('user', 'roles', 'projects')]);
    }

    //update user (post)
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => ['required', new Enum(UserRole::class)],
            'employee_id' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'active' => 'required|boolean',
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        // Sync Projects
        if ($request->has('projects')) {
            $validProjectIds = DB::table('master_project')->pluck('project_id')->toArray();

            $projectData = [];
            foreach ($request->projects as $projectId => $data) {
                if (
                    isset($data['assigned']) &&
                    $data['assigned'] &&
                    in_array((int)$projectId, $validProjectIds)
                ) {
                    $projectData[$projectId] = [
                        'role' => $data['role'] ?? ProjectRole::VIEWER->value,
                    ];
                }
            }

            $user->projects()->sync($projectData);
        }

        return response()->json([
            'message' => 'User updated successfully!',
            'user' => $user
        ]);
    }

    public function editUserPermissions($id)
    {
        $user = User::findOrFail($id);
        $projects = Project::all();
        $groups = \DB::table('groups')->get();
        $menus = Menu::where('active', 1)->get();
        $actions = Action::where('active', 1)->get();

        // All existing user access records (joined from access_group or user_group_access)
        $userAccesses = DB::table('access_groups')
            ->join('user_group_access', 'access_groups.group_id', '=', 'user_group_access.group_id')
            ->where('user_group_access.user_id', $user->id)
            ->select('access_groups.menu_id', 'access_groups.action_id')
            ->get();

        // dd($menus);
        return response()->json(['view' => 'admin.users.permissions', 'data' => compact('user', 'projects', 'menus', 'actions', 'userAccesses', 'groups')]);
    }

    public function updateUserPermissions(Request $request, $userId)
    {
        $validated = $request->validate([
            'group_id' => 'required|integer|exists:groups,group_id',
            'projects' => 'sometimes|array',
            'projects.*.assigned' => 'nullable|boolean',
            'permissions' => 'sometimes|array',
        ]);

        $groupId = (int) $validated['group_id'];
        $projectsInput = $validated['projects'] ?? [];
        $permissionsInput = $validated['permissions'] ?? [];

        $selectedProjectIds = collect($projectsInput)
            ->filter(fn ($v) => (int) ($v['assigned'] ?? 0) === 1)
            ->keys()
            ->map(fn ($k) => (int) $k)
            ->values();

        $actionMap = DB::table('actions')->pluck('action_id', 'action');

        $compiledPermissions = [];
        foreach ($permissionsInput as $menuId => $flags) {
            foreach (['create', 'read', 'update', 'delete'] as $actionName) {
                if (!empty($flags[$actionName]) && isset($actionMap[$actionName])) {
                    $compiledPermissions[] = [
                        'menu_id'   => (int) $menuId,
                        'action_id' => (int) $actionMap[$actionName],
                    ];
                }
            }
        }

        DB::transaction(function () use ($userId, $groupId, $selectedProjectIds, $compiledPermissions) {
            DB::table('user_group_access')->where('user_id', $userId)->delete();

            if ($selectedProjectIds->isNotEmpty()) {
                $now = now();
                $rows = $selectedProjectIds->map(fn ($pid) => [
                    'user_id'    => $userId,
                    'group_id'   => $groupId,
                    'project_id' => $pid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('user_group_access')->insert($rows->all());
            }

            DB::table('access_groups')->where('group_id', $groupId)->delete();

            if (!empty($compiledPermissions)) {
                $now = now();
                $rows = array_map(fn ($p) => [
                    'group_id'   => $groupId,
                    'menu_id'    => $p['menu_id'],
                    'action_id'  => $p['action_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $compiledPermissions);

                DB::table('access_groups')->insert($rows);
            }
        });

        return response()->json([
            'message' => 'User permissions updated successfully.'
        ]);
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
    public function projects(Request $request)
    {
        $search = $request->input('search');

        $query = MasterProject::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_id', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%")
                ->orWhere('name', 'LIKE', "%{$search}%")
                ->orWhere('sh', 'LIKE', "%{$search}%");
            });
        }

        $projects = $query->paginate(20);

        return response()->json(['view' => 'admin.projects.index', 'data' => compact('projects')]);
    }


    public function createProject()
    {
        return response()->json(['view' => 'admin.projects.create']);
    }

    public function storeProject(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|unique:master_project,project_id',
            'sh' => 'nullable|string|max:255',
            'code' => 'required|string|unique:master_project,code',
            'name' => 'required|string|max:255',
        ]);

        $project = MasterProject::create($validated);

        return response()->json([
            'message' => 'Project created successfully!',
            'project' => $project
        ], 201);
    }


    public function editProject(MasterProject $project)
    {
        return response()->json(['view' => 'admin.projects.edit', 'data' => compact('project')]);
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

        return response()->json([
            'message' => 'Project updated successfully!',
            'project' => $project
        ]);
    }

    public function destroyProject(MasterProject $project)
    {
        $project->delete();

        return redirect()->route('admin.projects')->with('success', 'Project removed successfully!');
    }

    
}
