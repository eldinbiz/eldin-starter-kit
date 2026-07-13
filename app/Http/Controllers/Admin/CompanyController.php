<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /**
     * Display the company hierarchy tree view.
     */
    public function hierarchy(): View
    {
        $companies = Company::buildTree();

        return view('admin.companies.hierarchy', compact('companies'));
    }

    /**
     * Return the full company tree as JSON for the Alpine.js tree view.
     */
    public function tree(): JsonResponse
    {
        $roots = Company::buildTree();

        return response()->json([
            'companies' => $this->flattenTree($roots),
        ]);
    }

    /**
     * Reparent a company via drag-and-drop.
     */
    public function reparent(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'new_parent_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $companyId = (int) $request->input('company_id');
        $newParentId = $request->input('new_parent_id') ? (int) $request->input('new_parent_id') : null;

        $company = Company::findOrFail($companyId);

        // Prevent self-referencing
        if ($companyId === $newParentId) {
            return response()->json(['error' => 'A company cannot be its own parent.'], 422);
        }

        // Cycle detection: prevent moving a company under one of its own descendants
        if ($newParentId !== null) {
            $newParent = Company::findOrFail($newParentId);

            if ($newParent->isDescendantOf($company)) {
                return response()->json(['error' => 'Cannot move a company under one of its own subsidiaries.'], 422);
            }
        }

        $company->update([
            'parent_id' => $newParentId,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Toggle the active/inactive status of a company.
     * When disabling, validates that all children are already disabled.
     */
    public function toggleActive(Company $company): JsonResponse
    {
        if ($company->is_active) {
            // Disabling: check all children are already disabled
            if (! $company->allChildrenDisabled()) {
                return response()->json([
                    'error' => 'All child companies must be disabled before disabling this company.',
                ], 422);
            }
        }

        $company->update([
            'is_active' => ! $company->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $company->is_active,
        ]);
    }

    /**
     * Show the form for creating a new company.
     */
    public function create(): View
    {
        $parentCompanies = Company::orderBy('name')->get();
        $types = config('company.types', []);

        return view('admin.companies.create', compact('parentCompanies', 'types'));
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:companies,slug'],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(config('company.types', ['operating' => 'Operating Company'])))],
            'parent_id' => ['nullable', 'integer', 'exists:companies,id'],
            'logo' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg', 'max:2048'],
            'is_active' => ['boolean'],
        ]);

        $logoPath = null;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = \Illuminate\Support\Str::slug($request->string('name')->toString()) . '.' . $file->getClientOriginalExtension();
            $logoPath = $file->storeAs('logos', $filename, 'public');
        }

        Company::create([
            'name' => $request->string('name')->toString(),
            'slug' => $request->string('slug')->toString(),
            'type' => $request->string('type')->toString(),
            'parent_id' => $request->input('parent_id') ? (int) $request->input('parent_id') : null,
            'logo' => $logoPath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.companies.index')->with('success', 'Company created successfully.');
    }

    /**
     * Show the form for editing a company.
     */
    public function edit(Company $company): View
    {
        // Exclude self and all descendants from parent options to prevent cycle creation
        $excludeIds = $company->allChildren()->pluck('id')->push($company->id);

        $parentCompanies = Company::whereNotIn('id', $excludeIds)->orderBy('name')->get();
        $types = config('company.types', []);

        $companyUsers = $company->users()->get();

        // Eager load company-scoped roles for each user in this company
        $companyUsers->load(['companyRoles' => function ($query) use ($company): void {
            $query->wherePivot('company_id', $company->id);
        }]);

        $availableUsers = User::whereDoesntHave('companies', function ($query) use ($company): void {
            $query->where('company_id', $company->id);
        })->get();

        $roles = Role::all();

        return view('admin.companies.edit', compact('company', 'parentCompanies', 'types', 'companyUsers', 'availableUsers', 'roles'));
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, Company $company): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('companies', 'slug')->ignore($company->id)],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(config('company.types', ['operating' => 'Operating Company'])))],
            'parent_id' => ['nullable', 'integer', 'exists:companies,id'],
            'logo' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg', 'max:2048'],
            'is_active' => ['boolean'],
        ]);

        $newParentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;

        // Prevent self-referencing
        if ($newParentId === $company->id) {
            return redirect()->back()->withErrors(['parent_id' => 'A company cannot be its own parent.']);
        }

        // Cycle detection on edit as well
        if ($newParentId !== null) {
            $newParent = Company::findOrFail($newParentId);

            if ($newParent->isDescendantOf($company)) {
                return redirect()->back()->withErrors(['parent_id' => 'Cannot set a subsidiary as the parent company.']);
            }
        }

        $logoPath = $company->logo;

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $file = $request->file('logo');
            $filename = \Illuminate\Support\Str::slug($request->string('name')->toString()) . '.' . $file->getClientOriginalExtension();
            $logoPath = $file->storeAs('logos', $filename, 'public');
        }

        $company->update([
            'name' => $request->string('name')->toString(),
            'slug' => $request->string('slug')->toString(),
            'type' => $request->string('type')->toString(),
            'parent_id' => $newParentId,
            'logo' => $logoPath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.companies.index')->with('success', 'Company updated successfully.');
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

    /**
     * Recursively flatten a nested tree into a flat array of nodes with parent references.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Company>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private function flattenTree(\Illuminate\Database\Eloquent\Collection $nodes, ?int $parentId = null): array
    {
        $flat = [];

        foreach ($nodes as $node) {
            $flat[] = [
                'id' => $node->id,
                'name' => $node->name,
                'slug' => $node->slug,
                'type' => $node->type,
                'type_label' => $node->type_label,
                'parent_id' => $parentId,
                'is_active' => $node->is_active,
                'users_count' => $node->users_count ?? $node->users()->count(),
                'logo_url' => $node->logo ? asset('storage/' . $node->logo) : null,
            ];

            if ($node->relationLoaded('children') && $node->children->isNotEmpty()) {
                $flat = array_merge($flat, $this->flattenTree($node->children, $node->id));
            }
        }

        return $flat;
    }
}
