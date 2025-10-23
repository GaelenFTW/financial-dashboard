<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MasterProject;
use App\Models\Project; // ✅ This line fixes your current error
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

    // public function updateUserPermissions(Request $request, $userId)
    // {
    public function updateUserPermissions(Request $request, $userId)
{
    $permissions = $request->input('permissions', []);
    $groupId = $request->input('group_id');
    $projectIds = $request->input('project_id', []); // assume from checkboxes

    if (!$groupId) {
        return back()->with('error', 'Please select a group before updating permissions.');
    }

    $user = User::findOrFail($userId);

    // Delete old access for this group
    DB::table('access_groups')->where('group_id', $groupId)->delete();

    // Insert new permissions
    foreach ($permissions as $menuId => $actions) {
        foreach (['create', 'read', 'update', 'delete'] as $actionName) {
            if (!empty($actions[$actionName]) && $actions[$actionName] == '1') {
                $action = DB::table('actions')
                    ->where('menu_id', $menuId)
                    ->where('action', $actionName)
                    ->first();

                if ($action) {
                    DB::table('access_groups')->insert([
                        'group_id'   => $groupId,
                        'menu_id'    => $menuId,
                        'action_id'  => $action->action_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    // ✅ Make sure all projects for this user have correct group mapping
    $existingProjects = DB::table('user_group_access')
        ->where('user_id', $userId)
        ->pluck('project_id')
        ->toArray();

    foreach ($projectIds as $projectId) {
        DB::table('user_group_access')->updateOrInsert(
            [
                'user_id'    => $userId,
                'project_id' => $projectId,
            ],
            [
                'group_id'   => $groupId,  // ensure correct group id is set
                'updated_at' => now(),
            ]
        );
    }

    return back()->with('success', 'Permissions updated successfully!');
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
