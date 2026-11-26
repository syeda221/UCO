<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed only the primary system administrator credentials (no fake/demo operational data).
     */
    public function run(): void
    {
        $email = config('uco.admin_seed_email', 'admin@ucotrace.gh');
        $password = config('uco.admin_seed_password', 'Admin@UCO2026!');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('uco.admin_seed_name', 'UCO System Admin'),
                'phone' => config('uco.admin_seed_phone', '+233200000001'),
                'password' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );
    }
}
