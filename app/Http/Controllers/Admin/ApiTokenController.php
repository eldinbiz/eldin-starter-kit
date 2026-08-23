<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ApiTokens\CreateApiTokenAction;
use App\Actions\ApiTokens\RevokeApiTokenAction;
use App\DataTables\ApiTokensDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApiTokens\StoreApiTokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request, ApiTokensDataTable $dataTable): mixed
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
    public function store(StoreApiTokenRequest $request, CreateApiTokenAction $action): RedirectResponse
    {
        $token = $action->execute($request->validated(), $request->user());

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'API Token generated successfully.',
            'api_token' => $token->plainTextToken,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, RevokeApiTokenAction $action): RedirectResponse
    {
        $action->execute($id);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'API Token revoked successfully.',
        ]);
    }
}
