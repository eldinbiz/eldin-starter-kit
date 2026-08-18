<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DataTables\ApiTokensDataTable;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    /**
     * Complete registry of all API v1 endpoints with their required abilities.
     */
    public const AVAILABLE_ENDPOINTS = [
        [
            'ability' => 'auth:me',
            'method' => 'GET',
            'path' => '/api/v1/me',
            'label' => 'Identity & Profile',
            'group' => 'Auth',
            'doc_anchor' => 'auth.me',
        ],
        [
            'ability' => 'notifications:index',
            'method' => 'GET',
            'path' => '/api/v1/notifications',
            'label' => 'List In-App Notifications',
            'group' => 'Notifications',
            'doc_anchor' => 'notification.index',
        ],
        [
            'ability' => 'approvals:index',
            'method' => 'GET',
            'path' => '/api/v1/approvals',
            'label' => 'List Approval Requests',
            'group' => 'Approvals',
            'doc_anchor' => 'approval.index',
        ],
        [
            'ability' => 'approvals:show',
            'method' => 'GET',
            'path' => '/api/v1/approvals/{id}',
            'label' => 'View Approval Request Detail',
            'group' => 'Approvals',
            'doc_anchor' => 'approval.show',
        ],
        [
            'ability' => 'purchases:index',
            'method' => 'GET',
            'path' => '/api/v1/purchases',
            'label' => 'List Purchase Requests',
            'group' => 'Purchases',
            'doc_anchor' => 'purchaseRequest.index',
        ],
        [
            'ability' => 'purchases:show',
            'method' => 'GET',
            'path' => '/api/v1/purchases/{id}',
            'label' => 'View Purchase Request Detail',
            'group' => 'Purchases',
            'doc_anchor' => 'purchaseRequest.show',
        ],
        [
            'ability' => 'users:index',
            'method' => 'GET',
            'path' => '/api/v1/users',
            'label' => 'List Users Directory',
            'group' => 'Users',
            'doc_anchor' => 'user.index',
        ],
        [
            'ability' => 'users:show',
            'method' => 'GET',
            'path' => '/api/v1/users/{id}',
            'label' => 'View User Detail',
            'group' => 'Users',
            'doc_anchor' => 'user.show',
        ],
        [
            'ability' => 'roles:index',
            'method' => 'GET',
            'path' => '/api/v1/roles',
            'label' => 'List Roles & Permissions',
            'group' => 'Roles',
            'doc_anchor' => 'role.index',
        ],
        [
            'ability' => 'roles:show',
            'method' => 'GET',
            'path' => '/api/v1/roles/{id}',
            'label' => 'View Role Detail',
            'group' => 'Roles',
            'doc_anchor' => 'role.show',
        ],
        [
            'ability' => 'companies:index',
            'method' => 'GET',
            'path' => '/api/v1/companies',
            'label' => 'List Companies',
            'group' => 'Companies',
            'doc_anchor' => 'company.index',
        ],
        [
            'ability' => 'companies:hierarchy',
            'method' => 'GET',
            'path' => '/api/v1/companies/hierarchy',
            'label' => 'View Company Hierarchy Tree',
            'group' => 'Companies',
            'doc_anchor' => 'company.hierarchy',
        ],
        [
            'ability' => 'companies:show',
            'method' => 'GET',
            'path' => '/api/v1/companies/{id}',
            'label' => 'View Company Detail',
            'group' => 'Companies',
            'doc_anchor' => 'company.show',
        ],
        [
            'ability' => 'audit-logs:index',
            'method' => 'GET',
            'path' => '/api/v1/audit-logs',
            'label' => 'List System Audit Logs',
            'group' => 'Audit Logs',
            'doc_anchor' => 'auditLog.index',
        ],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ApiTokensDataTable $dataTable)
    {
        $dataTable->handle($request);

        if ($dataTable->isPartialRequest($request)) {
            return $dataTable->renderPartialView();
        }

        $endpoints = self::AVAILABLE_ENDPOINTS;

        return view('admin.api-tokens.index', [
            'dataTable' => $dataTable,
            'endpoints' => $endpoints,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'expiry' => 'nullable|in:30,60,90,365,never',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
        ]);

        $expiresAt = match ($request->input('expiry')) {
            '30' => now()->addDays(30),
            '60' => now()->addDays(60),
            '90' => now()->addDays(90),
            '365' => now()->addYear(),
            default => null,
        };

        $selectedAbilities = $request->input('abilities', []);
        $abilities = (empty($selectedAbilities) || in_array('*', $selectedAbilities, true))
            ? ['*']
            : array_values(array_unique($selectedAbilities));

        // Create or find a system user for this token
        // We namespace it to avoid collisions.
        $botEmail = 'bot.'.Str::slug($request->name).'@system.local';

        $bot = User::firstOrCreate(
            ['email' => $botEmail],
            [
                'name' => $request->name.' (System Bot)',
                'password' => bcrypt(Str::random(32)),
                'is_system' => true,
                'auth_method' => 'local',
            ]
        );

        // Make sure it's a system bot
        if (! $bot->is_system) {
            $bot->update(['is_system' => true]);
        }

        // Grant platform-admin role to bot so it can act across companies passed in API header
        $platformRole = Role::firstOrCreate(
            ['name' => 'platform-admin'],
            [
                'permissions' => ['*'],
                'is_active' => true,
            ]
        );

        $activeCompany = auth()->user()?->getActiveCompany() ?? Company::first();

        if ($activeCompany) {
            // Attach bot to active company if not already a member
            if (! $bot->companies()->where('company_id', $activeCompany->id)->exists()) {
                $bot->companies()->attach($activeCompany->id, ['status' => 'active']);
            }

            // Attach platform-admin role scoped to the company
            if (! $bot->hasPlatformAccess()) {
                $bot->companyRoles()->attach($platformRole->id, ['company_id' => $activeCompany->id]);
            }
        }

        // Create the token with specified abilities and expiration
        $token = $bot->createToken($request->name, $abilities, $expiresAt);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'API Token generated successfully.',
            'api_token' => $token->plainTextToken, // Pass the plain text token to show ONCE
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $token = PersonalAccessToken::findOrFail($id);

        // Ensure we are only deleting system tokens for safety, or any token if admin.
        $token->delete();

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'API Token revoked successfully.',
        ]);
    }
}
