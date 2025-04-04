<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'System administrator with full access to all features'
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Manager with access to most features except system settings'
            ],
            [
                'name' => 'accountant',
                'display_name' => 'Accountant',
                'description' => 'Accountant with access to financial features'
            ],
            [
                'name' => 'staff',
                'display_name' => 'Staff',
                'description' => 'General staff with limited access'
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
