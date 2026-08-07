<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, UsersDataTable $dataTable): View
    {
        return view('admin.users.index', [
            'dataTable' => $dataTable->handle($request),
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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'auth_method' => ['nullable', 'string', Rule::in(array_keys(User::AUTH_METHODS))],
            'roles' => ['required', 'array'],
            'roles.*' => [Rule::exists('roles', 'id')->where('is_active', true)->where('deleted_at', null)],
        ]);

        $activeCompany = auth()->user()?->getActiveCompany();

        if (! $activeCompany) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No active company selected. Please select a company context before assigning roles.');
        }

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
            'auth_method' => $request->input('auth_method') ?? 'local',
        ]);

        // Enforce default 'user' role is always assigned
        $userRoleId = Role::where('name', 'user')->value('id');
        $roleIds = $request->input('roles', []);
        if ($userRoleId && ! in_array($userRoleId, $roleIds)) {
            $roleIds[] = $userRoleId;
        }

        // Attach the user to the active company
        $user->companies()->attach($activeCompany->id, [
            'job_title' => $request->input('job_title'),
            'manager_id' => $request->input('manager_id'),
        ]);

        // Attach company-scoped roles
        $user->companyRoles()->attach($roleIds, ['company_id' => $activeCompany->id]);

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
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'auth_method' => ['nullable', 'string', Rule::in(array_keys(User::AUTH_METHODS))],
            'roles' => ['required', 'array'],
            'roles.*' => [Rule::exists('roles', 'id')->where('is_active', true)->where('deleted_at', null)],
        ]);

        $activeCompany = auth()->user()?->getActiveCompany();

        if (! $activeCompany) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No active company selected. Please select a company context before updating roles.');
        }

        $userData = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'auth_method' => $request->input('auth_method', $user->auth_method ?? 'local'),
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->string('password')->toString());
        }

        $user->update($userData);

        // Enforce default 'user' role is always assigned
        $userRoleId = Role::where('name', 'user')->value('id');
        $roleIds = $request->input('roles', []);
        if ($userRoleId && ! in_array($userRoleId, $roleIds)) {
            $roleIds[] = $userRoleId;
        }

        // Sync company-scoped roles for the active company
        $pivotData = array_fill(0, count($roleIds), ['company_id' => $activeCompany->id]);
        $user->companyRoles()
            ->wherePivot('company_id', $activeCompany->id)
            ->sync(array_combine($roleIds, $pivotData));

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Assign the user to a company with roles and optional job title / manager.
     */
    public function assignCompany(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $companyId = (int) $request->input('company_id');

        // Prevent duplicate assignment
        if ($user->companies()->where('company_id', $companyId)->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This user is already assigned to that company.');
        }

        $user->companies()->attach($companyId, [
            'job_title' => $request->input('job_title'),
            'manager_id' => $request->input('manager_id') ? (int) $request->input('manager_id') : null,
            'status' => 'active',
        ]);

        // Attach company-scoped roles
        $user->companyRoles()->attach($request->input('roles'), ['company_id' => $companyId]);

        return redirect()->route('admin.users.edit', $user)->with('success', 'User assigned to company successfully.');
    }

    /**
     * Remove the user from a specific company.
     */
    public function removeCompany(User $user, int $companyId): RedirectResponse
    {
        $company = Company::findOrFail($companyId);

        // Reassign direct reports before removing the user
        $affectedCount = $company->users()
            ->wherePivot('manager_id', $user->id)
            ->newPivotStatement()
            ->where('company_id', $company->id)
            ->update(['manager_id' => null]);

        // Detach company-scoped roles
        $user->companyRoles()
            ->wherePivot('company_id', $company->id)
            ->detach();

        $user->companies()->detach($company->id);

        $message = 'User removed from company.';
        if ($affectedCount > 0) {
            $message .= ' '.$affectedCount.' team member(s) have been reassigned to report at the company level.';
        }

        return redirect()->route('admin.users.edit', $user)->with('success', $message);
    }
}
