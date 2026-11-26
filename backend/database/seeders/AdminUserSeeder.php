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
        $email = env('ADMIN_SEED_EMAIL', 'admin@ucotrace.gh');
        $password = env('ADMIN_SEED_PASSWORD', 'Admin@UCO2026!');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_SEED_NAME', 'UCO System Admin'),
                'phone' => env('ADMIN_SEED_PHONE', '+233200000001'),
                'password' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );
    }
}
