# RBAC System Architecture

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    RBAC System Overview                      │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────┐
│    System-Level Roles   │
│  (UserRole Enum)        │
├─────────────────────────┤
│  - super_admin          │  ← Bypasses ALL checks
│  - admin                │  ← System administration
│  - user                 │  ← Standard users
└─────────────────────────┘
            │
            ▼
┌─────────────────────────┐
│         Users           │
│  (users table)          │
├─────────────────────────┤
│  - id                   │
│  - name                 │
│  - email                │
│  - role ←────────────── System-level role
│  - permissions          │  (legacy field)
│  - employee_id          │
│  - position             │
└─────────────────────────┘
            │
            │ Many-to-Many
            ▼
┌─────────────────────────┐       ┌─────────────────────────┐
│    Project User         │       │   Project-Level Roles   │
│  (project_user table)   │       │  (ProjectRole Enum)     │
├─────────────────────────┤       ├─────────────────────────┤
│  - user_id              │       │  - admin                │
│  - project_id           │       │  - editor               │
│  - role ←───────────────┼───────│  - viewer               │
└─────────────────────────┘       └─────────────────────────┘
            │
            │
            ▼
┌─────────────────────────┐
│    Master Projects      │
│ (master_projects table) │
├─────────────────────────┤
│  - id                   │
│  - name                 │
│  - code                 │
│  - description          │
│  - is_active            │
└─────────────────────────┘
```

## Permission Flow

```
Request
   │
   ▼
┌──────────────────────┐
│  Auth Middleware     │
│  (Check login)       │
└──────────────────────┘
   │
   ▼
┌──────────────────────────────────────────┐
│  CheckUserPermission Middleware          │
│  (System-level permissions)              │
│                                          │
│  1. Is user Super Admin? → ✓ Allow      │
│  2. Check specific permission (upload,   │
│     view, export)                        │
└──────────────────────────────────────────┘
   │
   ▼
┌──────────────────────────────────────────┐
│  CheckProjectAccess Middleware           │
│  (Project-level permissions)             │
│                                          │
│  1. Is user Super Admin? → ✓ Allow      │
│  2. Does user have project access?       │
│  3. Check project role (admin, editor,   │
│     viewer)                              │
└──────────────────────────────────────────┘
   │
   ▼
Controller Action
```

## Role Hierarchy

```
Super Admin
    │
    ├─→ Has access to ALL projects
    ├─→ Bypasses ALL permission checks
    └─→ Cannot be restricted
    
Admin
    │
    ├─→ System administration rights
    ├─→ Must be assigned to projects
    └─→ Follows project-level permissions
    
User
    │
    ├─→ Standard user
    ├─→ Must be assigned to projects
    └─→ Follows project-level permissions
```

## Project Role Permissions

```
Project Admin
    │
    ├─→ Full project control
    ├─→ Can manage project settings
    ├─→ Can edit all project data
    └─→ Can manage project users
    
Project Editor
    │
    ├─→ Can edit project data
    ├─→ Cannot manage project settings
    └─→ Cannot manage users
    
Project Viewer
    │
    ├─→ Read-only access
    ├─→ Cannot edit anything
    └─→ Can only view data
```

## Example Scenarios

### Scenario 1: Super Admin Access
```
User: Super Admin
Project: Any Project
Result: ✓ Full Access (bypasses all checks)
```

### Scenario 2: Regular User with Project Access
```
User: John (role: user)
Project: Financial Dashboard
Assignment: Editor role in project
Actions:
  - View data: ✓ Allowed
  - Edit data: ✓ Allowed
  - Manage project: ✗ Denied (needs admin role)
```

### Scenario 3: Regular User without Project Access
```
User: Jane (role: user)
Project: Financial Dashboard
Assignment: No assignment
Actions:
  - View data: ✗ Denied
  - Edit data: ✗ Denied
  - Manage project: ✗ Denied
```

### Scenario 4: Admin User with Project Access
```
User: Bob (role: admin)
Project: Financial Dashboard
Assignment: Admin role in project
Actions:
  - View data: ✓ Allowed
  - Edit data: ✓ Allowed
  - Manage project: ✓ Allowed
```

## Code Examples

### Check User Permissions
```php
// Check system-level role
if ($user->isSuperAdmin()) {
    // Super admin - full access
}

if ($user->isAdmin()) {
    // Admin user - system administration
}

// Check project access
if ($user->hasProjectAccess($projectId)) {
    // User has access to this project
}

// Check project role
$role = $user->getProjectRole($projectId);
if ($role === ProjectRole::ADMIN) {
    // User is project admin
}

// Check edit permission
if ($user->canEditProject($projectId)) {
    // User can edit in this project
}
```

### Assign Users to Projects
```php
use App\Models\MasterProject;
use App\Enums\ProjectRole;

$project = MasterProject::find(1);

// Assign single user
$project->users()->attach($userId, [
    'role' => ProjectRole::EDITOR->value
]);

// Assign multiple users
$project->users()->attach([
    1 => ['role' => ProjectRole::ADMIN->value],
    2 => ['role' => ProjectRole::EDITOR->value],
    3 => ['role' => ProjectRole::VIEWER->value],
]);

// Update user role
$project->users()->updateExistingPivot($userId, [
    'role' => ProjectRole::ADMIN->value
]);

// Remove user from project
$project->users()->detach($userId);
```

### Use in Routes
```php
// System-level permission check
Route::middleware(['auth', 'user.permission:upload'])->group(function () {
    Route::post('/data/upload', [DataController::class, 'upload']);
});

// Project-level permission check
Route::middleware(['auth', 'project.access:view'])->group(function () {
    Route::get('/project/{project_id}/data', [ProjectController::class, 'show']);
});

Route::middleware(['auth', 'project.access:edit'])->group(function () {
    Route::put('/project/{project_id}/data', [ProjectController::class, 'update']);
});

Route::middleware(['auth', 'project.access:admin'])->group(function () {
    Route::post('/project/{project_id}/users', [ProjectController::class, 'addUser']);
});
```

## Database Queries

### Get All Projects for a User
```php
$projects = $user->projects;
// or with role
$projects = $user->projects()->withPivot('role')->get();
```

### Get All Users in a Project
```php
$project = MasterProject::find(1);
$users = $project->users;
// or with role
$users = $project->users()->withPivot('role')->get();
```

### Get Users by Role in Project
```php
$admins = $project->users()
    ->wherePivot('role', ProjectRole::ADMIN->value)
    ->get();
```

### Count User's Projects
```php
$projectCount = $user->projects()->count();
```

## Testing

### Test User Role Assignment
```php
$user = User::factory()->create([
    'role' => UserRole::ADMIN->value
]);
$this->assertTrue($user->isAdmin());
```

### Test Project Access
```php
$user = User::factory()->create();
$project = MasterProject::create([...]);
$user->projects()->attach($project->id, [
    'role' => ProjectRole::VIEWER->value
]);
$this->assertTrue($user->hasProjectAccess($project->id));
```

### Test Permission Levels
```php
$this->assertTrue($user->canEditProject($projectId));
$this->assertFalse($user->isProjectAdmin($projectId));
```
