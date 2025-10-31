<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id; // or however you get the authenticated user

        // Check if user has permission to read projects
        if (!$this->permissionService->checkProjectPermission($userId, 'read')) {
            return response()->json([
                'message' => 'Unauthorized. Only admin group can view projects.'
            ], 403);
        }

        // Get projects user has access to
        $projects = DB::table('projects')
            ->whereIn('project_id', function($query) use ($userId) {
                $query->select('project_id')
                    ->from('user_group_access')
                    ->where('user_id', $userId)
                    ->where('group_id', 1);
            })
            ->orWhereExists(function($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('user_group_access')
                    ->where('user_id', $userId)
                    ->where('group_id', 1)
                    ->where('project_id', 999999); // Global access
            })
            ->get();

        return response()->json($projects);
    }

    /**
     * Store a newly created project
     */
    public function store(Request $request)
    {
        $userId = $request->user()->id;

        // Check if user has permission to create projects
        if (!$this->permissionService->checkProjectPermission($userId, 'create')) {
            return response()->json([
                'message' => 'Unauthorized. Only admin group can create projects.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Add other project fields
        ]);

        $projectId = DB::table('projects')->insertGetId(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now()
        ]));

        return response()->json([
            'message' => 'Project created successfully',
            'project_id' => $projectId
        ], 201);
    }

    /**
     * Display the specified project
     */
    public function show(Request $request, $id)
    {
        $userId = $request->user()->id;

        // Check if user has permission to read this specific project
        if (!$this->permissionService->checkProjectPermission($userId, 'read', $id)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have access to this project.'
            ], 403);
        }

        $project = DB::table('projects')->where('project_id', $id)->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        return response()->json($project);
    }

    public function update(Request $request, $id)
    {
        $userId = $request->user()->id;

        // Check if user has permission to update this specific project
        if (!$this->permissionService->checkProjectPermission($userId, 'update', $id)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have access to update this project.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            // Add other project fields
        ]);

        $updated = DB::table('projects')
            ->where('project_id', $id)
            ->update(array_merge($validated, [
                'updated_at' => now()
            ]));

        if (!$updated) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        return response()->json(['message' => 'Project updated successfully']);
    }

    /**
     * Remove the specified project
     */
    public function destroy(Request $request, $id)
    {
        $userId = $request->user()->id;

        // Check if user has permission to delete this specific project
        if (!$this->permissionService->checkProjectPermission($userId, 'delete', $id)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have access to delete this project.'
            ], 403);
        }

        $deleted = DB::table('projects')->where('project_id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        return response()->json(['message' => 'Project deleted successfully']);
    }
}