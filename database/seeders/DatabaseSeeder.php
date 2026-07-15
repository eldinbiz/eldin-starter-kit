<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Truncate in FK-safe order
        DB::statement('PRAGMA foreign_keys = OFF;');
        DB::table('company_user_role')->truncate();
        DB::table('company_user')->truncate();
        DB::table('users')->truncate();
        DB::table('companies')->truncate();
        DB::table('roles')->truncate();
        DB::statement('PRAGMA foreign_keys = ON;');

        // ─────────────────────────────────────────────
        // 1. Gather always-enabled permissions from config
        // ─────────────────────────────────────────────
        $alwaysEnabledPermissions = collect(config('permissions.list'))
            ->filter(fn (array $perm): bool => (bool) ($perm['always_enabled'] ?? false))
            ->keys()
            ->all();

        // ─────────────────────────────────────────────
        // 2. Create Roles (explicit permissions merged with always-enabled)
        // ─────────────────────────────────────────────
        $adminRole = Role::create([
            'name' => 'admin',
            'permissions' => array_values(array_unique(array_merge(
                ['manage-users', 'manage-roles', 'manage-org-chart'],
                $alwaysEnabledPermissions,
            ))),
        ]);
        $managerRole = Role::create([
            'name' => 'manager',
            'permissions' => array_values(array_unique(array_merge(
                ['manage-users', 'manage-org-chart'],
                $alwaysEnabledPermissions,
            ))),
        ]);
        $userRole = Role::create([
            'name' => 'user',
            'permissions' => $alwaysEnabledPermissions,
        ]);

        // ─────────────────────────────────────────────
        // 2. Create Companies
        // ─────────────────────────────────────────────
        $mmsgi = Company::create([
            'name' => 'MMSGI',
            'slug' => 'mmsgi',
            'type' => 'holding',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $mmsResources = Company::create([
            'name' => 'MMS Resources',
            'slug' => 'mms-resources',
            'type' => 'sub-holding',
            'parent_id' => $mmsgi->id,
            'is_active' => true,
        ]);

        $mmsSolutions = Company::create([
            'name' => 'MMS Solutions',
            'slug' => 'mms-solutions',
            'type' => 'sub-holding',
            'parent_id' => $mmsgi->id,
            'is_active' => true,
        ]);

        $mmsLand = Company::create([
            'name' => 'MMS Land',
            'slug' => 'mms-land',
            'type' => 'sub-holding',
            'parent_id' => $mmsgi->id,
            'is_active' => true,
        ]);

        $mmsgi2 = Company::create([
            'name' => 'MMSGI 2',
            'slug' => 'mmsgi-2',
            'type' => 'holding',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $mitraKelola = Company::create([
            'name' => 'Mitra Kelola Saranajaya',
            'slug' => 'mitra-kelola-saranajaya',
            'type' => 'operating',
            'parent_id' => $mmsgi2->id,
            'is_active' => true,
        ]);

        // ─────────────────────────────────────────────
        // 3. Create Super Administrator (all roles, all companies)
        // ─────────────────────────────────────────────
        $superAdmin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@local.net',
            'password' => 'password',
        ]);

        $allCompanies = [$mmsgi, $mmsResources, $mmsSolutions, $mmsLand, $mmsgi2, $mitraKelola];

        foreach ($allCompanies as $company) {
            $superAdmin->companies()->attach($company->id, [
                'job_title' => 'System Administrator',
                'manager_id' => null,
                'status' => 'active',
            ]);

            $superAdmin->companyRoles()->attach($adminRole->id, ['company_id' => $company->id]);
            $superAdmin->companyRoles()->attach($managerRole->id, ['company_id' => $company->id]);
            $superAdmin->companyRoles()->attach($userRole->id, ['company_id' => $company->id]);
        }

        // ─────────────────────────────────────────────
        // 4. Define company config: min/max users, job titles pool
        // ─────────────────────────────────────────────
        $companyConfigs = [
            'mmsgi' => [
                'company' => $mmsgi,
                'min' => 5,
                'max' => 10,
                'rootUsers' => [1, 2],       // how many root-level users
                'titles' => [
                    'Chief Executive Officer',
                    'Chief Financial Officer',
                    'Chief Operating Officer',
                    'Chief Strategy Officer',
                    'Group Head of Corporate Development',
                    'Group Head of Legal',
                    'Group Head of Internal Audit',
                    'Corporate Secretary',
                    'Head of Investor Relations',
                    'Group Head of Human Capital',
                ],
            ],
            'mms_resources' => [
                'company' => $mmsResources,
                'min' => 8,
                'max' => 15,
                'rootUsers' => [1, 2],
                'titles' => [
                    'President Director',
                    'Operations Director',
                    'Finance Director',
                    'Mine Operations Manager',
                    'Geologist Manager',
                    'Exploration Manager',
                    'Project Development Manager',
                    'Supply Chain Manager',
                    'Procurement Manager',
                    'Contract Management Specialist',
                    'Mine Engineer',
                    'Geotechnical Engineer',
                    'Surveyor',
                    'Drill & Blast Supervisor',
                    'Production Supervisor',
                ],
            ],
            'mms_solutions' => [
                'company' => $mmsSolutions,
                'min' => 8,
                'max' => 15,
                'rootUsers' => [1, 2],
                'titles' => [
                    'Managing Director',
                    'Business Development Director',
                    'Technical Director',
                    'Project Manager',
                    'Engineering Manager',
                    'Technology Solutions Manager',
                    'Software Development Manager',
                    'IT Infrastructure Manager',
                    'Data Analytics Lead',
                    'Business Analyst',
                    'Solutions Architect',
                    'DevOps Engineer',
                    'Quality Assurance Lead',
                    'Technical Support Specialist',
                    'Implementation Consultant',
                ],
            ],
            'mms_land' => [
                'company' => $mmsLand,
                'min' => 8,
                'max' => 15,
                'rootUsers' => [1, 2],
                'titles' => [
                    'President Commissioner',
                    'Land Acquisition Director',
                    'Development Director',
                    'Property Development Manager',
                    'Estate Manager',
                    'Asset Management Manager',
                    'Legal & Compliance Manager',
                    'Community Relations Manager',
                    'Land Surveyor',
                    'Urban Planner',
                    'Permitting Specialist',
                    'Environmental Officer',
                    'GIS Analyst',
                    'Facilities Coordinator',
                    'Property Appraiser',
                ],
            ],
            'mmsgi2' => [
                'company' => $mmsgi2,
                'min' => 3,
                'max' => 8,
                'rootUsers' => [1, 2],
                'titles' => [
                    'Chief Executive Officer',
                    'Chief Investment Officer',
                    'Chief Financial Officer',
                    'Portfolio Manager',
                    'Investment Analyst',
                    'Risk Management Head',
                    'Corporate Planning Manager',
                    'Executive Assistant to CEO',
                ],
            ],
            'mitra_kelola' => [
                'company' => $mitraKelola,
                'min' => 10,
                'max' => 20,
                'rootUsers' => [1, 3],
                'titles' => [
                    'Branch Manager',
                    'Operations Supervisor',
                    'Project Supervisor',
                    'Field Operations Coordinator',
                    'Site Manager',
                    'Service Delivery Manager',
                    'Client Relations Manager',
                    'Administration Manager',
                    'HR & GA Supervisor',
                    'Finance & Accounting Supervisor',
                    'Field Crew Leader',
                    'Heavy Equipment Operator',
                    'Maintenance Technician',
                    'Logistics Coordinator',
                    'HSE Officer',
                    'Payroll Administrator',
                    'Procurement Officer',
                    'Driver',
                    'Security Coordinator',
                    'General Affairs Staff',
                ],
            ],
        ];

        // ─────────────────────────────────────────────
        // 4. Generate users for each company
        // ─────────────────────────────────────────────
        foreach ($companyConfigs as $config) {
            $company = $config['company'];
            $userCount = random_int($config['min'], $config['max']);
            $rootCount = random_int($config['rootUsers'][0], $config['rootUsers'][1]);
            $titles = $config['titles'];

            // Track user IDs assigned to this company (in order of creation)
            $companyUserIds = [];

            for ($i = 0; $i < $userCount; $i++) {
                // Generate user via factory
                $user = User::factory()->create([
                    'password' => 'password',
                ]);

                // Determine if this user is a root (no manager)
                $managerId = null;
                if ($i >= $rootCount && count($companyUserIds) > 0) {
                    // Pick a random manager from the company's existing users
                    $managerId = $companyUserIds[array_rand($companyUserIds)];
                }

                // Randomize status: ~85% active, ~10% on_leave, ~5% inactive
                $statusRoll = random_int(1, 100);
                $status = match (true) {
                    $statusRoll <= 85 => 'active',
                    $statusRoll <= 95 => 'on_leave',
                    default => 'inactive',
                };

                // Pick a random job title
                $jobTitle = $titles[array_rand($titles)];

                // Attach user to company
                $user->companies()->attach($company->id, [
                    'job_title' => $jobTitle,
                    'manager_id' => $managerId,
                    'status' => $status,
                ]);

                // Assign roles via company_user_role
                // - At least 1 admin per company for holding/sub-holding
                //   For operating companies, at least 1 manager
                // - ~20% get manager role
                // - Rest get user role
                $rolesToAssign = [$userRole->id];

                // Root user #1 always gets admin (for holding/sub-holding) or manager (for operating)
                if ($i === 0) {
                    if ($company->type === 'operating') {
                        $rolesToAssign[] = $managerRole->id;
                    } else {
                        $rolesToAssign[] = $adminRole->id;
                    }
                } elseif (random_int(1, 100) <= 20) {
                    // ~20% chance of extra manager role
                    $rolesToAssign[] = $managerRole->id;
                }

                foreach ($rolesToAssign as $roleId) {
                    $user->companyRoles()->attach($roleId, ['company_id' => $company->id]);
                }

                $companyUserIds[] = $user->id;
            }
        }
    }
}
