<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\UserRole;
use App\Models\MasterProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RBACTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can be assigned a role
     */
    public function test_user_can_have_role(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $this->assertEquals(UserRole::ADMIN, $user->role);
        $this->assertTrue($user->isAdmin());
    }

    /**
     * Test super admin identification
     */
    public function test_super_admin_is_identified(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->isAdmin());
    }

    /**
     * Test project creation and user assignment
     */
    public function test_user_can_be_assigned_to_project(): void
    {
        $user = User::factory()->create();
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST001',
            'description' => 'A test project',
        ]);

        $user->projects()->attach($project->id, ['role' => ProjectRole::VIEWER->value]);

        $this->assertTrue($user->hasProjectAccess($project->id));
    }

    /**
     * Test user project role retrieval
     */
    public function test_user_can_get_project_role(): void
    {
        $user = User::factory()->create();
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST002',
            'description' => 'A test project',
        ]);

        $user->projects()->attach($project->id, ['role' => ProjectRole::EDITOR->value]);

        $role = $user->getProjectRole($project->id);
        $this->assertEquals(ProjectRole::EDITOR, $role);
    }

    /**
     * Test user without project access
     */
    public function test_user_without_project_access_is_denied(): void
    {
        $user = User::factory()->create();
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST003',
            'description' => 'A test project',
        ]);

        $this->assertFalse($user->hasProjectAccess($project->id));
    }

    /**
     * Test super admin has access to all projects
     */
    public function test_super_admin_has_access_to_all_projects(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
        ]);
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST004',
            'description' => 'A test project',
        ]);

        $this->assertTrue($superAdmin->hasProjectAccess($project->id));
        $this->assertTrue($superAdmin->canEditProject($project->id));
        $this->assertTrue($superAdmin->isProjectAdmin($project->id));
    }

    /**
     * Test editor can edit project
     */
    public function test_editor_can_edit_project(): void
    {
        $user = User::factory()->create();
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST005',
            'description' => 'A test project',
        ]);

        $user->projects()->attach($project->id, ['role' => ProjectRole::EDITOR->value]);

        $this->assertTrue($user->canEditProject($project->id));
        $this->assertFalse($user->isProjectAdmin($project->id));
    }

    /**
     * Test viewer cannot edit project
     */
    public function test_viewer_cannot_edit_project(): void
    {
        $user = User::factory()->create();
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST006',
            'description' => 'A test project',
        ]);

        $user->projects()->attach($project->id, ['role' => ProjectRole::VIEWER->value]);

        $this->assertFalse($user->canEditProject($project->id));
        $this->assertFalse($user->isProjectAdmin($project->id));
    }

    /**
     * Test project admin has admin privileges
     */
    public function test_project_admin_has_admin_privileges(): void
    {
        $user = User::factory()->create();
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST007',
            'description' => 'A test project',
        ]);

        $user->projects()->attach($project->id, ['role' => ProjectRole::ADMIN->value]);

        $this->assertTrue($user->isProjectAdmin($project->id));
        $this->assertTrue($user->canEditProject($project->id));
    }

    /**
     * Test project relationships
     */
    public function test_project_has_users_relationship(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST008',
            'description' => 'A test project',
        ]);

        $project->users()->attach($user1->id, ['role' => ProjectRole::ADMIN->value]);
        $project->users()->attach($user2->id, ['role' => ProjectRole::VIEWER->value]);

        $this->assertCount(2, $project->users);
    }
}
