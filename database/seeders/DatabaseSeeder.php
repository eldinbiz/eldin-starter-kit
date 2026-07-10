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
        $adminRole = Role::create([
            'name' => 'admin',
            'permissions' => ['manage-users', 'manage-roles', 'manage-org-chart'],
        ]);
        $managerRole = Role::create([
            'name' => 'manager',
            'permissions' => ['manage-users', 'manage-org-chart'],
        ]);
        $userRole = Role::create([
            'name' => 'user',
            'permissions' => [],
        ]);

        // Create Default Admin User
        $admin = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create a manager user
        $manager = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create a regular test user
        $testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create regular staff users for org chart depth
        $staff1 = User::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
        ]);

        $staff2 = User::create([
            'name' => 'Bob Williams',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
        ]);

        $staff3 = User::create([
            'name' => 'Carol Davis',
            'email' => 'carol@example.com',
            'password' => Hash::make('password'),
        ]);

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
        // John Doe = CEO (root, no manager), has admin + user roles in this company
        $admin->companies()->attach($acmeCorp->id, [
            'job_title' => 'Chief Executive Officer',
            'manager_id' => null,
            'status' => 'active',
        ]);
        $admin->companyRoles()->attach([$adminRole->id, $userRole->id], ['company_id' => $acmeCorp->id]);

        // Jane Smith = VP of Engineering (reports to John), has manager + user roles
        $manager->companies()->attach($acmeCorp->id, [
            'job_title' => 'VP of Engineering',
            'manager_id' => $admin->id,
            'status' => 'active',
        ]);
        $manager->companyRoles()->attach([$managerRole->id, $userRole->id], ['company_id' => $acmeCorp->id]);

        // Alice = Senior Developer (reports to Jane), user role only
        $staff1->companies()->attach($acmeCorp->id, [
            'job_title' => 'Senior Developer',
            'manager_id' => $manager->id,
            'status' => 'active',
        ]);
        $staff1->companyRoles()->attach($userRole->id, ['company_id' => $acmeCorp->id]);

        // Bob = Developer (reports to Jane), user role only
        $staff2->companies()->attach($acmeCorp->id, [
            'job_title' => 'Developer',
            'manager_id' => $manager->id,
            'status' => 'active',
        ]);
        $staff2->companyRoles()->attach($userRole->id, ['company_id' => $acmeCorp->id]);

        // Carol = QA Lead (reports to Jane — on leave status for variety), user role only
        $staff3->companies()->attach($acmeCorp->id, [
            'job_title' => 'QA Lead',
            'manager_id' => $manager->id,
            'status' => 'on_leave',
        ]);
        $staff3->companyRoles()->attach($userRole->id, ['company_id' => $acmeCorp->id]);

        // Test User = regular employee (reports to John directly), user role only
        $testUser->companies()->attach($acmeCorp->id, [
            'job_title' => 'Administrative Assistant',
            'manager_id' => $admin->id,
            'status' => 'active',
        ]);
        $testUser->companyRoles()->attach($userRole->id, ['company_id' => $acmeCorp->id]);

        // Assign admin to subsidiary with admin role only (demonstrating different roles per company)
        $admin->companies()->attach($acmeSub->id, [
            'job_title' => 'Managing Director',
            'manager_id' => null,
            'status' => 'active',
        ]);
        $admin->companyRoles()->attach($adminRole->id, ['company_id' => $acmeSub->id]);

        // Manager in subsidiary has only manager role (different roles per company)
        $manager->companies()->attach($acmeSub->id, [
            'job_title' => 'Tech Lead',
            'manager_id' => $admin->id,
            'status' => 'active',
        ]);
        $manager->companyRoles()->attach($managerRole->id, ['company_id' => $acmeSub->id]);
    }
}
