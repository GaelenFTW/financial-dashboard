<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        // Get statistics for admin dashboard
        $usersCount = DB::table('users')->count();
        $projectsCount = DB::table('master_project')->count();
        $activeProjectsCount = DB::table('master_project')->count();
        $groupsCount = DB::table('groups')->count();
        
        return view('admin.index', compact(
            'usersCount',
            'projectsCount',
            'activeProjectsCount',
            'groupsCount'
        ));
    }

    // ==================== PROJECT METHODS ====================
    
    /**
     * Display a listing of projects
     * Only accessible by users with group_id = 1
     */
    public function projects()
    {
        $this->checkProjectAccess();

        // Use master_project table instead of projects
        $projects = DB::table('master_project')
            ->orderBy('project_id', 'asc')
            ->paginate(25);

        return view('admin.projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new project
     */
    public function createProject()
    {
        $this->checkProjectAccess();

        return view('admin.projects.create');
    }

    /**
     * Store a newly created project
     */
    public function storeProject(Request $request)
    {
        $this->checkProjectAccess();

        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'project_code' => 'required|string|max:50|unique:projects,project_code',

        ]);

        DB::table('master_project')->insert([
            'project_name' => $validated['project_name'],
            'project_code' => $validated['project_code'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.projects')
            ->with('success', 'Project created successfully.');
    }

    /**
     * Show the form for editing the specified project
     */
    public function editProject($projectId)
    {
        $this->checkProjectAccess();

        $project = DB::table('master_project')
            ->where('project_id', $projectId)
            ->first();

        if (!$project) {
            abort(404, 'Project not found');
        }

        return view('admin.projects.edit', compact('project'));
    }

    /**
     * Update the specified project
     */
    public function updateProject(Request $request, $projectId)
    {
        $this->checkProjectAccess();

        $project = DB::table('master_project')
            ->where('project_id', $projectId)
            ->first();

        $validated = $request->validate([
            'project_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:master_project,code,' . $projectId . ',project_id',
            'sh' => 'required|integer',
        ]);

        DB::table('master_project')
            ->where('project_id', $projectId)
            ->update([
                'project_id' => $validated['project_id'],
                'name' => $validated['name'],
                'code' => $validated['code'],
                'sh' => $validated['sh'],
            ]);


        return redirect()->route('admin.projects')
            ->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project
     */
    public function destroyProject($projectId)
    {
        $this->checkProjectAccess();

        $project = DB::table('master_project')
            ->where('project_id', $projectId)
            ->first();

        if (!$project) {
            abort(404, 'Project not found');
        }

        // Check if project is being used in user_group_access
        $inUse = DB::table('user_group_access')
            ->where('project_id', $projectId)
            ->exists();

        if ($inUse) {
            return redirect()->route('admin.projects')
                ->with('error', 'Cannot delete project. It is assigned to users.');
        }

        DB::table('master_project')->where('project_id', $projectId)->delete();

        return redirect()->route('admin.projects')
            ->with('success', 'Project deleted successfully.');
    }

    // ==================== USER METHODS ====================
    
    public function users()
    {
        $users = DB::table('users')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        $groups = DB::table('groups')->get();
        $projects = DB::table('master_project')->get();

        return view('admin.users.create', compact('groups', 'projects'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin,super_admin',
            'group_id' => 'required|integer|exists:groups,group_id',
            'project_id' => 'required|integer|exists:projects,project_id',
        ]);

        // Create user
        $userId = DB::table('users')->insertGetId([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign user to group and project
        DB::table('user_group_access')->insert([
            'user_id' => $userId,
            'group_id' => $validated['group_id'],
            'project_id' => $validated['project_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.users')
            ->with('success', 'User created successfully.');
    }

    public function editUser($userId)
    {
        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            abort(404, 'User not found');
        }

        $groups = DB::table('groups')->get();
        $projects = DB::table('master_project')->get();
        $userAccess = DB::table('user_group_access')
            ->where('user_id', $userId)
            ->get();

        return view('admin.users.edit', compact('user', 'groups', 'projects', 'userAccess'));
    }

    public function updateUser(Request $request, $userId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId . ',id',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:user,admin,super_admin',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'updated_at' => now(),
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        DB::table('users')->where('id', $userId)->update($updateData);

        return redirect()->route('admin.users')
            ->with('success', 'User updated successfully.');
    }

    public function destroyUser($userId)
    {
        // Delete user's group access
        DB::table('user_group_access')->where('user_id', $userId)->delete();

        // Delete user
        DB::table('users')->where('id', $userId)->delete();

        return redirect()->route('admin.users')
            ->with('success', 'User deleted successfully.');
    }

    public function editUserPermissions($userId)
    {
        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            abort(404, 'User not found');
        }

        $groups = DB::table('groups')->get();
        $projects = DB::table('master_project')->get();
        $userAccess = DB::table('user_group_access')
            ->where('user_id', $userId)
            ->get();

        return view('admin.users.permissions', compact('user', 'groups', 'projects', 'userAccess'));
    }

    public function updateUserPermissions(Request $request, $userId)
    {
        $validated = $request->validate([
            'accesses' => 'required|array',
            'accesses.*.group_id' => 'required|integer|exists:groups,group_id',
            'accesses.*.project_id' => 'required|integer|exists:projects,project_id',
        ]);

        DB::transaction(function () use ($userId, $validated) {
            // Remove old access
            DB::table('user_group_access')->where('user_id', $userId)->delete();

            // Add new access
            foreach ($validated['accesses'] as $access) {
                DB::table('user_group_access')->insert([
                    'user_id' => $userId,
                    'group_id' => $access['group_id'],
                    'project_id' => $access['project_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()->route('admin.users.permissions', $userId)
            ->with('success', 'User permissions updated successfully.');
    }

    // ==================== HELPER METHODS ====================
    
    /**
     * Check if current user has project management access
     * Super admins have full access, regular admins need group_id = 1
     */
    private function checkProjectAccess()
    {
        $user = auth()->user();
        
        // Super admins have full access - no group check needed
        if ($user->isSuperAdmin()) {
            return;
        }
        
        // For regular admins, check if they have admin role
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can access project management.');
        }
        
        // Regular admins must have group_id = 1
        $hasGroupAccess = DB::table('user_group_access')
            ->where('user_id', $user->id)
            ->where('group_id', 1)
            ->exists();

        if (!$hasGroupAccess) {
            abort(403, 'Unauthorized. Only users in admin group (group_id = 1) can manage projects.');
        }
    }
}