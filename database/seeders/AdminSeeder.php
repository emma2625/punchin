<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a Superadmin
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'), // change to a secure password
            'role' => UserRole::SUPERADMIN,
        ]);

        // Create a sample Admin
        // User::create([
        //     'first_name' => 'Company',
        //     'last_name' => 'Admin',
        //     'email' => 'admin@example.com',
        //     'password' => Hash::make('password123'), // change to a secure password
        //     'role' => UserRole::ADMIN,
        //     'company_id' => 1, // assign to a company_id if you have a company seeded
        // ]);
    }
}
