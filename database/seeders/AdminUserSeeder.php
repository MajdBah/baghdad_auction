<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the admin role
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            $this->command->info('Admin role not found. Please run the RoleSeeder first.');
            return;
        }

        // Check if an admin user already exists
        $adminExists = User::where('email', 'admin@example.com')->exists();

        if ($adminExists) {
            $this->command->info('Admin user already exists.');
            return;
        }

        // Create the admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
        ]);

        $this->command->info('Admin user created successfully.');
    }
}
