<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure this list matches what you want
        $permissions = [
            ['name' => 'view_dashboard',  'description' => 'Access dashboard'],
            ['name' => 'view_projects',   'description' => 'View project list'],
            ['name' => 'edit_projects',   'description' => 'Edit project details'],
            ['name' => 'manage_users',    'description' => 'Manage users'],
            ['name' => 'manage_roles',    'description' => 'Manage roles and permissions'],
            ['name' => 'export_data',     'description' => 'Export reports'],
            ['name' => 'upload_data',     'description' => 'Upload new data'],
        ];

        // Insert only those that don't exist (idempotent)
        foreach ($permissions as $p) {
            $exists = DB::table('permissions')->where('name', $p['name'])->exists();
            if (! $exists) {
                DB::table('permissions')->insert([
                    'name' => $p['name'],
                    'description' => $p['description'],
                ]);
            }
        }
    }
}
