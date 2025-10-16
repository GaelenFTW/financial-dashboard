<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MasterProject;
use App\Enums\UserRole;
use App\Enums\ProjectRole;
use Illuminate\Support\Facades\Hash;

class RBACSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::SUPER_ADMIN->value,
            'employee_id' => 1000,
            'position' => 'System Administrator',
        ]);

        // Create an admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN->value,
            'employee_id' => 1001,
            'position' => 'Administrator',
        ]);

        // Create regular users
        $editor = User::create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::USER->value,
            'employee_id' => 2001,
            'position' => 'Editor',
        ]);

        $viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::USER->value,
            'employee_id' => 2002,
            'position' => 'Viewer',
        ]);

        // Create sample projects
        $project1 = MasterProject::create([
            'name' => 'Financial Dashboard',
            'code' => 'FIN-DASH-001',
            'description' => 'Main financial dashboard project',
            'is_active' => true,
        ]);

        $project2 = MasterProject::create([
            'name' => 'Reporting System',
            'code' => 'REP-SYS-001',
            'description' => 'Internal reporting system',
            'is_active' => true,
        ]);

        // Assign users to projects with different roles
        $project1->users()->attach($admin->id, ['role' => ProjectRole::ADMIN->value]);
        $project1->users()->attach($editor->id, ['role' => ProjectRole::EDITOR->value]);
        $project1->users()->attach($viewer->id, ['role' => ProjectRole::VIEWER->value]);

        $project2->users()->attach($admin->id, ['role' => ProjectRole::ADMIN->value]);
        $project2->users()->attach($editor->id, ['role' => ProjectRole::VIEWER->value]);

        $this->command->info('RBAC seed data created successfully!');
        $this->command->info('Users created:');
        $this->command->info('- superadmin@example.com (password: password) - Super Admin');
        $this->command->info('- admin@example.com (password: password) - Admin');
        $this->command->info('- editor@example.com (password: password) - User with Editor role in projects');
        $this->command->info('- viewer@example.com (password: password) - User with Viewer role in projects');
    }
}
