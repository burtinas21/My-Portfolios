<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the admin user. Safe to run multiple times.
     * Reads credentials from .env file.
     */
    public function run(): void
    {
        $name = env('ADMIN_NAME', 'Admin');
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'password');

        User::updateOrCreate(
            ['email' => $email], // Match by email
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        $this->command->info("Admin user created/updated: {$email}");
    }
}
