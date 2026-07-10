<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies.
     */
    public function index(): View
    {
        $companies = Company::with('parent')->withCount('users')->get();

        return view('admin.companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new company.
     */
    public function create(): View
    {
        $parentCompanies = Company::whereNull('parent_id')->get();

        return view('admin.companies.create', compact('parentCompanies'));
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:companies,slug'],
            'parent_id' => ['nullable', 'integer', 'exists:companies,id'],
            'logo' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        Company::create([
            'name' => $request->string('name')->toString(),
            'slug' => $request->string('slug')->toString(),
            'parent_id' => $request->input('parent_id') ? (int) $request->input('parent_id') : null,
            'logo' => $request->input('logo'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.companies.index')->with('success', 'Company created successfully.');
    }

    /**
     * Show the form for editing a company.
     */
    public function edit(Company $company): View
    {
        $parentCompanies = Company::whereNull('parent_id')
            ->where('id', '!=', $company->id)
            ->get();

        $companyUsers = $company->users()->get();

        // Eager load company-scoped roles for each user in this company
        $companyUsers->load(['companyRoles' => function ($query) use ($company): void {
            $query->wherePivot('company_id', $company->id);
        }]);

        $availableUsers = User::whereDoesntHave('companies', function ($query) use ($company): void {
            $query->where('company_id', $company->id);
        })->get();

        $roles = Role::all();

        return view('admin.companies.edit', compact('company', 'parentCompanies', 'companyUsers', 'availableUsers', 'roles'));
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, Company $company): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('companies', 'slug')->ignore($company->id)],
            'parent_id' => ['nullable', 'integer', 'exists:companies,id'],
            'logo' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $company->update([
            'name' => $request->string('name')->toString(),
            'slug' => $request->string('slug')->toString(),
            'parent_id' => $request->input('parent_id') ? (int) $request->input('parent_id') : null,
            'logo' => $request->input('logo'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.companies.index')->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified company.
     */
    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()->route('admin.companies.index')->with('success', 'Company deleted successfully.');
    }

    /**
     * Assign a user to this company.
     */
    public function assignUser(Request $request, Company $company): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $userId = (int) $request->input('user_id');

        $company->users()->attach($userId, [
            'job_title' => $request->input('job_title'),
            'manager_id' => $request->input('manager_id') ? (int) $request->input('manager_id') : null,
            'status' => 'active',
        ]);

        // Attach company-scoped roles
        $user = User::find($userId);
        if ($user) {
            $user->companyRoles()->attach($request->input('roles'), ['company_id' => $company->id]);
        }

        return redirect()->route('admin.companies.edit', $company)->with('success', 'User assigned to company.');
    }

    /**
     * Remove a user from this company.
     */
    public function removeUser(Company $company, int $userId): RedirectResponse
    {
        $company->users()->detach($userId);

        return redirect()->route('admin.companies.edit', $company)->with('success', 'User removed from company.');
    }
}
