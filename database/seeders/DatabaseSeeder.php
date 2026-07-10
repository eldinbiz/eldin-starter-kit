<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default roles
        $adminRole = Role::create(['name' => 'admin']);
        $managerRole = Role::create(['name' => 'manager']);
        $userRole = Role::create(['name' => 'user']);

        // Create Default Admin User
        $admin = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
        $admin->roles()->attach([$adminRole->id, $userRole->id]);

        // Create a regular test user
        $testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
        $testUser->roles()->attach([$userRole->id]);
    }
}

