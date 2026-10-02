<?php

namespace Database\Seeders;

use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\RbacService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure RBAC roles and permissions exist
        if (class_exists(RbacService::class)) {
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
        }

        // 2. Create or Update Super Admin User
        $email = 'mamabazar@gmail.com';
        $password = 'mamabazar@12345';
        $phone = '01711111111';

        // Check by email or phone to avoid unique key conflicts
        $superAdmin = User::where('email', $email)
            ->orWhere('phone', $phone)
            ->first();

        if (! $superAdmin) {
            $superAdmin = new User;
        }

        $superAdmin->name = 'Super Admin';
        $superAdmin->email = $email;
        $superAdmin->phone = $phone;
        $superAdmin->password = Hash::make($password);
        $superAdmin->role = 'admin';
        $superAdmin->custom_role = 'SUPER_ADMIN';
        $superAdmin->permissions_json = json_encode(['*']);
        $superAdmin->status = 'active';
        $superAdmin->save();

        // 3. Grant full wildcard permission in user_permissions table
        UserPermission::updateOrCreate(
            [
                'user_id' => $superAdmin->id,
                'permission_code' => '*',
            ],
            [
                'granted' => true,
            ]
        );

        $this->command->info("Super Admin successfully seeded: {$email}");
    }
}
