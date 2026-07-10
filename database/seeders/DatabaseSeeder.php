<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            'password' => Hash::make('password'),
        ]);
        $admin->roles()->attach([$adminRole->id, $userRole->id]);

        // Create a manager user
        $manager = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
        ]);
        $manager->roles()->attach([$managerRole->id, $userRole->id]);

        // Create a regular test user
        $testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $testUser->roles()->attach([$userRole->id]);

        // Create regular staff users for org chart depth
        $staff1 = User::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
        ]);
        $staff1->roles()->attach([$userRole->id]);

        $staff2 = User::create([
            'name' => 'Bob Williams',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
        ]);
        $staff2->roles()->attach([$userRole->id]);

        $staff3 = User::create([
            'name' => 'Carol Davis',
            'email' => 'carol@example.com',
            'password' => Hash::make('password'),
        ]);
        $staff3->roles()->attach([$userRole->id]);

        // Seed default company
        $acmeCorp = Company::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'is_active' => true,
        ]);

        // Subsidiary company
        $acmeSub = Company::create([
            'name' => 'Acme Technologies',
            'slug' => 'acme-tech',
            'parent_id' => $acmeCorp->id,
            'is_active' => true,
        ]);

        // Assign users to Acme Corporation with org chart hierarchy
        // John Doe = CEO (root, no manager)
        $admin->companies()->attach($acmeCorp->id, [
            'role' => 'admin',
            'job_title' => 'Chief Executive Officer',
            'manager_id' => null,
            'status' => 'active',
        ]);

        // Jane Smith = VP of Engineering (reports to John)
        $manager->companies()->attach($acmeCorp->id, [
            'role' => 'manager',
            'job_title' => 'VP of Engineering',
            'manager_id' => $admin->id,
            'status' => 'active',
        ]);

        // Alice = Senior Developer (reports to Jane)
        $staff1->companies()->attach($acmeCorp->id, [
            'role' => 'employee',
            'job_title' => 'Senior Developer',
            'manager_id' => $manager->id,
            'status' => 'active',
        ]);

        // Bob = Developer (reports to Jane)
        $staff2->companies()->attach($acmeCorp->id, [
            'role' => 'employee',
            'job_title' => 'Developer',
            'manager_id' => $manager->id,
            'status' => 'active',
        ]);

        // Carol = QA Lead (reports to Jane — on leave status for variety)
        $staff3->companies()->attach($acmeCorp->id, [
            'role' => 'employee',
            'job_title' => 'QA Lead',
            'manager_id' => $manager->id,
            'status' => 'on_leave',
        ]);

        // Test User = regular employee (reports to John directly)
        $testUser->companies()->attach($acmeCorp->id, [
            'role' => 'employee',
            'job_title' => 'Administrative Assistant',
            'manager_id' => $admin->id,
            'status' => 'active',
        ]);

        // Also assign the admin to the subsidiary
        $admin->companies()->attach($acmeSub->id, [
            'role' => 'admin',
            'job_title' => 'Managing Director',
            'manager_id' => null,
            'status' => 'active',
        ]);
        $manager->companies()->attach($acmeSub->id, [
            'role' => 'manager',
            'job_title' => 'Tech Lead',
            'manager_id' => $admin->id,
            'status' => 'active',
        ]);
    }
}
