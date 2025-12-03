<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@bylin.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'phone' => '+33612345678',
        ]);
        $admin->assignRole('super_admin');

        // Create Product Manager
        $manager = User::create([
            'name' => 'Product Manager',
            'email' => 'manager@bylin.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'phone' => '+33687654321',
        ]);
        $manager->assignRole('manager');
    }
}
