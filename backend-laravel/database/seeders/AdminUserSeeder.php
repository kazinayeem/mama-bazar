<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('ADMIN_NAME', 'Administrator');
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $phone = env('ADMIN_PHONE', '01700000000');
        $password = env('ADMIN_PASSWORD', 'ChangeMe123!');

        // Update or create the primary administrator account
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make($password),
                'role' => 'admin',
                'custom_role' => 'SUPER_ADMIN',
                'status' => 'active',
            ]
        );
    }
}
