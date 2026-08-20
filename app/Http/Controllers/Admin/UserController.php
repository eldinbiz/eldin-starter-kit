<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Users\AssignCompanyUserAction;
use App\Actions\Users\CreateUserAction;
use App\Actions\Users\DeleteUserAction;
use App\Actions\Users\RemoveCompanyUserAction;
use App\Actions\Users\UpdateUserAction;
use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\AssignCompanyUserRequest;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Http\Requests\Admin\Users\UpdateUserRequest;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, UsersDataTable $dataTable): View
    {
        $dataTable->handle($request);

        if ($dataTable->isPartialRequest($request)) {
            return $dataTable->renderPartialView();
        }

        return view('admin.users.index', [
            'dataTable' => $dataTable,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $roles = Role::where('is_active', true)->get();
        $activeCompany = auth()->user()?->getActiveCompany();

        return view('admin.users.create', compact('roles', 'activeCompany'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $activeCompany = $request->user()?->getActiveCompany();

        if (! $activeCompany) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No active company selected. Please select a company context before assigning roles.');
        }

        /** @var array{name: string, email: string, password: string, auth_method?: string|null, roles: array<int, int|string>, job_title?: string|null, manager_id?: int|string|null} $validated */
        $validated = $request->validated();

        $action->execute($activeCompany, $validated);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $roles = Role::where('is_active', true)->get();
        $activeCompany = auth()->user()?->getActiveCompany();
        $allCompanies = Company::where('is_active', true)->orderBy('name')->get();

        if ($activeCompany) {
            $userRoleIds = $user->companyRoles()
                ->wherePivot('company_id', $activeCompany->id)
                ->pluck('roles.id')
                ->toArray();
        } else {
            $userRoleIds = [];
        }

        // Reload companies with pivot data (job_title)
        $user->load('companies');

        // Load the user's assigned companies with their roles
        $assignedCompanies = $user->companies->map(function (Company $company) use ($user): Company {
            $company->user_roles = $user->companyRoles()
                ->wherePivot('company_id', $company->id)
                ->get();

            return $company;
        });

        // Companies not yet assigned to this user
        $assignedIds = $assignedCompanies->pluck('id');
        $availableCompanies = $allCompanies->whereNotIn('id', $assignedIds);

        return view('admin.users.edit', compact(
            'user', 'roles', 'userRoleIds', 'activeCompany',
            'assignedCompanies', 'availableCompanies'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $activeCompany = $request->user()?->getActiveCompany();

        if (! $activeCompany) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No active company selected. Please select a company context before updating roles.');
        }

        /** @var array{name: string, email: string, password?: string|null, auth_method?: string|null, roles: array<int, int|string>} $validated */
        $validated = $request->validated();

        $action->execute($user, $activeCompany, $validated);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user, DeleteUserAction $action): RedirectResponse
    {
        try {
            $action->execute($user, (int) Auth::id());
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.users.index')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Assign the user to a company with roles and optional job title / manager.
     */
    public function assignCompany(AssignCompanyUserRequest $request, User $user, AssignCompanyUserAction $action): RedirectResponse
    {
        /** @var array{company_id: int|string, roles: array<int, int|string>, job_title?: string|null, manager_id?: int|string|null} $validated */
        $validated = $request->validated();

        try {
            $action->execute($user, $validated);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.users.edit', $user)->with('success', 'User assigned to company successfully.');
    }

    /**
     * Remove the user from a specific company.
     */
    public function removeCompany(User $user, int $companyId, RemoveCompanyUserAction $action): RedirectResponse
    {
        $company = Company::findOrFail($companyId);

        $affectedCount = $action->execute($user, $company);

        $message = 'User removed from company.';
        if ($affectedCount > 0) {
            $message .= ' '.$affectedCount.' team member(s) have been reassigned to report at the company level.';
        }

        return redirect()->route('admin.users.edit', $user)->with('success', $message);
    }
}
