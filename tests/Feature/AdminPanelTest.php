<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\MasterProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_requires_authentication()
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_super_admin_can_access_admin_panel()
    {
        $user = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
        ]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_admin_panel()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_admin_panel()
    {
        $user = User::factory()->create([
            'role' => UserRole::USER,
        ]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_users_list()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('Manage Users');
    }

    public function test_admin_can_view_create_user_form()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users/create');
        $response->assertStatus(200);
        $response->assertSee('Create New User');
    }

    public function test_admin_can_create_user()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::USER->value,
            'employee_id' => '12345',
            'position' => 'Developer',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_admin_can_view_edit_user_form()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('Edit User');
    }

    public function test_admin_can_update_user()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => UserRole::USER->value,
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_delete_user()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/users/{$user->id}");

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_cannot_delete_themselves()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_can_view_projects_list()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)->get('/admin/projects');
        $response->assertStatus(200);
        $response->assertSee('Manage Projects');
    }

    public function test_admin_can_sync_project()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)->post('/admin/projects/sync', [
            'project_id' => 1,
            'name' => 'Test Project',
            'description' => 'Test Description',
        ]);

        $response->assertRedirect('/admin/projects');
        $this->assertDatabaseHas('master_projects', [
            'project_id' => 1,
            'name' => 'Test Project',
        ]);
    }

    public function test_admin_can_view_edit_project_form()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $project = MasterProject::create([
            'project_id' => 1,
            'name' => 'Test Project',
            'code' => 'PROJ-1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get("/admin/projects/{$project->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('Edit Project');
    }

    public function test_admin_can_update_project()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $project = MasterProject::create([
            'project_id' => 1,
            'name' => 'Test Project',
            'code' => 'PROJ-1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put("/admin/projects/{$project->id}", [
            'name' => 'Updated Project',
            'code' => 'PROJ-1',
            'description' => 'Updated Description',
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/projects');
        $this->assertDatabaseHas('master_projects', [
            'id' => $project->id,
            'name' => 'Updated Project',
        ]);
    }

    public function test_admin_can_delete_project()
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $project = MasterProject::create([
            'project_id' => 1,
            'name' => 'Test Project',
            'code' => 'PROJ-1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/projects/{$project->id}");

        $response->assertRedirect('/admin/projects');
        $this->assertDatabaseMissing('master_projects', [
            'id' => $project->id,
        ]);
    }
}
