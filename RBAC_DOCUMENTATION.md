# Enterprise-Grade Role-Based Access Control (RBAC) System

This document describes the RBAC implementation for the Financial Dashboard application.

## Overview

The system implements a two-tier RBAC approach:
1. **System-Level Roles**: Define what users can do across the entire application
2. **Project-Level Roles**: Define what users can do within specific projects

## System-Level Roles

Defined in `App\Enums\UserRole`:

- **super_admin**: Full system access, bypasses all permission checks
- **admin**: Administrative privileges
- **user**: Regular user with project-specific permissions

## Project-Level Roles

Defined in `App\Enums\ProjectRole`:

- **admin**: Full control over the project and its settings
- **editor**: Can edit project data but cannot manage project settings
- **viewer**: Read-only access to project data

## Database Schema

### Users Table
```
- id
- name
- email
- password
- role (system-level role: super_admin, admin, user)
- permissions (legacy field for backward compatibility)
- employee_id
- position
- timestamps
```

### Master Projects Table
```
- id
- name
- code (unique identifier)
- description
- is_active
- timestamps
```

### Project User Pivot Table
```
- id
- user_id (foreign key to users)
- project_id (foreign key to master_projects)
- role (project-level role: admin, editor, viewer)
- timestamps
- unique constraint on (user_id, project_id)
```

## Models

### User Model (`App\Models\User`)

Key methods:
- `projects()`: Relationship to projects
- `hasProjectAccess(int $projectId)`: Check if user can access a project
- `getProjectRole(int $projectId)`: Get user's role in a specific project
- `canEditProject(int $projectId)`: Check if user can edit in a project
- `isProjectAdmin(int $projectId)`: Check if user is admin of a project
- `isSuperAdmin()`: Check if user is super admin
- `isAdmin()`: Check if user has admin privileges

### MasterProject Model (`App\Models\MasterProject`)

Key methods:
- `users()`: Relationship to users with pivot data

## Middleware

### CheckUserPermission (`App\Http\Middleware\CheckUserPermission`)

Checks system-level permissions for actions like upload, view, and export.

Usage in routes:
```php
Route::middleware(['auth', 'user.permission:upload'])->group(function () {
    // Routes that require upload permission
});
```

### CheckProjectAccess (`App\Http\Middleware\CheckProjectAccess`)

Checks if a user has access to a specific project and verifies project-level permissions.

Usage in routes:
```php
Route::middleware(['auth', 'project.access:view'])->group(function () {
    // Routes that require project view access
});

Route::middleware(['auth', 'project.access:edit'])->group(function () {
    // Routes that require project edit access
});

Route::middleware(['auth', 'project.access:admin'])->group(function () {
    // Routes that require project admin access
});
```

The middleware expects the project_id to be available either:
- As a route parameter: `{project_id}`
- As a request parameter: `?project_id=123`

## Usage Examples

### Assigning a User to a Project

```php
use App\Models\User;
use App\Models\MasterProject;
use App\Enums\ProjectRole;

$user = User::find(1);
$project = MasterProject::find(1);

// Attach user to project with editor role
$user->projects()->attach($project->id, [
    'role' => ProjectRole::EDITOR->value
]);
```

### Checking User Permissions

```php
// Check if user has access to a project
if ($user->hasProjectAccess($projectId)) {
    // User can access the project
}

// Check if user can edit in a project
if ($user->canEditProject($projectId)) {
    // User can edit in the project
}

// Check if user is project admin
if ($user->isProjectAdmin($projectId)) {
    // User is admin of the project
}

// Check if user is super admin
if ($user->isSuperAdmin()) {
    // User is super admin
}
```

### Creating Projects and Assigning Users

```php
use App\Models\MasterProject;
use App\Enums\ProjectRole;

// Create a new project
$project = MasterProject::create([
    'name' => 'New Project',
    'code' => 'PROJ-001',
    'description' => 'Project description',
    'is_active' => true,
]);

// Assign multiple users with different roles
$project->users()->attach([
    1 => ['role' => ProjectRole::ADMIN->value],
    2 => ['role' => ProjectRole::EDITOR->value],
    3 => ['role' => ProjectRole::VIEWER->value],
]);
```

## Migrations

Run migrations to set up the RBAC system:

```bash
php artisan migrate
```

This will create:
- `master_projects` table
- `project_user` pivot table
- `role` column in `users` table
- `permissions` column in `users` table (for backward compatibility)

## Seeding Test Data

To seed the database with sample users and projects:

```bash
php artisan db:seed --class=RBACSeeder
```

This creates:
- Super Admin user (superadmin@example.com)
- Admin user (admin@example.com)
- Editor user (editor@example.com)
- Viewer user (viewer@example.com)
- Sample projects with user assignments

All seeded users have password: `password`

## Testing

Run RBAC tests:

```bash
php artisan test --filter=RBACTest
php artisan test --filter=MiddlewareTest
```

## Backward Compatibility

The system maintains backward compatibility with the legacy permissions system:
- The `permissions` field still exists on the User model
- Legacy methods `canUpload()`, `canView()`, and `canExport()` still work
- Existing routes and controllers continue to function

## Security Features

1. **Super Admin Bypass**: Super admins automatically have access to all projects
2. **Unique Constraints**: Users can only have one role per project
3. **Cascade Deletes**: Project-user relationships are automatically cleaned up when users or projects are deleted
4. **Role Validation**: Uses PHP enums for type-safe role definitions
5. **Middleware Protection**: All project access is protected by middleware

## Best Practices

1. Always check permissions in controllers even when using middleware
2. Use the enum values when assigning roles (never hard-code strings)
3. Grant minimum required permissions (principle of least privilege)
4. Regularly audit user-project assignments
5. Deactivate projects instead of deleting them to maintain audit trails
