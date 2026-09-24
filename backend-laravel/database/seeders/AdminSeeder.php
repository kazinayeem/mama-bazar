<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AdminRole;
use App\Models\AdminPermission;
use App\Models\RolePermission;
use App\Services\RbacService;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Permissions
        foreach (RbacService::ALL_PERMISSIONS as $perm) {
            AdminPermission::updateOrCreate(
                ['code' => $perm['code']],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'description' => $perm['description'],
                ]
            );
        }

        // 2. Seed Roles & Role Permissions
        foreach (RbacService::getRolePresets() as $roleName => $preset) {
            AdminRole::updateOrCreate(
                ['name' => $roleName],
                [
                    'display_name' => $preset['displayName'],
                    'description' => $preset['description'],
                    'is_system' => true,
                ]
            );

            foreach ($preset['permissions'] as $permCode) {
                RolePermission::firstOrCreate([
                    'role_name' => $roleName,
                    'permission_code' => $permCode,
                ]);
            }
        }

        // 3. Seed Default Admin Accounts with DevAdmin@12345
        $password = Hash::make('DevAdmin@12345');

        User::updateOrCreate(
            ['phone' => '01711111111'],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'role' => 'admin',
                'custom_role' => 'SUPER_ADMIN',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['phone' => '01943124215'],
            [
                'name' => 'Dev Admin',
                'password' => $password,
                'role' => 'admin',
                'custom_role' => 'SUPER_ADMIN',
                'status' => 'active',
            ]
        );
    }
}
