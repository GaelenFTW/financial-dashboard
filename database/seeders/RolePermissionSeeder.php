<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Optional: Clear existing data
        // DB::table('role_permission')->truncate();

        // Define permissions for each role
        $rolesWithPermissions = [
            'super_admin' => [
                'manage_users',
                'manage_projects',
                'manage_roles',
                'view_reports',
                'edit_reports',
                'assign_permissions',
                'access_financials',
                'system_settings',
            ],
            'admin' => [
                'manage_users',
                'manage_projects',
                'view_reports',
                'edit_reports',
                'assign_permissions',
            ],
            'manager' => [
                'manage_projects',
                'assign_tasks',
                'view_reports',
                'approve_submissions',
            ],
            'developer' => [
                'view_projects',
                'edit_code',
                'commit_changes',
                'view_tasks',
                'submit_progress',
            ],
            'qa' => [
                'view_projects',
                'test_features',
                'report_bugs',
            ],
            'viewer' => [
                'view_projects',
                'view_reports',
            ],
        ];

        // Get permission IDs from the permissions table
        $permissionMap = DB::table('permissions')->pluck('id', 'name')->toArray();

        $insertData = [];

        foreach ($rolesWithPermissions as $role => $permissionNames) {
            foreach ($permissionNames as $permName) {
                if (isset($permissionMap[$permName])) {
                    $insertData[] = [
                        'role' => $role,
                        'permission_id' => $permissionMap[$permName],
                    ];
                }
            }
        }

        DB::table('role_permission')->insert($insertData);

        $this->command->info('✅ Role-permission relationships seeded successfully!');
    }
}
