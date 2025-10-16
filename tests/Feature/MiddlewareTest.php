<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\MasterProject;
use App\Enums\UserRole;
use App\Enums\ProjectRole;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test CheckUserPermission middleware allows super admin
     */
    public function test_super_admin_bypasses_permission_checks(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN->value,
        ]);

        $this->actingAs($superAdmin);

        // Super admin should be able to access all routes regardless of permissions
        $response = $this->get('/dashboard');
        $this->assertNotEquals(403, $response->status());
    }

    /**
     * Test CheckProjectAccess middleware denies access without project membership
     */
    public function test_project_middleware_denies_access_without_membership(): void
    {
        $user = User::factory()->create();
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST001',
            'description' => 'A test project',
        ]);

        $this->actingAs($user);

        $this->assertFalse($user->hasProjectAccess($project->id));
    }

    /**
     * Test CheckProjectAccess middleware allows access with project membership
     */
    public function test_project_middleware_allows_access_with_membership(): void
    {
        $user = User::factory()->create();
        $project = MasterProject::create([
            'name' => 'Test Project',
            'code' => 'TEST002',
            'description' => 'A test project',
        ]);

        $user->projects()->attach($project->id, ['role' => ProjectRole::VIEWER->value]);

        $this->actingAs($user);

        $this->assertTrue($user->hasProjectAccess($project->id));
    }
}
