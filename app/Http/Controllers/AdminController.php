<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MasterProject;
use App\Models\Project;
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
        
        return view('admin.index', compact('usersCount', 'projectsCount'));
    }

    public function users()
    {
        $users = User::with('projects')->paginate(20);
        
        return view('admin.users.index', compact('users'));
    }

    //create user view (get)
    public function createUser(){
        $roles = UserRole::cases();
        
        return view('admin.users.create', compact('roles'));
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

        User::create($validated);

        return redirect()->route('admin.users')->with('success', 'User created successfully!');
    }

    //edit user view (get)
    public function editUser(User $user)
    {
        $roles = UserRole::cases();
        $projects = MasterProject::all();
        
        return view('admin.users.edit', compact('user', 'roles', 'projects'));
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

        // Filter & sync valid project assignments
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
                        'role' => $data['role'] ?? ProjectRole::VIEWER->value,
                    ];
                }
            }

            $user->projects()->sync($projectData);
        }

        return redirect()->route('admin.users')->with('success', 'User updated successfully!');
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
        return view('admin.users.permissions', compact('user', 'projects', 'menus', 'actions', 'userAccesses', 'groups'));
    }

    public function updateUserPermissions(Request $request, $userId)
    {
        // Validate incoming payload from resources/views/admin/users/permissions.blade.php
        $validated = $request->validate([
            'group_id' => 'required|integer|exists:groups,group_id',
            'projects' => 'sometimes|array',
            'projects.*.assigned' => 'nullable|boolean',
            'permissions' => 'sometimes|array',
        ]);

        $groupId = (int) $validated['group_id'];
        $projectsInput = $validated['projects'] ?? [];
        $permissionsInput = $validated['permissions'] ?? [];

        // Collect selected project IDs (checkbox checked => "1")
        $selectedProjectIds = collect($projectsInput)
            ->filter(fn ($v) => (int)($v['assigned'] ?? 0) === 1)
            ->keys()
            ->map(fn ($k) => (int) $k)
            ->values();

        // Map action_name => action_id for CRUD actions
        $actionMap = DB::table('actions')->pluck('action_id', 'action'); // e.g. create/read/update/delete

        // Build permissions to sync for the selected group
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
            // 1) Replace user project access for this user
            DB::table('user_group_access')->where('user_id', $userId)->delete();

            if ($selectedProjectIds->isNotEmpty()) {
                $now = now();
                $rows = $selectedProjectIds->map(fn ($pid) => [
                    'user_id'    => $userId,
                    'group_id'   => $groupId,
                    'project_id' => $pid, // references master_project.project_id
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('user_group_access')->insert($rows);
            }

            // 2) Sync group menu/action permissions for the chosen group
            DB::table('access_groups')->where('group_id', $groupId)->delete();

            if (!empty($compiledPermissions)) {
                $now = now();
                $rows = array_map(function ($p) use ($groupId, $now) {
                    return [
                        'group_id'   => $groupId,
                        'menu_id'    => $p['menu_id'],
                        'action_id'  => $p['action_id'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $compiledPermissions);

                DB::table('access_groups')->insert($rows);
            }
        });

        return redirect()->route('admin.users.permissions', $userId)
            ->with('success', 'User permissions updated successfully.');
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
